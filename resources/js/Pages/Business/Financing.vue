<script setup lang="ts">
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Status from '@/Components/Status.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import Btn from '@/Components/Btn.vue'
import { date, mk, months } from '@/lib/format'

defineProps<{ agreements: any[] }>()
</script>

<template>
  <BusinessLayout>
    <PageHead title="Financing" sub="Your agreements, what you owe and your statements." />
    <div v-if="!agreements.length" class="rounded-lg border border-dashed border-line-2 px-6 py-12 text-center text-sm text-muted">You have no financing agreements yet.</div>
    <div class="space-y-5">
      <Card v-for="a in agreements" :key="a.id">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="font-mono text-[12px] text-muted">{{ a.reference }}</p>
            <p class="mt-1 text-[17px] font-medium">{{ a.purpose }}</p>
            <p class="mt-1"><Status :s="a.status" /></p>
          </div>
          <div class="flex flex-wrap gap-2">
            <ConfirmAction v-if="a.status === 'awaiting_signatures' && !a.iSigned" :href="`/business/agreements/${a.id}/sign`" variant="primary" size="md" title="Sign this agreement?" confirm="Sign agreement">
              Review and sign
              <template #body>You agree to borrow {{ mk(a.principal) }} from {{ a.investors }} investors on the terms listed. A service fee of {{ mk(a.fee) }} ({{ Number(a.feeRate) }}%) is deducted when funds are released.</template>
            </ConfirmAction>
            <Btn v-if="a.disbursed" variant="outline" :href="`/business/agreements/${a.id}/statement`" external>Download statement</Btn>
          </div>
        </div>
        <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-line pt-4 text-sm sm:grid-cols-4">
          <div><dt class="text-muted">Principal</dt><dd class="num font-medium">{{ mk(a.principal) }}</dd></div>
          <div><dt class="text-muted">Service fee</dt><dd class="num">{{ mk(a.fee) }} · {{ Number(a.feeRate) }}%</dd></div>
          <div><dt class="text-muted">Outstanding</dt><dd class="num">{{ mk(a.outstanding) }}</dd></div>
          <div><dt class="text-muted">Disbursed</dt><dd>{{ date(a.disbursed) }}</dd></div>
        </dl>
        <table class="mt-5 w-full text-sm">
          <caption class="sr-only">Investor tranches</caption>
          <thead class="text-left text-xs text-muted"><tr><th class="pb-2 font-medium">Tranche</th><th class="pb-2 text-right font-medium">Principal</th><th class="pb-2 text-right font-medium">Rate</th><th class="pb-2 text-right font-medium">Period</th><th class="pb-2 text-right font-medium">Signed</th></tr></thead>
          <tbody>
            <tr v-for="(t, i) in a.tranches" :key="i" class="border-t border-line">
              <td class="py-2">Investor {{ Number(i) + 1 }}</td><td class="num py-2 text-right">{{ mk(t.principal) }}</td><td class="num py-2 text-right">{{ Number(t.annual_rate) }}%</td><td class="py-2 text-right">{{ months(t.term_months) }}</td><td class="py-2 text-right">{{ t.signed_at ? 'Yes' : 'Waiting' }}</td>
            </tr>
          </tbody>
        </table>
      </Card>
    </div>
  </BusinessLayout>
</template>
