<script setup lang="ts">
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import { date, mk } from '@/lib/format'

defineProps<{ rows: any[] }>()
</script>

<template>
  <InvestorLayout>
    <PageHead title="Transactions" sub="Money you sent to businesses, and money returned to you." />
    <DataTable caption="Transactions" :rows="rows" empty="No transactions yet."
      :columns="[{ key: 'date', label: 'Date' }, { key: 'type', label: 'Type' }, { key: 'reference', label: 'Reference' }, { key: 'amount', label: 'Amount', align: 'right' }]">
      <template #cell-date="{ row }">{{ date(row.date) }}</template>
      <template #cell-reference="{ row }"><span class="font-mono text-[13px]">{{ row.reference }}</span></template>
      <template #cell-amount="{ row }"><span :class="+row.amount < 0 ? '' : 'text-ok'">{{ +row.amount < 0 ? '−' : '+' }}{{ mk(Math.abs(+row.amount), 2) }}</span></template>
    </DataTable>
  </InvestorLayout>
</template>
