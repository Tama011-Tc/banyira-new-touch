<?php

namespace App\Services;

use App\Models\Application;
use App\Models\CreditAssessment;
use App\Models\CreditDecision;
use App\Models\User;
use App\Support\LoanCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Transparent, configurable scoring plus a human decision. The score only SUGGESTS a category; analysts may adjust
 * within a bounded range with written reasons, and a different approver records the decision. No protected
 * characteristic (gender, ethnicity, religion, age, etc.) is an input, and none exists in the data model.
 */
class CreditService
{
    public const MAX_ADJUSTMENT = 15;

    public function __construct(private readonly ApplicationWorkflow $workflow, private readonly AuditLogger $audit) {}

    /** @return array{score:int, category:string, criteria:array<string, array{label:string, weight:int, score:int, detail:string}>} */
    public function suggest(Application $app): array
    {
        $weights = Settings::get('credit.weights');
        $sched = LoanCalculator::schedule($app->amount, $app->proposed_rate_bp, $app->term_months, $app->frequency, $app->interest_method, 0, now()->toDateString());
        $perInstalment = max(1, $sched['instalments'][0]['total']);
        $monthlyPayment = $app->frequency === 'weekly' ? (int) round($perInstalment * 52 / 12) : $perInstalment;
        $surplus = $app->monthly_revenue - $app->monthly_expenses;
        $dscr = $surplus / $monthlyPayment;
        $dscrScore = $dscr >= 2 ? 100 : ($dscr >= 1.5 ? 80 : ($dscr >= 1.2 ? 60 : ($dscr >= 1 ? 40 : 10)));

        $years = $app->business->years_operating;
        $histScore = $years >= 5 ? 100 : ($years >= 3 ? 80 : ($years >= 2 ? 60 : ($years >= 1 ? 40 : 15)));

        $required = config('banyira.documents.required');
        $have = $app->documents()->pluck('type')->unique()->all();
        $docScore = (int) round(100 * count(array_intersect($required, $have)) / max(1, count($required)));
        $docScore = min(100, $docScore + 5 * count(array_diff($have, $required)));

        $annualRevenue = max(1, $app->monthly_revenue * 12);
        $debtRatio = $app->existing_debt / $annualRevenue;
        $debtScore = $debtRatio < 0.1 ? 100 : ($debtRatio < 0.25 ? 75 : ($debtRatio < 0.5 ? 50 : 20));

        $sectorScore = (int) (Settings::get('credit.sector_scores')[$app->business->sector] ?? 55);

        $c = [
            'dscr' => ['label' => 'Repayment capacity', 'score' => $dscrScore, 'detail' => sprintf('Monthly surplus is %.2f× the estimated monthly repayment.', $dscr)],
            'history' => ['label' => 'Operating history', 'score' => $histScore, 'detail' => "{$years} year(s) trading."],
            'documents' => ['label' => 'Documentation', 'score' => $docScore, 'detail' => count(array_intersect($required, $have)).' of '.count($required).' required documents provided.'],
            'debt' => ['label' => 'Existing debt', 'score' => $debtScore, 'detail' => sprintf('Existing debt is %.0f%% of annual revenue.', $debtRatio * 100)],
            'sector' => ['label' => 'Sector risk', 'score' => $sectorScore, 'detail' => "{$app->business->sector} (configurable sector score)."],
        ];
        $total = 0;
        foreach ($c as $k => &$row) {
            $row['weight'] = (int) $weights[$k];
            $total += $row['score'] * $row['weight'];
        }
        $score = (int) round($total / max(1, array_sum($weights)));

        return ['score' => $score, 'category' => self::category($score), 'criteria' => $c];
    }

    public static function category(int $score): string
    {
        return $score >= 80 ? 'A' : ($score >= 65 ? 'B' : ($score >= 50 ? 'C' : 'D'));
    }

    public function startScreening(Application $app, User $analyst): void
    {
        $this->workflow->transition($app, 'screening', $analyst);
        $app->forceFill(['assigned_to' => $app->assigned_to ?? $analyst->id])->save();
        $this->audit->record('credit.case_taken', $app, [], $analyst);
    }

    public function beginAssessment(Application $app, User $analyst): void
    {
        $this->workflow->transition($app, 'assessment', $analyst);
    }

    public function requestInfo(Application $app, User $actor, string $request): void
    {
        if (mb_strlen(trim($request)) < 10) {
            throw ValidationException::withMessages(['request' => 'Explain what additional information is needed.']);
        }
        $app->forceFill(['info_request' => $request])->save();
        $this->workflow->transition($app, 'info_requested', $actor, $request);
    }

