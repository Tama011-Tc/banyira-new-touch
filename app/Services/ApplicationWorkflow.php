<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** The application lifecycle as an explicit state machine. Every transition is validated, authorised and audited. */
class ApplicationWorkflow
{
    public const STATES = ['draft', 'submitted', 'screening', 'assessment', 'info_requested', 'approved', 'rejected', 'listing_prepared', 'published', 'funding', 'funded', 'repayment_active', 'completed', 'overdue', 'restructured', 'defaulted', 'withdrawn', 'expired', 'suspended', 'cancelled'];

    /** from => [to => ability|null]. null = system transition; 'applicant' = owner or the assisting sales rep. */
    private const MAP = [
        'draft' => ['submitted' => 'applicant', 'withdrawn' => 'applicant'],
        'submitted' => ['screening' => 'credit.assess', 'withdrawn' => 'applicant'],
        'screening' => ['assessment' => 'credit.assess', 'info_requested' => 'credit.assess'],
        'assessment' => ['info_requested' => 'credit.assess', 'approved' => 'credit.approve', 'rejected' => 'credit.approve'],
        'info_requested' => ['submitted' => 'applicant', 'withdrawn' => 'applicant'],
        'approved' => ['listing_prepared' => 'listing.manage', 'cancelled' => 'credit.approve', 'expired' => null],
        'listing_prepared' => ['published' => 'listing.manage', 'cancelled' => 'listing.manage'],
        'published' => ['funding' => null, 'expired' => null, 'suspended' => 'listing.manage', 'cancelled' => 'listing.manage'],
        'funding' => ['funded' => null, 'expired' => null, 'suspended' => 'listing.manage'],
        'funded' => ['repayment_active' => null],
        'repayment_active' => ['completed' => null, 'overdue' => null, 'restructured' => 'credit.approve', 'defaulted' => 'credit.approve'],
        'overdue' => ['repayment_active' => null, 'restructured' => 'credit.approve', 'defaulted' => 'credit.approve', 'completed' => null],
        'restructured' => ['repayment_active' => null, 'defaulted' => 'credit.approve', 'completed' => null],
        'suspended' => ['published' => 'listing.manage', 'cancelled' => 'listing.manage'],
    ];

    public function canTransition(Application $app, string $to): bool
    {
        return array_key_exists($to, self::MAP[$app->status] ?? []);
    }

    public function transition(Application $app, string $to, ?User $actor, ?string $reason = null, array $meta = []): Application
    {
        $from = $app->status;
        if (! $this->canTransition($app, $to)) {
            throw ValidationException::withMessages(['status' => "An application cannot move from {$from} to {$to}."]);
        }
        $ability = self::MAP[$from][$to];
        if ($ability !== null) {
            if (! $actor) {
                throw ValidationException::withMessages(['status' => 'This step needs an authorised person.']);
            }
            $ok = $ability === 'applicant' ? $this->isApplicant($app, $actor) : $actor->role->can($ability);
            if (! $ok) {
                abort(403, 'You are not allowed to make this change.');
            }
        }
        $app->forceFill(['status' => $to])->save();
        app(AuditLogger::class)->record('application.status', $app, ['from' => $from, 'to' => $to, 'reason' => $reason] + $meta, $actor);
        $this->notify($app, $to);

        return $app;
    }

    /** New work goes to the credit analysts; progress goes to the applicant and the rep who helped them. */
    private function notify(Application $app, string $to): void
    {
        $b = $app->business;
        $amount = \App\Support\Money::format($app->amount);
        if ($to === 'submitted') {
            $analysts = User::query()->where('role', 'credit_analyst')->where('status', 'active')->get();
            \Illuminate\Support\Facades\Notification::send($analysts, new \App\Notifications\ApplicationNotice("New application: {$b->display_name}", "{$amount} over {$app->term_months} months is waiting for credit review.", route('credit.show', $app->id), 'credit'));

            return;
        }
        $messages = [
            'info_requested' => ['More information needed', 'Credit staff asked for more detail on your application.'],
            'approved' => ['Application approved', 'Your application passed credit review. The team is preparing your listing.'],
            'rejected' => ['Application not approved', 'Your application was not approved. Open it to see the reason.'],
            'published' => ['Your listing is live', 'Verified investors can now see your opportunity.'],
            'funded' => ['Funding target reached', 'Your listing is fully funded.'],
        ];
        if (! isset($messages[$to])) {
            return;
        }
        $recipients = collect([$b->owner, $app->assisted_by ? User::find($app->assisted_by) : null])->filter()->unique('id');
        \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\ApplicationNotice($messages[$to][0], $messages[$to][1], route('applications.show', $app->id)));
        if ($b->owner) {
            app(\App\Services\Sms\SmsService::class)->send($b->owner, 'application_update', config('banyira.brand').': '.$messages[$to][0].'. Open the app for details.');
        }
    }

    public function isApplicant(Application $app, User $user): bool
    {
        return $app->business->owner_id === $user->id || ($user->role->can('sales.workspace') && $app->assisted_by === $user->id);
    }

    /** Draft completeness: required fields and required documents. */
    public function submissionProblems(Application $app): array
    {
        $problems = [];
        foreach (['amount' => 'Amount requested', 'purpose' => 'Use of funds', 'term_months' => 'Proposed term', 'monthly_revenue' => 'Monthly revenue'] as $f => $label) {
            if (empty($app->{$f})) {
                $problems[] = "{$label} is required.";
            }
        }
        $have = $app->documents()->pluck('type')->all();
        foreach (config('banyira.documents.required') as $type) {
            if (! in_array($type, $have, true)) {
                $problems[] = 'Missing document: '.str_replace('_', ' ', $type).'.';
            }
        }
        if (! $app->business->owner_consent_at && $app->assisted_by) {
            $problems[] = 'The business owner’s consent to assisted registration has not been recorded.';
        }

        return $problems;
    }

    public function submit(Application $app, User $actor): Application
    {
        $problems = $this->submissionProblems($app);
        if ($problems) {
            throw ValidationException::withMessages(['application' => $problems]);
        }
        $app->forceFill(['submitted_at' => now(), 'info_request' => null])->save();

        return $this->transition($app, 'submitted', $actor);
    }
}
