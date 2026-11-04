<?php

namespace Database\Seeders;

use App\Models\AssessmentCriteriaSet;
use App\Models\LedgerAccount;
use App\Models\LoanProduct;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RuleSet;
use Illuminate\Database\Seeder;

/** Reference data the platform needs to run. Safe to re-run. */
class ReferenceDataSeeder extends Seeder
{
    public const PERMISSIONS = [
        'Applications' => [
            'applications.view' => 'View financing applications',
            'applications.assess' => 'Review and assess applications',
            'applications.approve' => 'Approve or reject assessment recommendations',
            'listings.publish' => 'Publish approved businesses to the marketplace',
            'customers.verify' => 'Verify customer identity and documents',
        ],
        'Configuration' => [
            'products.manage' => 'Edit financing products',
            'rules.propose' => 'Propose financing rule changes',
            'rules.approve' => 'Approve financing rule changes',
        ],
        'Administration' => [
            'users.manage' => 'Manage employee accounts',
            'roles.manage' => 'Manage roles and permissions',
            'security.view' => 'View security and login activity',
            'audit.view' => 'View audit logs',
            'system.manage' => 'Manage system settings and jobs',
        ],
        'Sales and marketing' => [
            'leads.manage' => 'Manage leads and follow-ups',
            'campaigns.manage' => 'Manage campaigns',
            'marketing.reports' => 'View acquisition reports',
        ],
        'Finance' => [
            'finance.view' => 'View financial records and reports',
            'finance.record' => 'Prepare financial transactions (maker)',
            'finance.approve' => 'Approve financial transactions (checker)',
            'payments.reconcile' => 'Reconcile and confirm payments',
            'payroll.view' => 'View payroll',
            'payroll.manage' => 'Prepare payroll',
        ],
        'Executive' => [
            'executive.dashboard' => 'View executive dashboard',
            'reports.view' => 'View management reports',
        ],
    ];

