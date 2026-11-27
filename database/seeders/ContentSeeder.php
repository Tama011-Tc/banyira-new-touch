<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Services\Content\ContentValidator;
use Illuminate\Database\Seeder;

/**
 * The built-in public pages. Creates any that are missing and never overwrites
 * one that exists, so editors' changes survive re-seeding and upgrades.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $validator = app(ContentValidator::class);

        foreach ($this->pages() as $slug => $content) {
            if (Page::where('slug', $slug)->exists()) {
                continue;
            }
            $clean = $validator->validate($content, forPublishing: true);
            $page = Page::create(['slug' => $slug, 'is_system' => true, 'draft' => $clean, 'published' => $clean, 'published_revision' => 1, 'published_at' => now()]);
            $page->versions()->create(['revision' => 1, 'content' => $clean]);
        }
    }

    private function pages(): array
    {
        return [
            'home' => $this->page('Business financing and SME investment in Malawi', $this->home()),
            'products' => $this->page('Financing products', $this->products()),
            'how-to-get-a-loan' => $this->page('How to get a loan', $this->loan(), $this->nav(10, 'How to Get a Loan', 'businesses', 'How to get a loan')),
            'how-to-invest' => $this->page('How to invest', $this->invest(), $this->nav(20, 'How to Invest', 'investors', 'How to invest')),
            'about' => $this->page('About us', $this->about(), $this->nav(30, 'About Us', 'company', 'About us')),
            'contact' => $this->page('Contact', $this->contact(), $this->nav(40, 'Contact', 'company', 'Contact')),
            'legal' => $this->page('Terms, privacy and complaints', $this->legal(), ['footer' => 'company', 'footer_label' => 'Terms, privacy and complaints', 'order' => 50]),
        ];
    }

    private function page(string $title, array $blocks, array $nav = []): array
    {
        return ['title' => $title, 'nav' => $nav, 'blocks' => $blocks];
    }

    private function nav(int $order, string $header, string $column, string $footer): array
    {
        return ['header' => true, 'header_label' => $header, 'footer' => $column, 'footer_label' => $footer, 'order' => $order];
    }

    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data];
    }

    /** @param list<array{0: string, 1: string}> $pairs */
    private function pairs(array $pairs, string $first = 'title', string $second = 'text'): array
    {
        return array_map(fn ($p) => [$first => $p[0], $second => $p[1]], $pairs);
    }

    private function titles(array $titles): array
    {
        return array_map(fn ($t) => ['title' => $t], $titles);
    }

    private function home(): array
    {
        return [
            $this->block('hero', [
                'headline' => 'Capital for businesses.',
                'headline_muted' => 'Opportunities for investors.',
                'lead' => 'Banyira connects ambitious businesses seeking financing with investors looking for opportunities to support real businesses and participate in their growth.',
                'buttons' => $this->pairs([['Find Business Financing', '/register/business'], ['Explore Investment Opportunities', '/register/investor']], 'label', 'href'),
                'show_example' => true,
                'example_title' => 'Equipment financing · 18 months',
                'example_caption' => 'Example only, not a real listing. One request, three investors, each with their own rate and repayment schedule.',
            ]),
            $this->block('columns', [
                'heading' => 'A marketplace between businesses that need capital and people who want to invest in them.',
                'items' => $this->pairs([
                    ['For businesses', 'Apply once, get assessed by our credit team, and receive offers from several investors. Choose the terms that work for you.'],
                    ['For investors', 'Review verified Malawian SMEs, decide how much to offer and at what rate, and track every repayment.'],
                    ['Banyira\'s role', 'We verify, assess and list businesses, prepare agreements, handle repayments and keep records. Investors provide the capital, not Banyira.'],
                ]),
            ]),
            $this->block('steps', [
                'heading' => 'Six steps from application to repayment.',
                'shaded' => true,
                'items' => $this->pairs([
                    ['Apply', 'A business applies online with its records and documents.'],
                    ['Assess', 'A Banyira credit officer verifies the business and assigns a risk class.'],
                    ['List', 'Approved businesses are published to investors on the marketplace.'],
                    ['Offer', 'Investors offer part of the amount at a rate and term they choose.'],
                    ['Choose', 'The business compares offers and accepts the ones that suit it.'],
                    ['Repay', 'Banyira manages agreements, disbursement and monthly repayments.'],
                ]),
            ]),
            $this->block('list', [
                'heading' => 'Financing on terms you can compare.',
                'intro' => 'Offers from several investors instead of one take-it-or-leave-it quote.',
                'items' => $this->titles(['Apply online, from your phone, without queuing at a branch', 'Compare amounts, rates and repayment periods side by side', 'The service fee is shown before you accept anything', 'Accept partial funding if you choose', 'Track applications, agreements and repayments in one place']),
                'links' => $this->pairs([['How to get a loan', '/how-to-get-a-loan'], ['Our products', '/products']], 'label', 'href'),
            ]),
            $this->block('list', [
                'heading' => 'Back real businesses, with the facts in front of you.',
                'intro' => 'Every listed business has been verified and assessed by a person.',
                'shaded' => true,
                'items' => $this->titles(['Choose the amount, the annual rate and the repayment period', 'Spread smaller amounts across several businesses', 'Principal and interest are reported separately', 'Ask businesses questions through secure messaging', 'Repayment schedules and statements for every investment']),
                'links' => $this->pairs([['How to invest', '/how-to-invest']], 'label', 'href'),
            ]),
            $this->block('products', ['heading' => 'Five financing products.', 'display' => 'list']),
            $this->block('list', [
                'heading' => 'Built for the sectors that drive Malawi\'s economy.',
                'shaded' => true,
                'columns' => '2',
                'items' => $this->titles(['Agriculture', 'Manufacturing', 'Construction', 'Mining', 'Tourism', 'Transportation', 'Retail', 'Other commercial industries']),
            ]),
            $this->block('review', [
                'heading' => 'Every business is reviewed by a person, against published criteria.',
                'items' => $this->titles(['Identity and business registration are verified', 'Bank and mobile-money records are reviewed', 'Repayment capacity is scored against weighted criteria', 'A risk class is assigned and approved by a second person', 'Only then is the business published to investors']),
                'footnote' => 'A rating is an assessment, not a guarantee of repayment.',
            ]),
            $this->block('features', [
                'heading' => 'How money moves, and how it is protected.',
                'intro' => 'The same controls apply to every listing, whatever its size.',
                'shaded' => true,
                'items' => [
                    ['icon' => 'bank', 'title' => 'Client money is kept apart', 'text' => 'Investor funds and repayments are held in a client account, recorded separately from Banyira\'s own money.'],
                    ['icon' => 'users', 'title' => 'Two people release every payout', 'text' => 'A disbursement is prepared by one finance officer and approved by a second. Nobody can release funds alone.'],
                    ['icon' => 'check', 'title' => 'Repayments are confirmed first', 'text' => 'A repayment only reaches investors once the payment is confirmed as received. Interest is paid first, then principal.'],
                    ['icon' => 'log', 'title' => 'Every change leaves a record', 'text' => 'Money movements are posted to a double-entry ledger that cannot be edited, and sensitive actions are audited.'],
                ],
            ]),
            $this->block('warning', [
                'heading' => 'Read this before you invest.',
                'anchor' => 'risk',
                'body' => "Investing through Banyira involves financial risk. Investors may experience delayed repayments or lose some or all of their invested capital.\n\nBanyira's credit assessments and risk classifications are intended to support informed investment decisions. They do not guarantee repayment or returns.\n\nInvestors are responsible for evaluating opportunities and making their own investment decisions. Investments are not guaranteed, insured or risk-free.",
            ]),
            $this->block('faq', [
                'heading' => 'Frequently asked questions.',
                'shaded' => true,
                'items' => $this->pairs([
                    ['Is financing guaranteed if I apply?', 'No. Every application is verified and assessed by a Banyira credit officer. Approval does not guarantee funding either, because investors decide which businesses to finance.'],
                    ['Who sets the interest rate?', 'Investors propose an annual rate, up to a maximum set by Banyira. The business compares offers and accepts only those that suit it.'],
                    ['Can several investors fund one business?', 'Yes. Most businesses are funded by several investors, each contributing part of the amount. Each investor has their own agreed rate and repayment schedule.'],
                    ['What does a risk rating mean?', 'Ratings from A+ to C are Banyira\'s assessment of a business at the time it applied. They are not a promise that the business will repay.'],
                    ['Can an investor lose money?', 'Yes. Repayments can be late, and investors can lose some or all of their capital. Invest only money you can afford to lose and spread it across businesses.'],
                    ['What does Banyira charge?', 'Businesses pay a service fee based on the amount financed, shown before they accept any offer. Banyira does not take a share of investor interest.'],
                ], 'question', 'answer'),
            ]),
            $this->block('call_to_action', [
                'panels' => [
                    ['title' => 'Ready to finance your business?', 'text' => 'Registration takes about ten minutes. You will need your registration certificate and recent statements.', 'button_label' => 'Get started with a loan', 'button_href' => '/register/business'],
                    ['title' => 'Ready to explore opportunities?', 'text' => 'Open an account, verify your identity, then browse assessed businesses.', 'button_label' => 'Get started with investing', 'button_href' => '/register/investor'],
                ],
            ]),
        ];
    }

    private function products(): array
    {
        return [
            $this->block('page_header', ['title' => 'Financing for different business needs.', 'subtitle' => 'Choose the product that matches how you will use the funds. Final amounts, rates and terms depend on your assessment and on investor offers.']),
            $this->block('products', ['display' => 'cards', 'layout' => 'compact']),
            $this->block('fees', ['heading' => 'What it costs, stated plainly.']),
        ];
    }

    private function loan(): array
    {
        return [
            $this->block('page_header', ['title' => 'How to get a loan', 'subtitle' => 'From registration to repayment, here is every step, what you need for it and what happens next.']),
            $this->block('notice', ['tone' => 'warn', 'title' => 'Applying is not a guarantee', 'body' => 'Submitting an application does not guarantee approval or funding. Approved businesses are funded only if investors make offers that the business accepts.']),
            $this->block('journey', ['phases' => [
                ['title' => 'Get set up', 'steps' => $this->pairs([
                    ['Create a business account', 'Register with your business name, email and mobile number. We confirm your phone by SMS.'],
                    ['Complete business verification', 'Give your registration number and owner ID. Our team checks them, usually within two working days.'],
                    ['Provide business information', 'Tell us where you operate, how long you have traded, your staff and your monthly turnover.'],
                ])],
                ['title' => 'Apply', 'steps' => $this->pairs([
                    ['Submit financial records', 'Enter monthly money in and money out for recent months, from your bank or mobile-money statements.'],
                    ['Upload supporting documents', 'Clear photos or PDFs: registration certificate, statements, quotations for anything you will buy.'],
                    ['Select a financing product', 'Choose the product that fits how you will use the funds. Each has its own limits and periods.'],
                    ['Submit the application', 'Check everything, then submit. You can save a draft and come back at any time before this.'],
                ])],
                ['title' => 'Assessment', 'steps' => $this->pairs([
                    ['Credit assessment', 'A Banyira credit officer reviews your records. They may ask for more information, which you answer in your dashboard.'],
                    ['Risk classification', 'Your business is scored against published criteria and given a class from A+ to C. A second person approves the decision.'],
                    ['Approval and marketplace publication', 'If approved, a summary of your business and request is shown to investors. Your registration details and contacts stay private.'],
                ])],
                ['title' => 'Funding', 'steps' => $this->pairs([
                    ['Receive investor offers', 'Investors offer part or all of the amount, each with their own annual rate and repayment period.'],
                    ['Compare offers', 'See amounts, rates, periods and the estimated monthly cost of each offer side by side.'],
                    ['Accept suitable offers', 'Accept the offers that work for you and reject the rest. You can proceed with partial funding if you wish.'],
                    ['Complete financing agreements', 'Review and sign the agreement in your dashboard. The investors sign too.'],
                ])],
                ['title' => 'After funding', 'steps' => $this->pairs([
                    ['Disbursement', 'When investor funds are confirmed, the money is released to your account, less the service fee.'],
                    ['Repayment management', 'Pay monthly by mobile money or bank. You get SMS reminders, and every payment appears in your statement.'],
                ])],
            ]]),
            $this->block('fees', ['heading' => 'Fees and limits', 'layout' => 'compact']),
            $this->block('faq', ['heading' => 'Questions', 'layout' => 'compact', 'items' => $this->pairs([
                ['How long does it take?', 'Verification usually takes two working days and assessment about a week. Funding depends on investors and typically takes one to four weeks once you are listed.'],
                ['What if I only get part of the amount?', 'You can wait for more offers until the listing closes, or close funding early and proceed with the amount you have accepted.'],
                ['When is the service fee paid?', 'It is calculated on the total amount financed and deducted when the funds are released, so you never pay it upfront.'],
                ['What happens if I miss a payment?', 'Contact us straight away. Missed payments are recorded, affect your future applications and are reported to your investors.'],
                ['Who sees my information?', 'Banyira staff who need it for your assessment. Investors see a summary of your business and request, never your documents, ID or contact details.'],
            ], 'question', 'answer')]),
            $this->block('call_to_action', ['panels' => [['title' => 'Ready to finance your business?', 'button_label' => 'GET STARTED WITH A LOAN', 'button_href' => '/register/business']]]),
        ];
    }

    private function invest(): array
    {
        return [
            $this->block('page_header', ['title' => 'How to invest', 'subtitle' => 'Support verified Malawian businesses with an amount, rate and period you choose, and follow every repayment.']),
            $this->block('warning', [
                'heading' => 'Investment risk disclosure',
                'layout' => 'compact',
                'body' => "Investing through Banyira involves financial risk.\n\nInvestors may experience delayed repayments or lose some or all of their invested capital.\n\nBanyira's credit assessments and risk classifications are intended to support informed investment decisions. They do not guarantee repayment or returns.\n\nInvestors are responsible for evaluating opportunities and making their own investment decisions.",
            ]),
            $this->block('journey', ['phases' => [
                ['title' => 'Open an account', 'steps' => $this->pairs([
                    ['Investor registration', 'Sign up with your name, email and mobile number, and confirm that you understand the risks.'],
                    ['Identity verification', 'Give your national ID, source of funds and payout account. Our team verifies them before you can make offers.'],
                ])],
                ['title' => 'Choose', 'steps' => $this->pairs([
                    ['Browse business opportunities', 'Filter the marketplace by industry, region, purpose, risk class, amount and repayment period.'],
                    ['Review business information', 'Read what the business does, why it needs the money, how much it has raised and how long the listing is open.'],
                    ['Understand credit risk ratings', 'Each business has a class from A+ to C, given by a Banyira credit officer and approved by a second person.'],
                    ['Evaluate the opportunity', 'Decide whether the purpose, the risk and the period suit you, and how much of your capital to commit.'],
                ])],
                ['title' => 'Invest', 'steps' => $this->pairs([
                    ['Submit an investment offer', 'Offer an amount, an annual rate up to the ceiling and a repayment period. You can withdraw a pending offer.'],
                    ['Communicate with the business', 'Ask questions through secure messaging. Contact details are removed so all dealings stay on the platform.'],
                    ['Complete agreements', 'If your offer is accepted, sign the agreement and transfer your funds using the instructions provided.'],
                ])],
                ['title' => 'Track', 'steps' => $this->pairs([
                    ['Monitor repayments', 'See every instalment due, what was paid, and how much was principal and how much was interest.'],
                    ['Manage your portfolio', 'Review outstanding principal, repayments received and how your capital is spread across classes and industries.'],
                ])],
            ]]),
            $this->block('funding_example', [
                'heading' => 'Shared funding',
                'layout' => 'compact',
                'title' => 'How several investors finance one business',
                'body' => 'A business requesting MK5,000,000 rarely gets it from one person. Each investor offers part of the amount. The business accepts offers until the total is reached, and the system never lets accepted offers exceed what was requested.',
                'caption' => 'Illustration only. Each investor has a separate repayment schedule at their own agreed rate.',
            ]),
            $this->block('investment_limits', ['heading' => 'Limits', 'layout' => 'compact']),
            $this->block('call_to_action', ['panels' => [['title' => 'Ready to explore investment opportunities?', 'button_label' => 'GET STARTED WITH INVESTING', 'button_href' => '/register/investor']]]),
        ];
    }

    private function about(): array
    {
        return [
            $this->block('page_header', ['title' => 'Making business finance in Malawi clearer and fairer.', 'subtitle' => 'Banyira is a marketplace operator. We verify and assess businesses, list them for investors, and manage agreements, repayments and records. The capital comes from investors, not from Banyira.']),
            $this->block('columns', ['heading' => 'What we hold ourselves to.', 'items' => $this->pairs([
                ['Transparency', 'Fees, limits, risk classes and offers are shown in plain language before anyone commits.'],
                ['Careful assessment', 'A person reviews every business against published criteria, and a second person approves each decision.'],
                ['Separation of money', 'Investor capital and repayments are held and reported separately from Banyira\'s own revenue.'],
            ])]),
            $this->block('list', ['heading' => 'Five teams, each with a clear responsibility.', 'items' => $this->pairs([
                ['Credit assessment', 'Verifies businesses, scores applications and recommends decisions.'],
                ['Finance', 'Reconciles payments, releases funds with a second approver, and keeps the ledger.'],
                ['Sales and marketing', 'Helps businesses and investors join and understand the platform.'],
                ['Technology', 'Runs and secures the platform. Has no power to approve credit or payments.'],
                ['Executive management', 'Oversight, final approvals and rule changes.'],
            ])]),
        ];
    }

    private function contact(): array
    {
        return [
            $this->block('page_header', ['title' => 'Talk to us.', 'subtitle' => 'Questions about financing, investing or an existing account. We reply within two working days.']),
            $this->block('contact_form', ['items' => $this->pairs([
                ['Office', 'Lilongwe, Malawi'],
                ['Hours', 'Monday to Friday, 08:00–17:00'],
                ['Existing customers', 'Use Help and Support in your dashboard so we can see your account.'],
            ])]),
        ];
    }

    private function legal(): array
    {
        return [
            $this->block('page_header', ['title' => 'Terms, privacy, risk and complaints.']),
            $this->block('notice', ['tone' => 'warn', 'title' => 'Draft for legal review', 'body' => 'These summaries are placeholders. Final terms, the privacy notice and the regulatory position of the marketplace must be reviewed by Malawian legal counsel before Banyira handles real money.']),
            $this->block('documents', ['items' => [
                ['anchor' => 'terms', 'title' => 'Terms of use', 'body' => 'Accounts are for verified businesses and investors only. You must give accurate information, keep your password private and use the platform only for its intended purpose.'],
                ['anchor' => 'privacy', 'title' => 'Privacy', 'body' => 'We collect what we need to verify you, assess applications and manage financing. Documents are stored privately and only staff who need them can see them. Investors never see a business\'s documents or contact details.'],
                ['anchor' => 'risk', 'title' => 'Investment risk', 'body' => 'Investing through Banyira involves financial risk. Investors may experience delayed repayments or lose some or all of their invested capital. Banyira\'s credit assessments and risk classifications do not guarantee repayment or returns. Investments are not guaranteed, insured or risk-free.'],
                ['anchor' => 'complaints', 'title' => 'Complaints', 'body' => 'Raise a complaint through Help and Support in your dashboard or the contact form. We acknowledge it within two working days and aim to resolve it within fifteen.'],
            ]]),
        ];
    }
}
