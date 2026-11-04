<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Status from '@/Components/Status.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import Notice from '@/Components/Notice.vue'
import { date, mk, months } from '@/lib/format'

const props = defineProps<{ agreements: any[] }>()
const demo = usePage().props.demoPayments
const toFund = props.agreements.filter((a) => a.status === 'awaiting_funding' && !a.fundsConfirmed)
</script>

<template>
  <InvestorLayout>
    <PageHead title="Agreements" sub="Each accepted offer is part of one agreement with the business." />
    <div v-if="toFund.length" class="mb-6">
      <Notice tone="warn" title="Funds needed">
        <p v-for="a in toFund" :key="a.id">Send <span class="num font-medium">{{ mk(a.principal) }}</span> for {{ a.reference }} to the Banyira client account, using <span class="font-mono">{{ a.reference }}</span> as the reference.</p>
        <p v-if="demo" class="mt-1">Demonstration mode: do not send real money. A finance officer confirms funding manually.</p>
      </Notice>
    </div>
    <DataTable caption="Agreements" :rows="agreements" empty="No agreements yet."
      :columns="[{ key: 'reference', label: 'Agreement' }, { key: 'business', label: 'Business' }, { key: 'principal', label: 'Your principal', align: 'right' }, { key: 'terms', label: 'Terms' }, { key: 'signed', label: 'You signed' }, { key: 'status', label: 'Status' }]">
      <template #cell-reference="{ row }"><span class="font-mono text-[13px]">{{ row.reference }}</span></template>
      <template #cell-principal="{ row }">{{ mk(row.principal) }}</template>
      <template #cell-terms="{ row }">{{ Number(row.rate) }}% · {{ months(row.term) }}</template>
      <template #cell-signed="{ row }">{{ date(row.signed) }}</template>
      <template #cell-status="{ row }"><Status :s="row.status" /></template>
      <template #actions="{ row }">
        <ConfirmAction v-if="row.status === 'awaiting_signatures' && !row.signed" :href="`/investor/agreements/${row.id}/sign`" variant="primary" title="Sign this agreement?" confirm="Sign">
          Review and sign
          <template #body>You agree to lend {{ mk(row.principal) }} at {{ Number(row.rate) }}% a year over {{ months(row.term) }}. You may lose some or all of this amount.</template>
        </ConfirmAction>
      </template>
    </DataTable>
  </InvestorLayout>
</template>
