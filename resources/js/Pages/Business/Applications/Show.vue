<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Notice from '@/Components/Notice.vue'
import Status from '@/Components/Status.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Btn from '@/Components/Btn.vue'
import { date, dateTime, mk, months } from '@/lib/format'

defineProps<{ application: any }>()
</script>

<template>
  <BusinessLayout>
    <PageHead :title="application.reference" :sub="`${application.product} · ${mk(application.amount)} over ${months(application.term_months)}`" :back="{ href: '/business/applications', label: 'Applications' }">
      <Btn v-if="application.editable" :href="`/business/applications/${application.id}/edit`">{{ application.status === 'draft' ? 'Continue draft' : 'Respond' }}</Btn>
    </PageHead>

    <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
      <div class="space-y-5">
        <Notice v-if="application.status === 'rejected'" tone="error" title="Not approved">{{ application.decision?.note ?? 'This application was not approved. You may apply again after addressing the issues raised.' }}</Notice>
        <Notice v-else-if="application.decision?.outcome === 'approved'" tone="ok" title="Approved">Class {{ application.decision.class }}. A rating is an assessment, not a guarantee of repayment.</Notice>

        <Card title="Progress" flush>
          <ol class="divide-y divide-line">
            <li v-for="(h, i) in application.history" :key="i" class="flex items-start justify-between gap-4 px-5 py-3">
              <div><p class="text-sm">{{ h.label }}</p><p v-if="h.note" class="text-[13px] text-muted">{{ h.note }}</p></div>
              <span class="whitespace-nowrap text-xs text-muted">{{ dateTime(h.at) }}</span>
            </li>
          </ol>
        </Card>

        <Card v-if="application.requests.length" title="Information requests" flush>
          <ul class="divide-y divide-line">
            <li v-for="r in application.requests" :key="r.id" class="px-5 py-3 text-sm">
              <div class="flex justify-between gap-3"><p>“{{ r.message }}”</p><Status :s="r.status" /></div>
              <p v-if="r.response" class="mt-2 rounded-md bg-[#f4f4f1] px-3 py-2 text-muted">{{ r.response }}</p>
            </li>
          </ul>
        </Card>

        <Card title="Purpose"><p class="text-[15px]">{{ application.purpose }}</p></Card>
      </div>

      <div class="space-y-5">
        <Card title="Status">
          <p><Status :s="application.statusLabel" /></p>
          <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-muted">Submitted</dt><dd>{{ date(application.submitted_at) }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Decided</dt><dd>{{ date(application.decided_at) }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Risk class</dt><dd><RiskBadge :r="application.risk_class" /></dd></div>
          </dl>
        </Card>
        <Card v-if="application.listing" title="Marketplace">
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-muted">Listing</dt><dd class="font-mono text-[13px]">{{ application.listing.reference }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Accepted</dt><dd class="num">{{ mk(application.listing.amount_accepted) }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Closes</dt><dd>{{ date(application.listing.closes_at) }}</dd></div>
          </dl>
          <div class="mt-4"><Btn size="sm" href="/business/offers">View offers</Btn></div>
        </Card>
        <Card title="Documents" flush>
          <ul class="divide-y divide-line">
            <li v-for="d in application.documents" :key="d.id" class="flex justify-between gap-3 px-5 py-2.5 text-sm"><Link :href="`/business/documents/${d.id}`" class="truncate hover:underline">{{ d.type }}</Link><Status :s="d.status" /></li>
          </ul>
        </Card>
      </div>
    </div>
  </BusinessLayout>
</template>
