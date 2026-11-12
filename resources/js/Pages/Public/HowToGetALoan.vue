<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageTop from '@/Components/PageTop.vue'
import Journey from '@/Components/Journey.vue'
import FeeTable from '@/Components/FeeTable.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'
import Faq from '@/Components/Faq.vue'

defineProps<{ rules: any }>()

const phases: { title: string; steps: [string, string][] }[] = [
  { title: 'Get set up', steps: [
    ['Create a business account', 'Register with your business name, email and mobile number. We confirm your phone by SMS.'],
    ['Complete business verification', 'Give your registration number and owner ID. Our team checks them, usually within two working days.'],
    ['Provide business information', 'Tell us where you operate, how long you have traded, your staff and your monthly turnover.'],
  ] },
  { title: 'Apply', steps: [
    ['Submit financial records', 'Enter monthly money in and money out for recent months, from your bank or mobile-money statements.'],
    ['Upload supporting documents', 'Clear photos or PDFs: registration certificate, statements, quotations for anything you will buy.'],
    ['Select a financing product', 'Choose the product that fits how you will use the funds. Each has its own limits and periods.'],
    ['Submit the application', 'Check everything, then submit. You can save a draft and come back at any time before this.'],
  ] },
  { title: 'Assessment', steps: [
    ['Credit assessment', 'A Banyira credit officer reviews your records. They may ask for more information, which you answer in your dashboard.'],
    ['Risk classification', 'Your business is scored against published criteria and given a class from A+ to C. A second person approves the decision.'],
    ['Approval and marketplace publication', 'If approved, a summary of your business and request is shown to investors. Your registration details and contacts stay private.'],
  ] },
  { title: 'Funding', steps: [
    ['Receive investor offers', 'Investors offer part or all of the amount, each with their own annual rate and repayment period.'],
    ['Compare offers', 'See amounts, rates, periods and the estimated monthly cost of each offer side by side.'],
    ['Accept suitable offers', 'Accept the offers that work for you and reject the rest. You can proceed with partial funding if you wish.'],
    ['Complete financing agreements', 'Review and sign the agreement in your dashboard. The investors sign too.'],
  ] },
  { title: 'After funding', steps: [
    ['Disbursement', 'When investor funds are confirmed, the money is released to your account, less the service fee.'],
    ['Repayment management', 'Pay monthly by mobile money or bank. You get SMS reminders, and every payment appears in your statement.'],
  ] },
]
const faq: [string, string][] = [
  ['How long does it take?', 'Verification usually takes two working days and assessment about a week. Funding depends on investors and typically takes one to four weeks once you are listed.'],
  ['What if I only get part of the amount?', 'You can wait for more offers until the listing closes, or close funding early and proceed with the amount you have accepted.'],
  ['When is the service fee paid?', 'It is calculated on the total amount financed and deducted when the funds are released, so you never pay it upfront.'],
  ['What happens if I miss a payment?', 'Contact us straight away. Missed payments are recorded, affect your future applications and are reported to your investors.'],
  ['Who sees my information?', 'Banyira staff who need it for your assessment. Investors see a summary of your business and request, never your documents, ID or contact details.'],
]
</script>

<template>
  <PublicLayout>
    <PageTop title="How to get a loan" sub="From registration to repayment, here is every step, what you need for it and what happens next." />
    <div class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
      <div class="mb-12 max-w-3xl"><Notice tone="warn" title="Applying is not a guarantee">Submitting an application does not guarantee approval or funding. Approved businesses are funded only if investors make offers that the business accepts.</Notice></div>
      <Journey :phases="phases" />

      <section class="mt-16 grid gap-4 border-t border-line pt-10 md:grid-cols-[200px_1fr] md:gap-10">
        <h2 class="text-[17px]">Fees and limits</h2>
        <FeeTable :rules="rules" />
      </section>

      <section class="mt-16 grid gap-4 border-t border-line pt-10 md:grid-cols-[200px_1fr] md:gap-10">
        <h2 class="text-[17px]">Questions</h2>
        <Faq :items="faq" />
      </section>
    </div>

    <section class="border-t border-line bg-surface">
      <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-20">
        <h2 class="text-[28px] uppercase tracking-tight md:text-[36px]">Ready to finance your business?</h2>
        <div class="mt-6"><Btn size="lg" href="/register/business">GET STARTED WITH A LOAN</Btn></div>
      </div>
    </section>
  </PublicLayout>
</template>
