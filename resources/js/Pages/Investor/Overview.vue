<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Stat from '@/Components/Stat.vue'
import Card from '@/Components/Card.vue'
import Notice from '@/Components/Notice.vue'
import Distribution from '@/Components/Distribution.vue'
import Btn from '@/Components/Btn.vue'
import { date, dateTime, mk } from '@/lib/format'

defineProps<{
  verification: string
  emailVerified: boolean
  stats: { invested: string; active: number; outstanding: string; principalReceived: string; interestReceived: string; expectedPrincipal: string; expectedInterest: string; committed: string; pendingOffers: number; next: { due: string; amount: string } | null }
  byRisk: { l: string; v: number }[]
  byIndustry: { l: string; v: number }[]
  activity: { principal: string; interest: string; created_at: string }[]
}>()
</script>

<template>
  <InvestorLayout>
    <PageHead title="Overview" sub="Your portfolio at a glance. Principal and interest are always shown separately.">
      <Btn href="/investor/marketplace">Browse marketplace</Btn>
    </PageHead>

    <div class="space-y-3">
      <Notice v-if="!emailVerified" tone="warn">Confirm your email address to receive agreements and statements. <button class="link font-medium" @click="router.post('/email/verification-notification')">Resend the link</button></Notice>
      <Notice v-if="verification === 'unverified'" tone="warn" title="Verify your identity to start investing">You can browse the marketplace, but you can only make offers after we verify you. <Link href="/onboarding" class="link font-medium">Complete verification</Link></Notice>
      <Notice v-else-if="verification === 'pending'">We are verifying your identity. This usually takes two working days.</Notice>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-x-6 gap-y-6 lg:grid-cols-4">
      <Stat label="Total invested" :value="mk(stats.invested)" note="Principal disbursed to businesses" />
      <Stat label="Outstanding principal" :value="mk(stats.outstanding)" :note="`${stats.active} active investments`" />
      <Stat label="Principal repaid" :value="mk(stats.principalReceived)" note="Your capital returned" />
      <Stat label="Interest received" :value="mk(stats.interestReceived)" note="Your actual return so far" />
      <Stat label="Expected principal" :value="mk(stats.expectedPrincipal)" note="Still scheduled" />
      <Stat label="Expected interest" :value="mk(stats.expectedInterest)" note="If every payment is made" />
      <Stat label="Committed, not yet invested" :value="mk(stats.committed)" note="Accepted offers awaiting disbursement" />
      <Stat label="Next repayment" :value="stats.next ? mk(stats.next.amount) : '—'" :note="stats.next ? 'Due ' + date(stats.next.due) : `${stats.pendingOffers} pending offers`" />
    </div>

    <div class="mt-8 grid gap-5 lg:grid-cols-3">
      <Card title="By risk class"><Distribution label="Outstanding principal by risk class" :data="byRisk" :fmt="(n) => mk(n)" /></Card>
      <Card title="By industry"><Distribution label="Outstanding principal by industry" :data="byIndustry" :fmt="(n) => mk(n)" /></Card>
      <Card title="Recent repayments" flush>
        <ul v-if="activity.length" class="divide-y divide-line">
          <li v-for="(a, i) in activity" :key="i" class="flex justify-between px-5 py-2.5 text-sm">
            <span class="text-muted">{{ dateTime(a.created_at) }}</span>
            <span class="num">{{ mk(a.principal) }} <span class="text-muted">+ {{ mk(a.interest) }} int.</span></span>
          </li>
        </ul>
        <p v-else class="px-5 py-6 text-sm text-muted">No repayments received yet.</p>
        <div class="border-t border-line px-5 py-3"><Link href="/investor/repayments" class="text-[13px] text-muted hover:text-ink">Repayment history →</Link></div>
      </Card>
    </div>

    <p class="mt-8 text-xs text-muted">Expected amounts assume every scheduled payment is made on time. Repayments can be late or missed, and you may lose some or all of your capital.</p>
  </InvestorLayout>
</template>
