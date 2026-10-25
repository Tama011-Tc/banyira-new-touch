<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Listing;
use App\Models\User;
use App\Support\MessageModerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Approved applications become public listings only after a prepared, independently reviewed, non-confidential summary. */
class ListingService
{
    public function __construct(private readonly ApplicationWorkflow $workflow, private readonly AuditLogger $audit) {}

    public function prepare(Application $app, User $preparer, array $data): Listing
    {
        abort_unless($preparer->role->can('listing.manage'), 403);
        if ($app->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Only approved applications can be prepared for listing.']);
        }
        foreach (['title', 'summary', 'risk_disclosure'] as $f) {
            if (MessageModerator::inspect((string) ($data[$f] ?? ''))['action'] === MessageModerator::BLOCK) {
                throw ValidationException::withMessages([$f => 'Listing text cannot contain contact details, links or payment instructions.']);
            }
        }
        $decision = $app->decisions()->where('decision', 'approved')->latest('id')->firstOrFail();
        $assessment = $app->assessments()->latest('id')->firstOrFail();
        $target = $decision->approved_amount;
        $min = (int) $data['min_investment'];
        $max = (int) $data['max_investment'];
        if ($min <= 0 || $max < $min || $max > $target) {
            throw ValidationException::withMessages(['min_investment' => 'Investment limits must satisfy 0 < min ≤ max ≤ funding target.']);
        }

        return DB::transaction(function () use ($app, $preparer, $data, $decision, $assessment, $target, $min, $max) {
            $l = Listing::create([
                'application_id' => $app->id, 'title' => $data['title'], 'summary' => $data['summary'], 'risk_disclosure' => $data['risk_disclosure'],
                'funding_target' => $target, 'min_investment' => $min, 'max_investment' => $max, 'rate_bp' => $app->proposed_rate_bp, 'max_rate_bp' => $decision->max_rate_bp,
                'term_months' => $app->term_months, 'frequency' => $app->frequency, 'interest_method' => $app->interest_method, 'risk_category' => $assessment->risk_category,
                'assessed_on' => $assessment->created_at->toDateString(), 'deadline' => $data['deadline'], 'status' => 'draft',
            ]);
            $this->workflow->transition($app, 'listing_prepared', $preparer);
            $this->audit->record('listing.prepared', $l, [], $preparer);

            return $l;
        });
    }

    /** Four-eyes: the reviewer must not be the person who prepared the listing. */
    public function review(Listing $listing, User $reviewer): Listing
    {
        abort_unless($reviewer->role->can('listing.manage'), 403);
        $preparedBy = \App\Models\AuditLog::where('action', 'listing.prepared')->where('subject_type', 'Listing')->where('subject_id', $listing->id)->value('actor_id');
        if ($preparedBy === $reviewer->id) {
            throw ValidationException::withMessages(['review' => 'A listing must be reviewed by someone other than its preparer.']);
        }
        if ($listing->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'This listing is not awaiting review.']);
        }
        $listing->forceFill(['status' => 'reviewed', 'reviewed_by' => $reviewer->id])->save();
        $this->audit->record('listing.reviewed', $listing, [], $reviewer);

        return $listing;
    }

    public function publish(Listing $listing, User $publisher): Listing
    {
        abort_unless($publisher->role->can('listing.manage'), 403);
        if ($listing->status !== 'reviewed') {
            throw ValidationException::withMessages(['status' => 'A listing must be reviewed before it is published.']);
        }
        if ($listing->deadline->isPast()) {
            throw ValidationException::withMessages(['deadline' => 'The funding deadline has passed.']);
        }

        return DB::transaction(function () use ($listing, $publisher) {
            $listing->forceFill(['status' => 'published', 'published_by' => $publisher->id, 'published_at' => now()])->save();
            $this->workflow->transition($listing->application, 'published', $publisher);
            $this->audit->record('listing.published', $listing, [], $publisher);

            return $listing;
        });
    }

    /** What a listing may reveal. Business identity is limited to the approved display name, sector and district. */
    public function publicView(Listing $l): array
    {
        $b = $l->application->business;
        $committed = $this->committed($l);

        return [
            'id' => $l->id, 'title' => $l->title, 'business' => $b->display_name, 'sector' => $b->sector, 'district' => $b->district,
            'years_operating' => $b->years_operating, 'verified' => $b->verification_status === 'verified', 'summary' => $l->summary, 'purpose' => $l->application->purpose,
            'risk_disclosure' => $l->risk_disclosure, 'risk_category' => $l->risk_category, 'assessed_on' => $l->assessed_on->toDateString(),
            'target' => $l->funding_target, 'committed' => $committed, 'remaining' => max(0, $l->funding_target - $committed), 'percent' => $l->funding_target ? min(100, (int) floor($committed * 100 / $l->funding_target)) : 0,
            'min_investment' => $l->min_investment, 'max_investment' => $l->max_investment, 'rate_bp' => $l->rate_bp, 'max_rate_bp' => $l->max_rate_bp,
            'term_months' => $l->term_months, 'frequency' => $l->frequency, 'interest_method' => $l->interest_method, 'deadline' => $l->deadline->toDateString(), 'status' => $l->status,
            'fees' => FeeService::snapshot($l->funding_target), 'fee_tiers' => app(FeeScheduleService::class)->active()?->tiers ?? [],
        ];
    }

    public function committed(Listing $l): int
    {
        return (int) \App\Models\LoanAgreement::where('listing_id', $l->id)->whereNotIn('status', ['cancelled'])->sum('principal');
    }
}
