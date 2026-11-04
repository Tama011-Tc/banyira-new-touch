<script setup lang="ts">
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Stat from '@/Components/Stat.vue'
import DataTable from '@/Components/DataTable.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import { mk } from '@/lib/format'

defineProps<{ stats: Record<string, any>; byClass: any[]; upcoming: number }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Repayment and risk" sub="Portfolio quality across all active financing." />
    <div class="grid grid-cols-2 gap-x-6 gap-y-6 lg:grid-cols-4">
      <Stat label="Borrower principal outstanding" :value="mk(stats.outstanding)" />
      <Stat label="On-time rate" :value="stats.onTimeRate !== null ? stats.onTimeRate + '%' : '—'" :note="`${stats.dueInstalments} instalments due so far`" />
      <Stat label="Overdue instalments" :value="String(stats.overdueCount)" :warn="stats.overdueCount > 0" :note="mk(stats.overdueAmount) + ' overdue'" />
      <Stat label="Investments in arrears" :value="String(stats.inArrears)" :warn="stats.inArrears > 0" />
      <Stat label="Confirmed repayments" :value="mk(stats.confirmedRepayments)" />
      <Stat label="Instalments due in 30 days" :value="String(upcoming)" />
    </div>
    <h2 class="mb-3 mt-10 text-[15px]">By risk class</h2>
    <DataTable caption="Portfolio by risk class" :rows="byClass" empty="No active investments yet."
      :columns="[{ key: 'class', label: 'Class' }, { key: 'count', label: 'Investments', align: 'right' }, { key: 'outstanding', label: 'Outstanding principal', align: 'right' }, { key: 'arrears', label: 'In arrears', align: 'right' }]">
      <template #cell-class="{ row }"><RiskBadge :r="row.class" long /></template>
      <template #cell-outstanding="{ row }">{{ mk(row.outstanding) }}</template>
    </DataTable>
  </StaffLayout>
</template>
