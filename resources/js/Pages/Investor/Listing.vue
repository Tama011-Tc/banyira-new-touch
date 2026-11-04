<script setup lang="ts">
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Progress from '@/Components/Progress.vue'
import Status from '@/Components/Status.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'
import { date, mk, months } from '@/lib/format'

const props = defineProps<{ listing: any; myOffers: any[]; offerCount: number; limits: { ceiling: string; maxMonths: number | null; minOffer: string }; canOffer: boolean }>()
const l = props.listing
const form = useForm({ amount: '', annual_rate: String(Number(l.rate)), term_months: String(l.term), conditions: '', accept_risk: false })
const ask = useForm({ body: '' })

// Estimate only, for the investor's information. The server recalculates everything.
const monthly = computed(() => {
  const a = +form.amount, r = +form.annual_rate / 1200, n = +form.term_months
  if (!a || !n) return null
  return r ? (a * r) / (1 - Math.pow(1 + r, -n)) : a / n
})
const pending = computed(() => props.myOffers.some((o) => o.status === 'pending'))
</script>

<template>
  <InvestorLayout>
    <PageHead :title="l.name" :sub="`${l.industry} · ${l.region}`" :back="{ href: '/investor/marketplace', label: 'Marketplace' }" />

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
      <div class="space-y-5">
        <Card>
          <div class="flex flex-wrap items-center gap-3"><RiskBadge :r="l.risk" long /><Status :s="l.status" /><span class="text-[13px] text-muted">Listed {{ date(l.published) }} · closes {{ date(l.closes) }}</span></div>
          <div class="mt-5 flex justify-between text-sm"><span class="num font-medium">{{ mk(l.funded) }} accepted</span><span class="num text-muted">of {{ mk(l.requested) }}</span></div>
          <div class="mt-2"><Progress :value="l.progress" label="Funding" /></div>
          <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-line pt-4 text-sm sm:grid-cols-4">
            <div><dt class="text-muted">Requested</dt><dd class="num">{{ mk(l.requested) }}</dd></div>
            <div><dt class="text-muted">Remaining</dt><dd class="num">{{ mk(l.remaining) }}</dd></div>
            <div><dt class="text-muted">Indicative rate</dt><dd class="num">{{ Number(l.rate) }}% a year</dd></div>
            <div><dt class="text-muted">Period</dt><dd>{{ months(l.term) }}</dd></div>
          </dl>
          <p class="mt-4 text-xs text-muted">{{ offerCount }} active offers from investors.</p>
        </Card>
        <Card title="About the business"><p class="text-[15px]">{{ l.summary }}</p></Card>
        <Card title="Financing purpose"><p class="text-[15px]">{{ l.purpose }}</p></Card>
        <Card title="Ask the business a question">
          <form class="space-y-3" @submit.prevent="ask.post(`/investor/marketplace/${l.id}/message`)">
            <TextArea v-model="ask.body" label="Your question" :rows="3" :error="ask.errors.body" hint="Keep conversations on Banyira. Contact details are removed automatically." />
            <Btn type="submit" variant="outline" :disabled="!ask.body.trim() || ask.processing">Send question</Btn>
          </form>
        </Card>
        <Card v-if="myOffers.length" title="Your offers on this business" flush>
          <ul class="divide-y divide-line">
            <li v-for="o in myOffers" :key="o.id" class="flex justify-between px-5 py-3 text-sm"><span class="num">{{ mk(o.amount) }} at {{ Number(o.annual_rate) }}% · {{ months(o.term_months) }}</span><Status :s="o.status" /></li>
          </ul>
        </Card>
      </div>

      <aside class="lg:sticky lg:top-6 lg:self-start">
        <Card title="Make an offer">
          <Notice v-if="!canOffer" tone="warn">Your identity must be verified before you can make offers.</Notice>
          <Notice v-else-if="l.status !== 'open'">This business is no longer accepting offers.</Notice>
          <Notice v-else-if="pending">You have a pending offer here. Withdraw it from My Offers to make a new one.</Notice>
          <form v-else class="space-y-4" @submit.prevent="form.post(`/investor/marketplace/${l.id}/offers`)">
            <TextInput v-model="form.amount" label="Amount (MK)" inputmode="numeric" :error="form.errors.amount" :hint="`${mk(limits.minOffer)} to ${mk(l.remaining)}`" />
            <TextInput v-model="form.annual_rate" label="Annual interest rate (%)" inputmode="decimal" :error="form.errors.annual_rate" :hint="`Maximum ${Number(limits.ceiling)}% a year`" />
            <TextInput v-model="form.term_months" label="Repayment period (months)" inputmode="numeric" :error="form.errors.term_months" :hint="limits.maxMonths ? `Class ${l.risk} limit: ${months(limits.maxMonths)}` : undefined" />
            <TextArea v-model="form.conditions" label="Conditions" optional :rows="2" :error="form.errors.conditions" hint="For example: monthly sales summary shared with investors." />
            <p v-if="monthly" class="rounded-md bg-[#f4f4f1] px-3 py-2 text-[13px] text-muted">If accepted, you would receive about <span class="num text-ink">{{ mk(monthly) }}</span> a month. This is an estimate, not a guaranteed return.</p>
            <label class="flex items-start gap-3 text-[13px]"><input v-model="form.accept_risk" type="checkbox" class="mt-0.5 h-4 w-4" />I understand I could lose some or all of this amount, and that the rating does not guarantee repayment.</label>
            <p v-if="form.errors.accept_risk" class="text-[13px] text-danger">{{ form.errors.accept_risk }}</p>
            <Btn type="submit" full :disabled="form.processing">{{ form.processing ? 'Sending…' : 'Submit offer' }}</Btn>
            <p class="text-xs text-muted">An offer is not an investment. Money moves only after the business accepts and the agreement is signed.</p>
          </form>
        </Card>
      </aside>
    </div>
  </InvestorLayout>
</template>
