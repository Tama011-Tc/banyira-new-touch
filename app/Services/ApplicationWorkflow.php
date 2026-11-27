<?php

namespace App\Services;

use App\Models\InformationRequest;
use App\Models\LoanApplication;
use App\Models\User;
use App\Notifications\Alert;
use App\Support\Reference;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns every status change of a financing application. Only the transitions
 * listed here are possible, and each one is recorded in the status history.
 */
class ApplicationWorkflow
{
    private const TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['under_review'],
        'under_review' => ['information_required', 'assessment_in_progress', 'rejected'],
        'information_required' => ['under_review'],
        'assessment_in_progress' => ['information_required', 'approved', 'rejected'],
        'approved' => ['listed'],
        'listed' => ['fully_funded', 'closed'],
        'fully_funded' => ['closed'],
    ];

    public function __construct(private AuditLogger $audit, private StaffNotifier $staff) {}

    public function createDraft(User $owner, array $data): LoanApplication
    {
        $app = new LoanApplication($data);
        $app->business_profile_id = $owner->businessProfile->id;
        $app->reference = Reference::make('APP');
        $app->status = 'draft';
        $app->save();
        $app->history()->create(['to_status' => 'draft', 'user_id' => $owner->id, 'note' => 'Draft created']);

        return $app;
    }

    public function transition(LoanApplication $app, string $to, ?User $by, ?string $note = null): LoanApplication
    {
        $from = $app->status;
        if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "An application cannot move from '{$app->statusLabel()}' to '".(LoanApplication::STATUSES[$to] ?? $to)."'."]);
        }

        DB::transaction(function () use ($app, $from, $to, $by, $note) {
            $app->status = $to;
            match ($to) {
                'submitted' => $app->submitted_at = now(),
                'approved', 'rejected' => $app->decided_at = now(),
                default => null,
            };
            $app->save();
            $app->history()->create(['from_status' => $from, 'to_status' => $to, 'user_id' => $by?->id, 'note' => $note]);
            $this->audit->log('applications', "Application {$app->reference}: {$from} → {$to}", $app, ['note' => $note], $by);
        });

        $this->notifyOwner($app, $to);

        return $app;
    }

    public function submit(LoanApplication $app, User $owner): LoanApplication
    {
        $app->loadMissing('product', 'documents');
        $missing = [];
        foreach (['amount' => 'amount', 'term_months' => 'repayment period', 'purpose' => 'purpose'] as $field => $label) {
            if (blank($app->{$field})) {
                $missing[] = $label;
            }
        }
        if ($app->documents->isEmpty()) {
            $missing[] = 'at least one supporting document';
        }
        if ($missing) {
            throw ValidationException::withMessages(['application' => 'Before submitting, add: '.implode(', ', $missing).'.']);
        }
        if (bccomp($app->amount, $app->product->min_amount, 2) < 0 || bccomp($app->amount, $app->product->max_amount, 2) > 0) {
            throw ValidationException::withMessages(['amount' => 'The amount must be within this product\'s financing limits.']);
        }

        $this->transition($app, 'submitted', $owner, 'Submitted by business');
        $this->staff->holders('applications.assess', 'New application', "{$app->reference} is waiting for review.", '/internal/credit/applications/'.$app->id);

        return $app;
    }

    public function startReview(LoanApplication $app, User $officer): LoanApplication
    {
        $app->assigned_to = $officer->id;
        $app->save();

        return $this->transition($app, 'under_review', $officer, 'Assigned to '.$officer->name);
    }

    public function requestInformation(LoanApplication $app, User $officer, string $message): InformationRequest
    {
        return DB::transaction(function () use ($app, $officer, $message) {
            $req = $app->informationRequests()->create(['requested_by' => $officer->id, 'message' => $message, 'status' => 'open']);
            $this->transition($app, 'information_required', $officer, 'Information requested');

            return $req;
        });
    }

    public function respond(InformationRequest $req, User $owner, string $response): void
    {
        if ($req->status !== 'open') {
            throw ValidationException::withMessages(['response' => 'This request has already been answered.']);
        }
        DB::transaction(function () use ($req, $owner, $response) {
            $req->update(['response' => $response, 'responded_at' => now(), 'status' => 'answered']);
            $app = $req->application;
            if ($app->status === 'information_required' && ! $app->informationRequests()->where('status', 'open')->exists()) {
                $this->transition($app, 'under_review', $owner, 'Business responded to information request');
            }
        });
    }

    private function notifyOwner(LoanApplication $app, string $to): void
    {
        $messages = [
            'submitted' => ['Application submitted', "We received application {$app->reference}. A credit officer will review it."],
            'under_review' => ['Application under review', "A credit officer is reviewing {$app->reference}."],
            'information_required' => ['More information needed', "Please respond to the request on {$app->reference}."],
            'approved' => ['Application approved', "{$app->reference} was approved. It will be published to investors shortly."],
            'rejected' => ['Application not approved', "{$app->reference} was not approved. See your dashboard for details."],
            'listed' => ['Listed on the marketplace', "{$app->reference} is now visible to investors."],
            'fully_funded' => ['Fully funded', "Investors have committed the full amount for {$app->reference}."],
        ];
        if (! isset($messages[$to])) {
            return;
        }
        $owner = $app->business()->with('user')->first()?->user;
        $owner?->notify(new Alert($messages[$to][0], $messages[$to][1], '/business/applications/'.$app->id, sms: true));
    }
}
