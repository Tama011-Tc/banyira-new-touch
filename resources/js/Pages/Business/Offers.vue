<script setup lang="ts">
import { computed, ref } from 'vue'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Stat from '@/Components/Stat.vue'
import Progress from '@/Components/Progress.vue'
import Status from '@/Components/Status.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'
import { date, mk, months } from '@/lib/format'

const props = defineProps<{ listing: any; offers: any[]; fee: { rate: string; fee: string } | null }>()
const sort = ref<'amount' | 'rate' | 'term'>('rate')
const show = ref<'pending' | 'all'>('pending')
const list = computed(() => props.offers
  .filter((o) => show.value === 'all' || o.status === 'pending')
  .sort((a, b) => sort.value === 'rate' ? +a.rate - +b.rate : sort.value === 'term' ? a.term - b.term : +b.amount - +a.amount))
const lowest = computed(() => Math.min(...props.offers.filter((o) => o.status === 'pending').map((o) => +o.rate)))
const pct = computed(() => props.listing ? (+props.listing.amount_accepted / +props.listing.amount_requested) * 100 : 0)
</script>

<template>
  <BusinessLayout>
    <PageHead title="Marketplace offers" sub="Compare investor offers and accept the ones that suit you. Nothing is final until you confirm." />

    <div v-if="!listing" class="rounded-lg border border-dashed border-line-2 px-6 py-12 text-center text-sm text-muted">
      Offers appear here once an application is approved and published to investors.
      <div class="mt-3"><Btn size="sm" href="/business/applications">My applications</Btn></div>
    </div>

    <template v-else>
      <div class="flex flex-wrap items-center gap-3 text-sm"><span class="font-mono text-[13px] text-muted">{{ listing.reference }}</span><RiskBadge :r="listing.risk_class" long /><Status :s="listing.status" /><span class="text-muted">Closes {{ date(listing.closes_at) }}</span></div>

      <div class="mt-5 grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
        <Stat label="Requested" :value="mk(listing.amount_requested)" />
        <Stat label="Accepted so far" :value="mk(listing.amount_accepted)" />
        <Stat label="Still needed" :value="mk(listing.remaining)" />
        <Stat v-if="fee" label="Service fee" :value="mk(fee.fee)" :note="`${Number(fee.rate)}% of the amount financed`" />
      </div>
      <div class="mt-4"><Progress :value="pct" label="Funding accepted" /></div>

      <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm">
          <label for="sort" class="text-muted">Sort by</label>
          <select id="sort" v-model="sort" class="input w-auto"><option value="rate">Lowest rate</option><option value="amount">Largest amount</option><option value="term">Shortest period</option></select>
          <select v-model="show" class="input w-auto" aria-label="Show"><option value="pending">Pending only</option><option value="all">All offers</option></select>
        </div>
        <ConfirmAction v-if="listing.status === 'open' && +listing.amount_accepted > 0" :href="`/business/listings/${listing.id}/close`" title="Close funding now?" confirm="Close funding" size="sm">
          Proceed with {{ mk(listing.amount_accepted) }}
          <template #body>Investors will no longer be able to make offers. Pending offers will expire, and an agreement will be prepared for the {{ mk(listing.amount_accepted) }} you have accepted.</template>
        </ConfirmAction>
      </div>

      <div v-if="!list.length" class="mt-4 rounded-lg border border-dashed border-line-2 px-6 py-10 text-center text-sm text-muted">No {{ show === 'pending' ? 'pending ' : '' }}offers yet.</div>
      <div v-else class="mt-4 relative overflow-x-auto rounded-lg border border-line bg-surface">
        <table class="w-full min-w-[640px] text-sm">
          <caption class="sr-only">Investor offers</caption>
          <thead class="text-left text-xs text-muted"><tr><th class="px-4 py-2.5 font-medium">Investor</th><th class="px-4 py-2.5 text-right font-medium">Amount</th><th class="px-4 py-2.5 text-right font-medium">Rate a year</th><th class="px-4 py-2.5 font-medium">Period</th><th class="px-4 py-2.5 text-right font-medium">Monthly cost</th><th class="px-4 py-2.5 font-medium">Status</th><th class="px-4 py-2.5"><span class="sr-only">Actions</span></th></tr></thead>
          <tbody>
            <tr v-for="o in list" :key="o.id" class="border-t border-line align-top">
              <td class="px-4 py-3">{{ o.investor }}<p v-if="o.conditions" class="mt-0.5 max-w-[220px] text-xs text-muted">Condition: {{ o.conditions }}</p></td>
              <td class="num px-4 py-3 text-right font-medium">{{ mk(o.amount) }}</td>
              <td class="num px-4 py-3 text-right">{{ Number(o.rate) }}%<span v-if="o.status === 'pending' && +o.rate === lowest" class="ml-1.5 text-xs text-ok">lowest</span></td>
              <td class="px-4 py-3">{{ months(o.term) }}</td>
              <td class="num px-4 py-3 text-right text-muted">{{ mk(o.monthly) }}</td>
              <td class="px-4 py-3"><Status :s="o.status" /><p v-if="o.status === 'pending'" class="text-xs text-muted">until {{ date(o.expires) }}</p></td>
              <td class="px-4 py-2.5">
                <div v-if="o.status === 'pending' && listing.status === 'open'" class="flex justify-end gap-2">
                  <ConfirmAction :href="`/business/offers/${o.id}/accept`" variant="primary" title="Accept this offer?" confirm="Accept offer">
                    Accept
                    <template #body>You agree to borrow {{ mk(o.amount) }} at {{ Number(o.rate) }}% a year over {{ months(o.term) }}, about {{ mk(o.monthly) }} a month. This cannot be undone. The agreement is prepared once funding closes.</template>
                  </ConfirmAction>
                  <ConfirmAction :href="`/business/offers/${o.id}/reject`" danger title="Reject this offer?" confirm="Reject offer">
                    Reject
                    <template #body>The investor will be told their offer was not accepted.</template>
                  </ConfirmAction>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="mt-3 text-xs text-muted">Monthly cost is the estimated first instalment for that offer alone. You cannot accept offers that would take you over the amount you requested.</p>
      <div class="mt-6"><Notice>Investor identities stay private until an agreement is signed.</Notice></div>
    </template>
  </BusinessLayout>
</template>