    public const ROLES = [
        'credit_officer' => ['Credit Assessment Officer', 'Reviews applications and recommends decisions.', ['applications.view', 'applications.assess', 'customers.verify']],
        'it_admin' => ['IT Administrator', 'Runs the platform. No financial approval powers.', ['users.manage', 'roles.manage', 'security.view', 'audit.view', 'system.manage', 'products.manage', 'rules.propose']],
        'sales_marketing' => ['Sales and Marketing Specialist', 'Grows the marketplace.', ['leads.manage', 'campaigns.manage', 'marketing.reports']],
        'finance_officer' => ['Finance Officer', 'Banyira accounts, client funds, reconciliation and payroll.', ['finance.view', 'finance.record', 'finance.approve', 'payments.reconcile', 'payroll.view', 'payroll.manage']],
        'executive' => ['Executive Management', 'Oversight and approvals. Does not process transactions.', ['executive.dashboard', 'reports.view', 'applications.view', 'applications.approve', 'listings.publish', 'rules.approve', 'finance.view', 'payroll.view', 'audit.view', 'marketing.reports']],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $group => $items) {
            foreach ($items as $slug => $name) {
                Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'group' => $group]);
            }
        }
        foreach (self::ROLES as $slug => [$name, $desc, $perms]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $desc]);
            $role->permissions()->sync(Permission::whereIn('slug', $perms)->pluck('id'));
        }

        $this->ledger();
        $this->products();
        $this->rules();
        $this->criteria();
    }

    private function ledger(): void
    {
        $accounts = [
            ['1010', 'Operating bank account', 'asset', 'banyira_cash', false],
            ['1020', 'Client funds bank account', 'asset', 'client_cash', true],
            ['1210', 'Borrower principal outstanding (held for investors)', 'asset', 'borrower_obligations', true],
            ['2010', 'Investor capital awaiting disbursement', 'liability', 'investor_capital', true],
            ['2020', 'Principal repayments due to investors', 'liability', 'principal_repayments', true],
            ['2030', 'Interest repayments due to investors', 'liability', 'interest_repayments', true],
            ['2040', 'Unallocated client receipts', 'liability', 'client_cash', true],
            ['2110', 'Investor principal claims on borrowers', 'liability', 'investor_capital', true],
            ['2210', 'Payroll deductions payable', 'liability', 'operating_expenses', false],
            ['3010', 'Owner equity', 'equity', 'banyira_cash', false],
            ['4010', 'Service fee revenue', 'revenue', 'service_charges', false],
            ['4090', 'Other operating revenue', 'revenue', 'banyira_revenue', false],
            ['5010', 'Salaries and payroll', 'expense', 'operating_expenses', false],
            ['5020', 'Premises', 'expense', 'operating_expenses', false],
            ['5030', 'Technology and SMS', 'expense', 'operating_expenses', false],
            ['5040', 'Marketing', 'expense', 'operating_expenses', false],
            ['5090', 'Other operating expenses', 'expense', 'operating_expenses', false],
        ];
        foreach ($accounts as [$code, $name, $type, $cat, $client]) {
            LedgerAccount::updateOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'category' => $cat, 'is_client_money' => $client]);
        }
    }

    private function products(): void
    {
        $common = ['Business registration certificate or proof of trading', 'National ID of each owner', 'Six months of bank or mobile money statements', 'Proof of business address'];
        $rows = [
            ['short-term', 'A', 'Short-Term Business Financing', 'Quick capital for temporary needs and cash-flow gaps.',
                'Short-term financing for businesses that need capital quickly to cover a temporary need: a delayed customer payment, a seasonal gap, an urgent stock purchase or an unexpected operating cost.',
                'Trading businesses with steady sales that need a short bridge rather than long-term borrowing.',
                ['Bridging a delayed customer payment', 'Seasonal cash-flow gaps', 'Urgent stock purchases', 'Immediate operating requirements'],
                ['Operating for at least 6 months', 'Registered or formally recognised business', 'Sales that can be verified through bank or mobile money statements'],
                $common, null, 'Monthly instalments of principal and interest. Repayment periods are short, so instalments are larger relative to the amount.', '300000', '3000000', 1, 12],
            ['working-capital', 'B', 'Working Capital Financing', 'Inventory, suppliers and day-to-day operations.',
                'Financing for the everyday running of an SME: buying inventory, paying suppliers and meeting operating expenses between sales cycles.',
                'SMEs with consistent turnover whose operating costs come before their sales income.',
                ['Inventory and stock', 'Supplier payments', 'Wages and operating expenses', 'Day-to-day operations'],
                ['Operating for at least 12 months', 'Consistent monthly turnover', 'No unresolved defaults'],
                [...$common, 'Stock or sales records'], null, 'Monthly instalments over 3 to 18 months.', '500000', '5000000', 3, 18],
            ['expansion', 'C', 'Business Expansion Financing', 'New branches, more production, new markets.',
                'For established SMEs that are ready to grow: opening a branch, increasing production capacity, expanding operations or entering a new market.',
                'Established businesses with a clear, costed expansion plan.',
                ['Opening a new branch', 'Increasing production', 'Expanding operations', 'Entering new markets'],
                ['Operating for at least 18 months', 'A written expansion plan and budget', 'Financial statements or management accounts'],
                [...$common, 'Expansion plan and budget', 'Management accounts'], null, 'Monthly instalments over 12 to 36 months.', '2000000', '15000000', 12, 36],
            ['long-term', 'D', 'Long-Term Business Financing', 'Larger amounts over longer periods.',
                'Larger financing amounts with longer repayment periods for significant commercial investments.',
                'Mature businesses with multi-year records and the capacity to repay over several years.',
                ['Significant commercial investments', 'Property improvements', 'Large contracts', 'Refinancing costly short-term debt'],
                ['Operating for at least 24 months', 'Reviewed or audited accounts preferred', 'Demonstrated multi-year repayment capacity'],
                [...$common, 'Annual financial statements', 'Tax compliance documents'], null, 'Monthly instalments over 24 to 48 months. Longer periods mean smaller instalments but more interest overall.', '5000000', '30000000', 24, 48],
            ['equipment', 'E', 'Equipment and Machinery Financing', 'Machinery, vehicles and productive assets.',
                'Financing to buy heavy machinery, vehicles, productive equipment and other commercial assets that directly generate income.',
                'SMEs in productive sectors that need an asset to grow output or reduce costs.',
                ['Heavy machinery', 'Commercial vehicles', 'Processing and production equipment', 'Other commercial assets'],
                ['Operating for at least 12 months', 'A supplier quotation for the asset', 'The asset is directly productive for the business'],
                [...$common, 'Supplier quotation or pro-forma invoice', 'Details of the asset and its expected use'],
                ['Agriculture', 'Manufacturing', 'Construction', 'Mining', 'Tourism', 'Transportation', 'Retail', 'Other commercial industries'],
                'Monthly instalments over 12 to 48 months, matched to the working life of the asset.', '1000000', '25000000', 12, 48],
        ];
        foreach ($rows as $i => [$slug, $code, $name, $summary, $desc, $aud, $purposes, $elig, $req, $sectors, $repay, $min, $max, $minM, $maxM]) {
            LoanProduct::updateOrCreate(['slug' => $slug], [
                'code' => $code, 'name' => $name, 'summary' => $summary, 'description' => $desc, 'audience' => $aud,
                'purposes' => $purposes, 'eligibility' => $elig, 'requirements' => $req, 'sectors' => $sectors,
                'repayment_info' => $repay, 'min_amount' => $min, 'max_amount' => $max, 'min_months' => $minM, 'max_months' => $maxM,
                'is_active' => true, 'sort_order' => $i,
            ]);
        }
    }

    private function rules(): void
    {
        if (RuleSet::exists()) {
            return;
        }
        $set = RuleSet::create([
            'version' => 'R-2025.1', 'effective_from' => '2025-01-01', 'status' => 'active', 'interest_ceiling' => '36.00',
            'fee_basis' => 'principal', 'fee_collection' => 'deducted_at_disbursement', 'notes' => 'Established Banyira demonstration rules',
        ]);
        foreach ([['1000000', '15'], ['5000000', '10'], [null, '4']] as $i => [$bound, $rate]) {
            $set->feeTiers()->create(['upper_bound' => $bound, 'rate' => $rate, 'sort_order' => $i]);
        }
        foreach ([['A+', 'Very low risk', 85, null, null], ['A', 'Low risk', 70, null, null], ['B', 'Moderate risk', 55, null, 24], ['C', 'Average risk', 40, '3000000', 12]] as [$c, $l, $min, $amt, $mo]) {
            $set->riskBands()->create(['risk_class' => $c, 'label' => $l, 'min_score' => $min, 'max_amount' => $amt, 'max_months' => $mo]);
        }
    }

    private function criteria(): void
    {
        if (AssessmentCriteriaSet::exists()) {
            return;
        }
        $set = AssessmentCriteriaSet::create(['version' => 'C-2025.1', 'status' => 'active']);
        $items = [
            ['cash_flow', 'Cash flow and turnover', 30, 'Are average monthly inflows sufficient and stable? 5 = inflows comfortably exceed outflows plus the proposed instalment every month.'],
            ['repayment_capacity', 'Repayment capacity', 25, 'Debt service cover after the new instalment. 5 = cover of 2.0x or more; 1 = below 1.0x.'],
            ['stability', 'Business stability', 20, 'Years trading, customer concentration and seasonality. 5 = over 5 years with a diverse customer base.'],
            ['purpose', 'Financing purpose', 15, 'Does the purpose directly generate income, and is the amount justified by quotations or plans?'],
            ['management', 'Ownership and management', 10, 'Verified ownership, record-keeping quality and management experience.'],
        ];
        foreach ($items as $i => [$key, $label, $w, $g]) {
            $set->criteria()->create(['key' => $key, 'label' => $label, 'weight' => $w, 'guidance' => $g, 'sort_order' => $i]);
        }
    }
}
