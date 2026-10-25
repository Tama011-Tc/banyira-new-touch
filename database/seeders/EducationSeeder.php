<?php

namespace Database\Seeders;

use App\Models\EducationArticle;
use Illuminate\Database\Seeder;

/**
 * Starter lessons, in English. General information only: not legal, tax or investment advice.
 * Chichewa titles and bodies are left empty for a qualified translator; English shows until then.
 */
class EducationSeeder extends Seeder
{
    public function run(): void
    {
        $note = "\n\nThis is general information to help you run your business. It is not legal, tax or investment advice.";
        $articles = [
            ['cash-flow-basics', 'cash_flow', 'Know where your cash is going', "Profit and cash are not the same thing. A business can sell a lot and still run out of cash if customers pay late or stock sits on the shelf.\n\nEvery week, write down three numbers: money that came in, money that went out, and money you are waiting to receive. The difference between in and out is your cash flow.\n\nWhat to do:\n- Look ahead four weeks and list the bills you must pay: rent, suppliers, wages, loan instalments.\n- Chase customers who owe you before they owe you for a month.\n- Keep a small cash cushion for slow weeks, even MK 5,000 a week adds up."],
            ['simple-bookkeeping', 'bookkeeping', 'A simple way to keep your books', "Bookkeeping is writing down what comes in and what goes out, every day, in the same place.\n\nYou only need a notebook or a spreadsheet with these columns: date, what it was, money in, money out, and who it was with.\n\nAt the end of each week add up the columns. At the end of each month, compare this month with last month: did sales go up? Which cost grew most?\n\nTip: keep receipts and mobile-money confirmations in an envelope for each month. When a lender asks for records, a neat notebook is far better than none."],
            ['separate-money', 'separate_finances', 'Keep business money and personal money apart', "When business and household money are mixed, you cannot tell whether the business is really making a profit.\n\nOpen a separate account or wallet for the business, even a second mobile-money number. Pay yourself a fixed amount on a fixed day, and treat it like a wage.\n\nIf you take money from the till, write it down as 'owner drawings'. If you put your own money in, write that down too.\n\nWhy it matters: lenders look at the business's own record. Clear separation shows you can manage a loan."],
            ['interest-explained', 'borrowing', 'Flat or reducing interest: what is the difference?', "Interest is the price of borrowing. The same rate can cost very different amounts depending on how it is calculated.\n\nFlat rate: interest is worked out on the original amount for the whole loan, even as you repay it. Borrow MK 120,000 at 24% a year flat for a year and you pay MK 28,800 interest.\n\nReducing balance: interest is worked out on what you still owe, so it falls as you repay. The same loan at 24% reducing costs about MK 16,200.\n\nAlways ask: what is the rate, is it flat or reducing, what fees are added, and what is the total I will pay back? Banyira shows all of this before you accept." . $note],
            ['plan-repayments', 'repayment', 'Plan your repayments before you borrow', "Before you ask for a loan, work out the instalment and check your business can pay it from its own cash, not from new borrowing.\n\nA simple test: your monthly surplus (sales minus all costs) should be comfortably more than the monthly instalment. Lenders often want about one and a half times.\n\nThink about slow seasons. If sales fall by a third for two months, can you still pay?\n\nIf you know you will miss a payment, say so early. Talking to the lender before the due date is always better than staying silent."],
            ['control-expenses', 'expenses', 'Control your expenses', "Small costs add up. List every cost for one month and put each into one of three groups: must pay, could reduce, could stop.\n\nCompare prices from two or three suppliers. Buy fast-selling items in bulk if it lowers the price, but do not tie up cash in stock that sells slowly.\n\nWatch for leaks: airtime, transport, mobile-money fees and unrecorded 'small' withdrawals."],
            ['keep-records', 'records', 'Why records help you get finance', "Credit teams ask for records because they show whether a business can repay.\n\nUseful records: a sales book, a list of what you owe and what you are owed, bank or mobile-money statements, and your registration papers.\n\nKeep them for at least six months before you apply and add to them every week. Never change a record to make the business look better; inconsistent records are the quickest way to lose trust."],
            ['borrow-responsibly', 'borrowing', 'Using finance responsibly', "A loan should help the business earn more than it costs. Use it for what you said you would: equipment, stock for a known order, or a clear expansion.\n\nDo not borrow to pay another loan, and do not borrow more than you can repay in a bad month.\n\nBefore you accept any offer, read the repayment schedule and the fees. Banyira investors lend their own money and returns are not guaranteed. Repay on time: it protects your reputation and your chance of borrowing again." . $note],
        ];
        foreach ($articles as $i => [$slug, $cat, $title, $body]) {
            EducationArticle::updateOrCreate(['slug' => $slug], ['category' => $cat, 'title_en' => $title, 'body_en' => $body, 'published' => true, 'position' => $i + 1]);
        }
    }
}
