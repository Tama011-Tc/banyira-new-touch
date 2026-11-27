<script setup lang="ts">
import { computed } from 'vue'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import TableToolbar from '@/Components/TableToolbar.vue'
import { dateTime, label } from '@/lib/format'
import { useTableQuery, type TableState } from '@/lib/table'

const props = defineProps<{ logs: any; query: TableState; areas: string[] }>()
const { apply, sortBy } = useTableQuery('/internal/audit', () => props.query)
const filters = computed(() => [{ key: 'area', label: 'Area', options: props.areas.map((a) => ({ value: a, label: label(a) })) }])
</script>

<template>
  <StaffLayout>
    <PageHead title="Audit log" sub="Every significant action, who took it and from where. Entries cannot be edited." />
    <TableToolbar :query="query" :filters="filters" search-label="Search actions" @change="apply" />
    <DataTable caption="Audit log" empty="No entries match" :rows="logs.data" :order="query" @sort="sortBy"
      :columns="[{ key: 'created_at', label: 'When', sort: 'when' }, { key: 'user', label: 'Who' }, { key: 'area', label: 'Area' }, { key: 'action', label: 'Action' }, { key: 'ip', label: 'IP' }]">
      <template #cell-created_at="{ row }"><span class="whitespace-nowrap">{{ dateTime(row.created_at) }}</span></template>
      <template #cell-area="{ row }">{{ label(row.area) }}</template>
      <template #cell-ip="{ row }"><span class="font-mono text-[13px]">{{ row.ip ?? '—' }}</span></template>
    </DataTable>
    <Pagination :page="logs" />
  </StaffLayout>
</template>
