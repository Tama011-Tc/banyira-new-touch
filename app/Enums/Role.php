<?php

namespace App\Enums;

/** Functional roles. Each user has exactly one; abilities are least-privilege and checked server-side by Gates. */
enum Role: string
{
    case Investor = 'investor';
    case BusinessOwner = 'business_owner';
    case Sales = 'sales';
    case CreditAnalyst = 'credit_analyst';
    case CreditApprover = 'credit_approver';
    case Executive = 'executive';
    case Finance = 'finance';
    case Compliance = 'compliance';
    case ItAdmin = 'it_admin';
    case Support = 'support';

    /** ability => roles. Deliberately absent: no role can edit a decision, a ledger entry or an audit row. */
    public const ABILITIES = [
        'marketplace.view' => ['investor', 'executive'],
        'invest' => ['investor'],
        'business.apply' => ['business_owner'],
        'sales.workspace' => ['sales'],
        'credit.assess' => ['credit_analyst'],
        'credit.approve' => ['credit_approver', 'executive'],
        'credit.view' => ['credit_analyst', 'credit_approver', 'executive', 'compliance'],
        'documents.view_any' => ['credit_analyst', 'credit_approver', 'compliance'],
        'listing.manage' => ['credit_approver', 'executive'],
        'executive.dashboard' => ['executive'],
        'finance.workspace' => ['finance', 'compliance', 'executive'],
        'finance.reconcile' => ['finance'],
        'finance.approve' => ['finance', 'compliance', 'executive'],
        'compliance.workspace' => ['compliance', 'executive'],
        'compliance.golive' => ['executive'],
        'kyc.review' => ['compliance'],
        'admin.users' => ['it_admin'],
        'admin.settings' => ['it_admin'],
        'audit.view' => ['it_admin', 'compliance', 'executive', 'finance'],
        'moderation.review' => ['support', 'compliance'],
        'complaints.handle' => ['support', 'compliance'],
        'agreements.view_any' => ['executive', 'finance', 'compliance'],
    ];

    public function can(string $ability): bool
    {
        return in_array($this->value, self::ABILITIES[$ability] ?? [], true);
    }

    public function isStaff(): bool
    {
        return ! in_array($this, [self::Investor, self::BusinessOwner], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Investor => 'Investor', self::BusinessOwner => 'Business owner', self::Sales => 'Sales representative',
            self::CreditAnalyst => 'Credit analyst', self::CreditApprover => 'Credit approver', self::Executive => 'Executive',
            self::Finance => 'Finance officer', self::Compliance => 'Compliance officer', self::ItAdmin => 'IT administrator', self::Support => 'Support & moderation',
        };
    }
}
