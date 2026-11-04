<script setup lang="ts">
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'

defineProps<{ rows: any[] }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Employee performance" sub="Activity measured from the work each person records in the system." />
    <DataTable caption="Employee activity" :rows="rows" empty="No recorded activity yet."
      :columns="[{ key: 'name', label: 'Employee' }, { key: 'area', label: 'Area' }, { key: 'measure', label: 'Measure' }, { key: 'value', label: 'Count', align: 'right' }, { key: 'extra', label: '' }]">
      <template #cell-extra="{ row }"><span class="text-[13px] text-muted">{{ row.extra ?? '' }}</span></template>
    </DataTable>
  </StaffLayout>
</template>
