<script setup lang="ts">
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import { date, dateTime, mk } from '@/lib/format'

defineProps<{ rows: any }>()
</script>

<template>
  <InvestorLayout>
    <PageHead title="Repayment history" sub="Every confirmed repayment credited to you, split into principal and interest." />
    <DataTable caption="Repayments" :rows="rows.data" empty="No repayments yet."
      :columns="[{ key: 'created_at', label: 'Received' }, { key: 'business', label: 'Business' }, { key: 'installment', label: 'Instalment' }, { key: 'principal', label: 'Principal', align: 'right' }, { key: 'interest', label: 'Interest', align: 'right' }]">
      <template #cell-created_at="{ row }">{{ dateTime(row.created_at) }}</template>
      <template #cell-installment="{ row }">#{{ row.installment_no }} · due {{ date(row.due_date) }}</template>
      <template #cell-principal="{ row }">{{ mk(row.principal, 2) }}</template>
      <template #cell-interest="{ row }">{{ mk(row.interest, 2) }}</template>
    </DataTable>
    <Pagination :page="rows" />
  </InvestorLayout>
</template>
