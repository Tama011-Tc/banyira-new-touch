<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import Status from '@/Components/Status.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import { date, label } from '@/lib/format'

defineProps<{ rows: any }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Assessment history" sub="Every submitted recommendation and its decision." />
    <DataTable caption="Assessments" :rows="rows.data" empty="No assessments yet."
      :columns="[{ key: 'reference', label: 'Application' }, { key: 'business', label: 'Business' }, { key: 'score', label: 'Score', align: 'right' }, { key: 'class', label: 'Class' }, { key: 'recommendation', label: 'Recommended' }, { key: 'assessor', label: 'Assessor' }, { key: 'decider', label: 'Decided by' }, { key: 'status', label: 'Outcome' }, { key: 'submitted', label: 'Submitted' }]">
      <template #cell-reference="{ row }"><Link :href="`/internal/credit/applications/${row.applicationId}`" class="font-mono text-[13px] hover:underline">{{ row.reference }}</Link></template>
      <template #cell-score="{ row }">{{ row.score ?? '—' }}</template>
      <template #cell-class="{ row }"><RiskBadge :r="row.class" /></template>
      <template #cell-recommendation="{ row }">{{ row.recommendation ? label(row.recommendation) : '—' }}</template>
      <template #cell-decider="{ row }">{{ row.decider ?? '—' }}</template>
      <template #cell-status="{ row }"><Status :s="row.status === 'submitted' ? 'awaiting decision' : row.status" /></template>
      <template #cell-submitted="{ row }">{{ date(row.submitted) }}</template>
    </DataTable>
    <Pagination :page="rows" />
  </StaffLayout>
</template>
