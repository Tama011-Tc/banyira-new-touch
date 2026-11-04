<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import Section from '@/Components/Section.vue'
import Btn from '@/Components/Btn.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import FundingSplit from '@/Components/FundingSplit.vue'
import Faq from '@/Components/Faq.vue'
import { mkShort, months } from '@/lib/format'

type Rules = { ceiling: string; fees: { upTo: string | null; rate: string }[]; bands: { class: string; label: string; maxAmount: string | null; maxMonths: number | null }[] }
defineProps<{ products: { slug: string; code: string; name: string; summary: string; min_amount: string; max_amount: string; min_months: number; max_months: number }[]; rules: Rules }>()

const faqs: [string, string][] = [
  ['Is financing guaranteed if I apply?', 'No. Every application is verified and assessed by a Banyira credit officer. Approval does not guarantee funding either, because investors decide which businesses to finance.'],
  ['Who sets the interest rate?', 'Investors propose an annual rate, up to a maximum set by Banyira. The business compares offers and accepts only those that suit it.'],
  ['Can several investors fund one business?', 'Yes. Most businesses are funded by several investors, each contributing part of the amount. Each investor has their own agreed rate and repayment schedule.'],
  ['What does a risk rating mean?', 'Ratings from A+ to C are Banyira\'s assessment of a business at the time it applied. They are not a promise that the business will repay.'],
  ['Can an investor lose money?', 'Yes. Repayments can be late, and investors can lose some or all of their capital. Invest only money you can afford to lose and spread it across businesses.'],
  ['What does Banyira charge?', 'Businesses pay a service fee based on the amount financed, shown before they accept any offer. Banyira does not take a share of investor interest.'],
]
const how: [string, string][] = [
  ['Apply', 'A business applies online with its records and documents.'],
  ['Assess', 'A Banyira credit officer verifies the business and assigns a risk class.'],
  ['List', 'Approved businesses are published to investors on the marketplace.'],
  ['Offer', 'Investors offer part of the amount at a rate and term they choose.'],
  ['Choose', 'The business compares offers and accepts the ones that suit it.'],
  ['Repay', 'Banyira manages agreements, disbursement and monthly repayments.'],
]
const industries = ['Agriculture', 'Manufacturing', 'Construction', 'Mining', 'Tourism', 'Transportation', 'Retail', 'Other commercial industries']
const example = [{ who: 'Investor A', amount: 1_000_000, rate: '21%' }, { who: 'Investor B', amount: 2_000_000, rate: '24%' }, { who: 'Investor C', amount: 2_000_000, rate: '23%' }]
</script>