    public function assess(Application $app, User $analyst, int $finalScore, string $notes, string $recommendation): CreditAssessment
    {
        abort_unless($analyst->role->can('credit.assess'), 403);
        if ($app->status !== 'assessment') {
            throw ValidationException::withMessages(['status' => 'The application is not in credit assessment.']);
        }
        if (! in_array($recommendation, ['approve', 'reject', 'refer'], true)) {
            throw ValidationException::withMessages(['recommendation' => 'Choose a recommendation.']);
        }
        $suggested = $this->suggest($app);
        if (abs($finalScore - $suggested['score']) > self::MAX_ADJUSTMENT) {
            throw ValidationException::withMessages(['score' => 'A score may differ from the calculated one by at most '.self::MAX_ADJUSTMENT.' points. Refer the case if more is warranted.']);
        }
        if ($finalScore !== $suggested['score'] && mb_strlen(trim($notes)) < 20) {
            throw ValidationException::withMessages(['notes' => 'Explain the adjustment in your notes (at least 20 characters).']);
        }
        if (mb_strlen(trim($notes)) < 10) {
            throw ValidationException::withMessages(['notes' => 'Assessment notes are required.']);
        }

        return DB::transaction(function () use ($app, $analyst, $finalScore, $notes, $recommendation, $suggested) {
            $a = CreditAssessment::create([
                'application_id' => $app->id, 'analyst_id' => $analyst->id, 'score' => $finalScore, 'risk_category' => self::category($finalScore),
                'criteria' => ['calculated_score' => $suggested['score'], 'items' => $suggested['criteria']], 'notes' => $notes, 'recommendation' => $recommendation,
            ]);
            $this->audit->record('credit.assessed', $app, ['score' => $finalScore, 'category' => $a->risk_category, 'recommendation' => $recommendation], $analyst);

            return $a;
        });
    }

    public function decide(Application $app, User $approver, string $decision, string $reason, ?string $conditions = null, ?int $approvedAmount = null, ?int $maxRateBp = null): CreditDecision
    {
        abort_unless($approver->role->can('credit.approve'), 403);
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose approve or reject.']);
        }
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'A written reason is required for every decision.']);
        }
        if ($app->status !== 'assessment') {
            throw ValidationException::withMessages(['status' => 'The application is not awaiting a decision.']);
        }
        $assessment = $app->assessments()->latest('id')->first();
        if (! $assessment) {
            throw ValidationException::withMessages(['assessment' => 'A credit assessment must be recorded before a decision.']);
        }
        if (Settings::get('credit.separate_approver') && $assessment->analyst_id === $approver->id) {
            throw ValidationException::withMessages(['decision' => 'The approver must be a different person from the analyst.']);
        }
        if ($decision === 'approved') {
            $amount = $approvedAmount ?? $app->amount;
            if ($amount <= 0 || $amount > $app->amount) {
                throw ValidationException::withMessages(['approved_amount' => 'The approved amount cannot exceed the amount requested.']);
            }
            if ($amount > (int) Settings::get('credit.executive_threshold') && $approver->role->value !== 'executive') {
                throw ValidationException::withMessages(['decision' => 'Amounts above MK '.number_format(Settings::get('credit.executive_threshold') / 100).' need an executive approver.']);
            }
            $maxRateBp ??= min((int) Settings::get('loan.max_rate_bp'), $app->proposed_rate_bp + 300);
            if ($maxRateBp > (int) Settings::get('loan.max_rate_bp')) {
                throw ValidationException::withMessages(['max_rate_bp' => 'The rate ceiling exceeds the platform maximum.']);
            }
        }

        return DB::transaction(function () use ($app, $approver, $decision, $reason, $conditions, $approvedAmount, $maxRateBp) {
            $d = CreditDecision::create([
                'application_id' => $app->id, 'decided_by' => $approver->id, 'decision' => $decision, 'reason' => $reason, 'conditions' => $conditions,
                'approved_amount' => $decision === 'approved' ? ($approvedAmount ?? $app->amount) : null, 'max_rate_bp' => $decision === 'approved' ? $maxRateBp : null,
            ]);
            $this->workflow->transition($app, $decision === 'approved' ? 'approved' : 'rejected', $approver, $reason);

            return $d;
        });
    }

    /** Material terms changed after approval: the approval no longer covers them, so the application must be re-assessed. */
    public function amendTerms(Application $app, User $actor, array $changes): void
    {
        $material = ['amount', 'term_months', 'proposed_rate_bp', 'frequency', 'interest_method'];
        $touch = array_intersect_key($changes, array_flip($material));
        $changed = array_filter($touch, fn ($v, $k) => (string) $app->{$k} !== (string) $v, ARRAY_FILTER_USE_BOTH);
        if (! $changed) {
            return;
        }
        if (in_array($app->status, ['approved', 'listing_prepared', 'published', 'funding'], true)) {
            throw ValidationException::withMessages(['terms' => 'Material terms cannot be edited after approval. Withdraw and resubmit for re-approval.']);
        }
        $app->forceFill($changed + ['terms_version' => ($app->terms_version ?? 1) + 1])->save();
        $this->audit->record('application.terms_changed', $app, ['changed' => array_keys($changed)], $actor);
    }
}
