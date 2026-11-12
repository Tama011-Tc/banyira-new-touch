<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageTop from '@/Components/PageTop.vue'
import Journey from '@/Components/Journey.vue'
import Btn from '@/Components/Btn.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import FundingSplit from '@/Components/FundingSplit.vue'
import { mk, mkShort, months } from '@/lib/format'

defineProps<{ rules: any; minOffer: string }>()

const phases: { title: string; steps: [string, string][] }[] = [
  { title: 'Open an account', steps: [
    ['Investor registration', 'Sign up with your name, email and mobile number, and confirm that you understand the risks.'],
    ['Identity verification', 'Give your national ID, source of funds and payout account. Our team verifies them before you can make offers.'],
  ] },
  { title: 'Choose', steps: [
    ['Browse business opportunities', 'Filter the marketplace by industry, region, purpose, risk class, amount and repayment period.'],
    ['Review business information', 'Read what the business does, why it needs the money, how much it has raised and how long the listing is open.'],
    ['Understand credit risk ratings', 'Each business has a class from A+ to C, given by a Banyira credit officer and approved by a second person.'],
    ['Evaluate the opportunity', 'Decide whether the purpose, the risk and the period suit you, and how much of your capital to commit.'],
  ] },
  { title: 'Invest', steps: [
    ['Submit an investment offer', 'Offer an amount, an annual rate up to the ceiling and a repayment period. You can withdraw a pending offer.'],
    ['Communicate with the business', 'Ask questions through secure messaging. Contact details are removed so all dealings stay on the platform.'],
    ['Complete agreements', 'If your offer is accepted, sign the agreement and transfer your funds using the instructions provided.'],
  ] },
  { title: 'Track', steps: [
    ['Monitor repayments', 'See every instalment due, what was paid, and how much was principal and how much was interest.'],
    ['Manage your portfolio', 'Review outstanding principal, repayments received and how your capital is spread across classes and industries.'],
  ] },
]
const parts = [{ who: 'Investor A', amount: 1_000_000, rate: '21%' }, { who: 'Investor B', amount: 2_000_000, rate: '24%' }, { who: 'Investor C', amount: 2_000_000, rate: '23%' }]
</script>

<template>
  <PublicLayout>
    <PageTop title="How to invest" sub="Support verified Malawian businesses with an amount, rate and period you choose, and follow every repayment." />

    <div class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
      <section class="mb-14 rounded-xl border border-clay/30 bg-clay-soft p-6 md:p-8" aria-labelledby="risk-h">
        <h2 id="risk-h" class="text-[19px] text-orange-text">Investment risk disclosure</h2>
        <div class="mt-3 max-w-3xl space-y-3 text-[15px] text-orange-text">
          <p>Investing through Banyira involves financial risk.</p>
          <p>Investors may experience delayed repayments or lose some or all of their invested capital.</p>
          <p>Banyira's credit assessments and risk classifications are intended to support informed investment decisions. They do not guarantee repayment or returns.</p>
          <p>Investors are responsible for evaluating opportunities and making their own investment decisions.</p>
        </div>
      </section>

      <Journey :phases="phases" />

      <section class="mt-16 grid gap-4 border-t border-line pt-10 md:grid-cols-[200px_1fr] md:gap-10">
        <h2 class="text-[17px]">Shared funding</h2>
        <div>
          <h3 class="text-[19px]">How several investors finance one business</h3>
          <p class="mt-2 max-w-2xl text-muted">A business requesting MK5,000,000 rarely gets it from one person. Each investor offers part of the amount. The business accepts offers until the total is reached, and the system never lets accepted offers exceed what was requested.</p>
          <div class="mt-8 max-w-xl rounded-xl border border-separator bg-surface p-6">
            <FundingSplit :total="5_000_000" :parts="parts" />
            <p class="mt-4 border-t border-line pt-3 text-xs text-muted">Illustration only. Each investor has a separate repayment schedule at their own agreed rate.</p>
          </div>
        </div>
      </section>

      <section class="mt-16 grid gap-4 border-t border-line pt-10 md:grid-cols-[200px_1fr] md:gap-10">
        <h2 class="text-[17px]">Limits</h2>
        <dl class="grid max-w-2xl grid-cols-2 gap-6 sm:grid-cols-3">
          <div><dt class="text-[13px] text-muted">Minimum offer</dt><dd class="num mt-0.5 font-medium">{{ mk(minOffer) }}</dd></div>
          <div><dt class="text-[13px] text-muted">Maximum annual rate</dt><dd class="num mt-0.5 font-medium">{{ Number(rules.ceiling) }}%</dd></div>
          <div v-for="b in rules.bands.filter((x: any) => x.maxMonths)" :key="b.class"><dt class="text-[13px] text-muted">Longest period, class <RiskBadge :r="b.class" /></dt><dd class="num mt-0.5 font-medium">{{ months(b.maxMonths) }}</dd></div>
        </dl>
      </section>
    </div>

    <section class="border-t border-line bg-surface">
      <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-20">
        <h2 class="text-[28px] uppercase tracking-tight md:text-[36px]">Ready to explore investment opportunities?</h2>
        <div class="mt-6"><Btn size="lg" href="/register/investor">GET STARTED WITH INVESTING</Btn></div>
      </div>
    </section>
  </PublicLayout>
</template>