<template>
  <Head title="Business financing and SME investment in Malawi" />
  <PublicLayout>
    <!-- Hero -->
    <section class="mx-auto grid max-w-6xl items-end gap-12 px-4 pb-20 pt-14 sm:px-6 md:grid-cols-[1.25fr_1fr] md:pb-28 md:pt-24">
      <div>
        <h1 class="text-[38px] uppercase leading-[1.02] tracking-[-0.03em] sm:text-[48px] md:text-[60px]">
          <span class="line-mask"><span style="--d: 0ms">Capital for</span></span>
          <span class="line-mask"><span style="--d: 70ms">businesses.</span></span>
          <span class="line-mask"><span class="text-forest" style="--d: 140ms">Opportunities</span></span>
          <span class="line-mask"><span class="text-forest" style="--d: 210ms">for investors.</span></span>
        </h1>
        <p class="rise-in mt-7 max-w-lg text-[17px] text-muted" style="--d: 360ms">Banyira connects ambitious businesses seeking financing with investors looking for opportunities to support real businesses and participate in their growth.</p>
        <div class="rise-in mt-9 flex flex-wrap gap-3" style="--d: 440ms">
          <Btn size="lg" href="/register/business">Find Business Financing</Btn>
          <Btn size="lg" variant="outline" href="/register/investor">Explore Investment Opportunities</Btn>
        </div>
      </div>

      <figure class="rise-in rounded-xl bg-white p-6 shadow-[0_1px_3px_rgb(23_25_26/0.08),0_16px_40px_-16px_rgb(23_25_26/0.18)]" style="--d: 260ms" aria-labelledby="ex-cap">
        <div class="mb-1 flex items-center justify-between">
          <p class="text-sm font-medium">Equipment financing · 18 months</p>
          <RiskBadge r="B" />
        </div>
        <FundingSplit :total="5_000_000" :parts="example" :delay="900" />
        <figcaption id="ex-cap" class="mt-5 border-t border-line pt-3 text-xs text-muted">Illustration. One request, three investors, each with their own rate and repayment schedule.</figcaption>
      </figure>
    </section>

    <Section title="A marketplace between businesses that need capital and people who want to invest in them.">
      <dl class="grid gap-8 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
        <div><dt class="font-display text-[17px] font-medium">For businesses</dt><dd class="mt-2 text-muted">Apply once, get assessed by our credit team, and receive offers from several investors. Choose the terms that work for you.</dd></div>
        <div><dt class="font-display text-[17px] font-medium">For investors</dt><dd class="mt-2 text-muted">Review verified Malawian SMEs, decide how much to offer and at what rate, and track every repayment.</dd></div>
        <div><dt class="font-display text-[17px] font-medium">Banyira's role</dt><dd class="mt-2 text-muted">We verify, assess and list businesses, prepare agreements, handle repayments and keep records. Investors provide the capital, not Banyira.</dd></div>
      </dl>
    </Section>

    <Section title="Six steps from application to repayment." tone="white">
      <ol class="grid gap-x-8 gap-y-7 sm:grid-cols-2">
        <li v-for="([t, d], i) in how" :key="t" class="grid grid-cols-[2rem_1fr] border-t border-line pt-4">
          <span class="num pt-px text-[13px] text-muted">{{ i + 1 }}</span>
          <div><h3 class="text-[17px]">{{ t }}</h3><p class="mt-1 text-sm text-muted">{{ d }}</p></div>
        </li>
      </ol>
    </Section>

    <Section title="Financing on terms you can compare." sub="Offers from several investors instead of one take-it-or-leave-it quote.">
      <ul class="divide-y divide-line border-y border-line text-[15px]">
        <li v-for="t in ['Apply online, from your phone, without queuing at a branch', 'Compare amounts, rates and repayment periods side by side', 'The service fee is shown before you accept anything', 'Accept partial funding if you choose', 'Track applications, agreements and repayments in one place']" :key="t" class="py-3.5">{{ t }}</li>
      </ul>
      <div class="mt-6 flex gap-6 text-sm"><Link href="/how-to-get-a-loan" class="link">How to get a loan</Link><Link href="/products" class="link text-muted">Our products</Link></div>
    </Section>

    <Section title="Back real businesses, with the facts in front of you." sub="Every listed business has been verified and assessed by a person." tone="white">
      <ul class="divide-y divide-line border-y border-line text-[15px]">
        <li v-for="t in ['Choose the amount, the annual rate and the repayment period', 'Spread smaller amounts across several businesses', 'Principal and interest are reported separately', 'Ask businesses questions through secure messaging', 'Repayment schedules and statements for every investment']" :key="t" class="py-3.5">{{ t }}</li>
      </ul>
      <div class="mt-6 text-sm"><Link href="/how-to-invest" class="link">How to invest</Link></div>
    </Section>

    <Section title="Five financing products.">
      <div class="divide-y divide-line border-y border-line">
        <Link v-for="p in products" :key="p.slug" :href="`/products/${p.slug}`" class="group grid gap-1 py-4 sm:grid-cols-[1fr_auto] sm:items-center sm:gap-6">
          <span>
            <span class="font-medium transition-colors duration-150 group-hover:text-forest">{{ p.name }}</span>
            <span class="block text-sm text-muted">{{ p.summary }}</span>
          </span>
          <span class="flex items-center gap-3 text-[13px] text-muted">
            <span class="num">{{ mkShort(p.min_amount) }}–{{ mkShort(p.max_amount) }} · {{ p.min_months }}–{{ months(p.max_months) }}</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="nudge hidden sm:block"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
          </span>
        </Link>
      </div>
    </Section>

    <Section title="Built for the sectors that drive Malawi's economy." tone="white">
      <ul class="grid grid-cols-2 gap-x-8 text-[15px]">
        <li v-for="i in industries" :key="i" class="border-t border-line py-3">{{ i }}</li>
      </ul>
    </Section>

    <Section title="Every business is reviewed by a person, against published criteria.">
      <ol class="space-y-3 text-[15px]">
        <li v-for="(t, i) in ['Identity and business registration are verified', 'Bank and mobile-money records are reviewed', 'Repayment capacity is scored against weighted criteria', 'A risk class is assigned and approved by a second person', 'Only then is the business published to investors']" :key="t" class="grid grid-cols-[2rem_1fr]">
          <span class="num pt-px text-[13px] text-muted">{{ i + 1 }}</span>{{ t }}
        </li>
      </ol>
      <table class="mt-10 w-full text-sm">
        <caption class="sr-only">Risk classes and limits</caption>
        <thead class="text-left text-xs text-muted"><tr><th class="pb-2 font-medium">Class</th><th class="pb-2 font-medium">Meaning</th><th class="pb-2 text-right font-medium">Limits</th></tr></thead>
        <tbody>
          <tr v-for="b in rules.bands" :key="b.class" class="border-t border-line">
            <td class="py-2.5"><RiskBadge :r="b.class" /></td>
            <td class="py-2.5">{{ b.label }}</td>
            <td class="num py-2.5 text-right text-muted">{{ [b.maxAmount && 'max ' + mkShort(b.maxAmount), b.maxMonths && months(b.maxMonths)].filter(Boolean).join(', ') || 'None' }}</td>
          </tr>
        </tbody>
      </table>
      <p class="mt-3 text-xs text-muted">A rating is an assessment, not a guarantee of repayment.</p>
    </Section>

    <Section id="risk" title="Read this before you invest." tone="white">
      <div class="max-w-2xl space-y-3 rounded-xl border border-clay/25 bg-clay-soft p-6 text-[15px] text-[#5e2611]">
        <p>Investing through Banyira involves financial risk. Investors may experience delayed repayments or lose some or all of their invested capital.</p>
        <p>Banyira's credit assessments and risk classifications are intended to support informed investment decisions. They do not guarantee repayment or returns.</p>
        <p>Investors are responsible for evaluating opportunities and making their own investment decisions. Investments are not guaranteed, insured or risk-free.</p>
      </div>
    </Section>

    <Section title="Frequently asked questions."><Faq :items="faqs" /></Section>

    <section class="border-t border-line bg-forest text-white">
      <div class="mx-auto grid max-w-6xl md:grid-cols-2">
        <div class="px-4 py-16 sm:px-6 md:border-r md:border-white/15 md:py-24">
          <h2 class="text-[28px]">Ready to finance your business?</h2>
          <p class="mt-3 max-w-sm text-white/75">Registration takes about ten minutes. You will need your registration certificate and recent statements.</p>
          <div class="mt-7"><Btn href="/register/business" variant="light">Get started with a loan</Btn></div>
        </div>
        <div class="border-t border-white/15 px-4 py-16 sm:px-6 md:border-t-0 md:py-24 md:pl-12">
          <h2 class="text-[28px]">Ready to explore opportunities?</h2>
          <p class="mt-3 max-w-sm text-white/75">Open an account, verify your identity, then browse assessed businesses.</p>
          <div class="mt-7"><Btn href="/register/investor" variant="ghost-light">Get started with investing</Btn></div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
@media (hover: hover) and (pointer: fine) {
  .nudge { transition: transform 200ms var(--ease-out); }
  .group:hover .nudge { transform: translateX(3px); }
}
</style>
