<script setup lang="ts">
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Status from '@/Components/Status.vue'
import Btn from '@/Components/Btn.vue'
import { date } from '@/lib/format'

defineProps<{ area: 'business' | 'investor'; documents: any[] }>()
</script>

<template>
  <CustomerLayout :area="area">
    <PageHead title="Documents" sub="Files are stored privately. Only you and the Banyira staff who need them can open them." />
    <DataTable caption="Documents" :rows="documents" empty="No documents yet."
      :columns="[{ key: 'name', label: 'Document' }, { key: 'type', label: 'Type' }, { key: 'ref', label: 'Reference' }, { key: 'status', label: 'Status' }, { key: 'date', label: 'Date' }]">
      <template #cell-ref="{ row }"><span class="font-mono text-[13px]">{{ row.ref }}</span></template>
      <template #cell-status="{ row }"><Status :s="row.status" /></template>
      <template #cell-date="{ row }">{{ date(row.date) }}</template>
      <template #actions="{ row }"><Btn v-if="row.download" size="sm" variant="outline" :href="row.download" external>Download</Btn></template>
    </DataTable>
  </CustomerLayout>
</template>
