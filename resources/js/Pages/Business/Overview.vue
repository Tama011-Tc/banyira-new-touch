<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Stat from '@/Components/Stat.vue'
import Card from '@/Components/Card.vue'
import Notice from '@/Components/Notice.vue'
import Status from '@/Components/Status.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Progress from '@/Components/Progress.vue'
import Btn from '@/Components/Btn.vue'
import { date, dateTime, mk } from '@/lib/format'

const props = defineProps<{
  profile: { name: string; verification: string }
  emailVerified: boolean
  application: { id: number; reference: string; product: string; amount: string; status: string; statusLabel: string; risk: string | null; listing: { amount_requested: string; amount_accepted: string; status: string } | null } | null
  openRequests: number
  stats: { active: number; financed: string; outstanding: string; pendingOffers: number }
  upcoming: { due: string; total: string; overdue: boolean }[]
  activity: { reference: string; amount: string; annual_rate: string; term_months: number; status: string; updated_at: string }[]
  notifications: { title: string; body: string; at: string; read: boolean }[]
}>()

const track = ['draft', 'submitted', 'under_review', 'assessment_in_progress', 'approved', 'listed', 'fully_funded']
const labels = ['Draft', 'Submitted', 'Review', 'Assessment', 'Approved', 'Listed', 'Funded']
const pos = computed(() => {
  const s = props.application?.status ?? ''
  return s === 'information_required' ? 2 : s === 'closed' ? 6 : track.indexOf(s)
})
</script>

<template>
  <BusinessLayout>
    <PageHead :title="profile.name" sub="Business overview">
      <Btn href="/business/applications">New application</Btn>
    </PageHead>

    <div class="space-y-3">
      <Notice v-if="!emailVerified" tone="warn">Confirm your email address to receive statements and agreements. <button class="link font-medium" @click="router.post('/email/verification-notification')">Resend the link</button></Notice>
      <Notice v-if="profile.verification === 'unverified'" tone="warn">Complete your business profile so we can verify you. <Link href="/onboarding" class="link font-medium">Continue</Link></Notice>
      <Notice v-else-if="profile.verification === 'pending'">Your business details are being verified. This usually takes two working days.</Notice>
      <Notice v-if="openRequests" tone="warn" title="Action needed">Banyira has asked for more information on an application. <Link href="/business/applications" class="link font-medium">Respond now</Link></Notice>
    </div>

    <Card v-if="application" class="mt-6">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="font-mono text-[12px] text-muted">{{ application.reference }} · {{ application.product }}</p>
          <p class="mt-1 text-[19px] font-semibold">{{ application.statusLabel }}</p>
          <p class="num text-sm text-muted">{{ mk(application.amount) }} requested</p>
        </div>
        <div class="flex items-center gap-4"><RiskBadge :r="application.risk" long /><Btn variant="outline" size="sm" :href="`/business/applications/${application.id}`">View</Btn></div>
      </div>
      <ol v-if="application.status !== 'rejected'" class="mt-6 grid grid-cols-7 gap-1 text-[11px] sm:text-xs">
        <li v-for="(l, i) in labels" :key="l">
          <div class="h-1 rounded-full" :class="i <= pos ? 'bg-forest' : 'bg-line'" />
          <p class="mt-1.5 truncate" :class="i <= pos ? 'text-ink' : 'text-muted'">{{ l }}</p>
        </li>
      </ol>
      <div v-if="application.listing" class="mt-5 border-t border-line pt-4">
        <div class="flex justify-between text-sm"><span>{{ mk(application.listing.amount_accepted) }} accepted</span><span class="text-muted">of {{ mk(application.listing.amount_requested) }}</span></div>
        <div class="mt-2"><Progress :value="(+application.listing.amount_accepted / +application.listing.amount_requested) * 100" label="Funding accepted" /></div>
      </div>
    </Card>
    <Card v-else class="mt-6">
      <p class="font-medium">You have not applied yet.</p>
      <p class="mt-1 text-sm text-muted">Start an application. You can save it as a draft and finish it later.</p>
      <div class="mt-4"><Btn href="/business/applications">Start an application</Btn></div>
    </Card>

    <div class="mt-8 grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
      <Stat label="Active financing" :value="String(stats.active)" />
      <Stat label="Total financed" :value="mk(stats.financed)" />
      <Stat label="Outstanding balance" :value="mk(stats.outstanding)" note="Principal and interest" />
      <Stat label="Offers waiting" :value="String(stats.pendingOffers)" />
    </div>

    <div class="mt-8 grid gap-5 lg:grid-cols-3">
      <Card title="Upcoming repayments" flush>
        <ul v-if="upcoming.length" class="divide-y divide-line">
          <li v-for="u in upcoming" :key="u.due" class="flex items-center justify-between px-5 py-3 text-sm">
            <span>{{ date(u.due) }} <span v-if="u.overdue" class="ml-1 text-xs text-danger">overdue</span></span><span class="num font-medium">{{ mk(u.total) }}</span>
          </li>
        </ul>
        <p v-else class="px-5 py-6 text-sm text-muted">No repayments due.</p>
        <div class="border-t border-line px-5 py-3"><Link href="/business/repayments" class="text-[13px] text-muted hover:text-ink">Repayments →</Link></div>
      </Card>
      <Card title="Investor activity" flush>
        <ul v-if="activity.length" class="divide-y divide-line">
          <li v-for="a in activity" :key="a.reference" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
            <span><span class="num">{{ mk(a.amount) }}</span> <span class="text-muted">at {{ Number(a.annual_rate) }}%</span></span><Status :s="a.status" />
          </li>
        </ul>
        <p v-else class="px-5 py-6 text-sm text-muted">Offers appear here once you are listed.</p>
        <div class="border-t border-line px-5 py-3"><Link href="/business/offers" class="text-[13px] text-muted hover:text-ink">Marketplace offers →</Link></div>
      </Card>
      <Card title="Notifications" flush>
        <ul v-if="notifications.length" class="divide-y divide-line">
          <li v-for="(n, i) in notifications" :key="i" class="px-5 py-3 text-sm">
            <p :class="!n.read && 'font-medium'">{{ n.title }}</p><p class="text-[13px] text-muted">{{ n.body }}</p><p class="mt-0.5 text-xs text-muted">{{ dateTime(n.at) }}</p>
          </li>
        </ul>
        <p v-else class="px-5 py-6 text-sm text-muted">Nothing new.</p>
        <div class="border-t border-line px-5 py-3"><Link href="/business/notifications" class="text-[13px] text-muted hover:text-ink">All notifications →</Link></div>
      </Card>
    </div>
  </BusinessLayout>
</template>
