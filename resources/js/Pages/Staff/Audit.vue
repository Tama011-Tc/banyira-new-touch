<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import { dateTime, label } from '@/lib/format'

defineProps<{ logs: any; areas: string[]; area: string | null }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Audit log" sub="Every significant action, who took it and from where. Entries cannot be edited.">
      <select class="input w-auto" :value="area ?? ''" aria-label="Area" @change="router.get('/internal/audit', ($event.target as HTMLSelectElement).value ? { area: ($event.target as HTMLSelectElement).value } : {})">
        <option value="">All areas</option><option v-for="a in areas" :key="a" :value="a">{{ label(a) }}</option>
      </select>
    </PageHead>
    <DataTable caption="Audit log" :rows="logs.data"
      :columns="[{ key: 'created_at', label: 'When' }, { key: 'user', label: 'Who' }, { key: 'area', label: 'Area' }, { key: 'action', label: 'Action' }, { key: 'ip', label: 'IP' }]">
      <template #cell-created_at="{ row }"><span class="whitespace-nowrap">{{ dateTime(row.created_at) }}</span></template>
      <template #cell-area="{ row }">{{ label(row.area) }}</template>
      <template #cell-ip="{ row }"><span class="font-mono text-[13px]">{{ row.ip ?? '—' }}</span></template>
    </DataTable>
    <Pagination :page="logs" />
  </StaffLayout>
</template>
