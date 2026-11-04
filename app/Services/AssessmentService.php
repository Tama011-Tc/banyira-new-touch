<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentCriteriaSet;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Structured credit assessment. The score is the weighted sum of 1–5 ratings
 * against the active, versioned criteria; the suggested class comes from the
 * active rule set's risk bands. Overrides need a written reason. The officer
 * recommends; a different, authorised employee decides.
 */
class AssessmentService
{
    public function __construct(private ApplicationWorkflow $workflow, private RulesService $rules, private AuditLogger $audit) {}

    public function start(LoanApplication $app, User $officer): Assessment
    {
        return DB::transaction(function () use ($app, $officer) {
            $set = AssessmentCriteriaSet::where('status', 'active')->firstOrFail();
            $assessment = $app->assessments()->create([
                'assessor_id' => $officer->id,
                'criteria_set_id' => $set->id,
                'rule_set_id' => $this->rules->active()->id,
                'status' => 'draft',
            ]);
            $this->workflow->transition($app, 'assessment_in_progress', $officer, 'Assessment started');

            return $assessment;
        });
    }

    public function save(Assessment $a, array $data, User $officer): Assessment
    {
        $this->ensureEditable($a, $officer);
        $a->loadMissing('criteriaSet.criteria', 'ruleSet.riskBands');

        return DB::transaction(function () use ($a, $data) {
            foreach ($a->criteriaSet->criteria as $c) {
                if (isset($data['scores'][$c->id])) {
                    $a->scores()->updateOrCreate(['criterion_id' => $c->id], ['score' => (int) $data['scores'][$c->id], 'note' => $data['notes'][$c->id] ?? null]);
                }
            }
            $a->load('scores');
            $total = $this->score($a);
            $a->fill([
                'checks' => $data['checks'] ?? $a->checks,
                'risks' => array_values(array_filter($data['risks'] ?? $a->risks ?? [])),
                'total_score' => $total,
                'suggested_class' => $total === null ? null : $this->rules->classForScore($total, $a->ruleSet),
                'final_class' => $data['final_class'] ?? null,
                'override_reason' => $data['override_reason'] ?? null,
                'recommendation' => $data['recommendation'] ?? $a->recommendation,
                'comments' => $data['comments'] ?? $a->comments,
            ])->save();

            return $a;
        });
    }

    /** Weighted score out of 100, or null until every criterion is rated. */
    public function score(Assessment $a): ?float
    {
        $criteria = $a->criteriaSet->criteria;
        $scores = $a->scores->keyBy('criterion_id');
        if ($criteria->contains(fn ($c) => ! $scores->has($c->id))) {
            return null;
        }

        return round($criteria->sum(fn ($c) => $scores[$c->id]->score / 5 * $c->weight), 2);
    }

    public function classOf(Assessment $a): ?string
    {
        return $a->final_class ?: $a->suggested_class;
    }

    public function submit(Assessment $a, User $officer): Assessment
    {
        $this->ensureEditable($a, $officer);
        $a->loadMissing('application', 'ruleSet.riskBands', 'criteriaSet.criteria', 'scores');
        $errors = [];
        if ($a->total_score === null) {
            $errors['scores'] = 'Rate every assessment criterion before submitting.';
        }
        if (blank($a->comments)) {
            $errors['comments'] = 'Add assessment comments.';
        }
        if (! $a->recommendation) {
            $errors['recommendation'] = 'Choose a recommendation.';
        }
        if ($a->recommendation === 'approve') {
            $class = $this->classOf($a);
            if (! $class) {
                $errors['final_class'] = 'The score is below the lowest risk class. Approval is not possible.';
            } else {
                if ($a->final_class && $a->final_class !== $a->suggested_class && blank($a->override_reason)) {
                    $errors['override_reason'] = 'Explain why the class differs from the suggested class.';
                }
                if ($b = $this->rules->bandBreaches($class, $a->application->amount, $a->application->term_months, $a->ruleSet)) {
                    $errors['final_class'] = implode(' ', $b);
                }
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $a->update(['status' => 'submitted', 'submitted_at' => now()]);
        $this->audit->log('assessment', "Recommendation submitted for {$a->application->reference}: {$a->recommendation}", $a, ['class' => $this->classOf($a), 'score' => $a->total_score], $officer);

        return $a;
    }

    public function decide(Assessment $a, bool $approve, User $approver, ?string $note = null): Assessment
    {
        if ($a->status !== 'submitted') {
            throw ValidationException::withMessages(['decision' => 'Only submitted recommendations can be decided.']);
        }
        if ($a->assessor_id === $approver->id) {
            throw ValidationException::withMessages(['decision' => 'You cannot decide on your own assessment.']);
        }

        return DB::transaction(function () use ($a, $approve, $approver, $note) {
            $app = $a->application;
            $a->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $approver->id, 'decided_at' => now(), 'decision_note' => $note]);
            if ($approve) {
                $app->risk_class = $this->classOf($a);
                $app->rule_set_id = $a->rule_set_id;
                $app->save();
            }
            $this->workflow->transition($app, $approve ? 'approved' : 'rejected', $approver, $note ?: ($approve ? "Approved with class {$app->risk_class}" : 'Rejected'));

            return $a;
        });
    }

    private function ensureEditable(Assessment $a, User $officer): void
    {
        if ($a->status !== 'draft') {
            throw ValidationException::withMessages(['assessment' => 'This assessment has been submitted and can no longer be edited.']);
        }
        if ($a->assessor_id !== $officer->id) {
            throw ValidationException::withMessages(['assessment' => 'Only the assigned assessor can edit this assessment.']);
        }
    }
}
