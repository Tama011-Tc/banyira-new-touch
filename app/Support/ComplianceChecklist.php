<?php

namespace App\Support;

use App\Models\ComplianceCheck;

/** Gates that must ALL be approved, with evidence, before real-money operation can even be requested. */
final class ComplianceChecklist
{
    public const ITEMS = [
        'legal_opinion' => ['Legal opinion on the lending marketplace model', 'Written opinion from qualified Malawian counsel on whether and how a peer-to-peer lending / investment intermediary may operate, and under which licence.'],
        'regulator_engagement' => ['Regulator engagement (Reserve Bank of Malawi and other competent authorities)', 'Confirmation of licensing or exemption, and any conditions, from the relevant regulators.'],
        'payment_provider' => ['Authorised payment provider and settlement arrangement', 'Signed agreement with a licensed provider, including how investor funds, repayments and fees are held and settled. A payment gateway alone does not authorise lending.'],
        'funds_segregation' => ['Safeguarding of investor funds', 'Approved arrangement keeping investor funds, borrower repayments, platform fees and operating funds separate.'],
        'kyc_aml' => ['KYC / AML / CFT programme', 'Documented identity verification, beneficial-ownership checks, sanctions screening, suspicious-activity escalation and a named compliance officer.'],
        'data_protection' => ['Data protection and retention policy', 'Privacy notice, lawful basis, retention schedule, breach procedure and data-subject request process.'],
        'consumer_disclosures' => ['Consumer and investor disclosures reviewed', 'Risk statements, fee disclosures, interest and late-fee wording, and complaints procedure reviewed by counsel.'],
        'credit_reporting' => ['Credit reporting obligations', 'Position on credit-reference reporting and use of credit-bureau data, if applicable.'],
        'tax' => ['Tax treatment confirmed', 'Tax on platform fees and on investor interest, and reporting duties, confirmed by an adviser.'],
        'security_review' => ['Independent security review', 'Penetration test and remediation of high findings; backup and recovery tested.'],
        'operational_readiness' => ['Operational readiness', 'Staffed credit, finance, support and incident processes; staff MFA enforced.'],
    ];

    public static function seed(): void
    {
        foreach (self::ITEMS as $key => [$label, $description]) {
            ComplianceCheck::firstOrCreate(['key' => $key], ['label' => $label, 'description' => $description]);
        }
    }
}
