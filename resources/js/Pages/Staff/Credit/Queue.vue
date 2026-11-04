<script setup lang="ts">
import { computed } from 'vue'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Tabs from '@/Components/Tabs.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import Status from '@/Components/Status.vue'
import Btn from '@/Components/Btn.vue'
import { date, mk, months } from '@/lib/format'

const props = defineProps<{ applications: any; status: string; counts: Record<string, number> }>()
const n = (k: string) => props.counts[k] ?? 0
const tabs = computed(() => [
  { key: 'open', label: 'Open', href: '/internal/credit?status=open', count: n('submitted') + n('under_review') + n('information_required') + n('assessment_in_progress') },
  { key: 'mine', label: 'Assigned to me', href: '/internal/credit?status=mine' },
  { key: 'submitted', label: 'New', href: '/internal/credit?status=submitted', count: n('submitted') },
  { key: 'information_required', label: 'Waiting on business', href: '/internal/credit?status=information_required', count: n('information_required') },
  { key: 'all', label: 'All', href: '/internal/credit?status=all' },
])
</script>

<template>
  <StaffLayout>
    <PageHead title="Application queue" sub="Oldest first. Open an application to verify, assess and recommend." />
    <Tabs :model-value="status" :tabs="tabs" />
    <div class="mt-4">
      <DataTable caption="Applications" :rows="applications.data" empty="Nothing in this queue."
        :columns="[{ key: 'reference', label: 'Reference' }, { key: 'business', label: 'Business' }, { key: 'product', label: 'Product' }, { key: 'amount', label: 'Amount', align: 'right' }, { key: 'submitted', label: 'Submitted' }, { key: 'assignee', label: 'Officer' }, { key: 'status', label: 'Stage' }]">
        <template #cell-reference="{ row }"><span class="font-mono text-[13px]">{{ row.reference }}</span></template>
        <template #cell-business="{ row }">{{ row.business }}<span class="block text-xs text-muted">{{ row.industry }}</span></template>
        <template #cell-amount="{ row }">{{ mk(row.amount) }}<span class="block text-xs text-muted">{{ months(row.term) }}</span></template>
        <template #cell-submitted="{ row }">{{ date(row.submitted) }}</template>
        <template #cell-assignee="{ row }">{{ row.assignee ?? '—' }}</template>
        <template #cell-status="{ row }"><Status :s="row.statusLabel" /></template>
        <template #actions="{ row }"><Btn size="sm" :href="`/internal/credit/applications/${row.id}`">Open</Btn></template>
      </DataTable>
      <Pagination :page="applications" />
    </div>
  </StaffLayout>
</template>
