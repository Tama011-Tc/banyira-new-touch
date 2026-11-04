<script setup lang="ts">
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Stat from '@/Components/Stat.vue'
import Card from '@/Components/Card.vue'
import BarChart from '@/Components/BarChart.vue'
import Distribution from '@/Components/Distribution.vue'
import { label, mk, mkShort } from '@/lib/format'

defineProps<{ stats: Record<string, any>; applicationsByMonth: { l: string; v: number }[]; byClass: { l: string; v: number }[]; pipeline: Record<string, number> }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Platform performance" sub="Live figures from the database. Revenue and profit are Banyira's own accounts only." />
    <div class="grid grid-cols-2 gap-x-6 gap-y-6 lg:grid-cols-4">
      <Stat label="Registered businesses" :value="String(stats.businesses)" />
      <Stat label="Registered investors" :value="String(stats.investors)" :note="`${stats.participating} have invested`" />
      <Stat label="Applications submitted" :value="String(stats.applications)" />
      <Stat label="Approval rate" :value="stats.approvalRate !== null ? stats.approvalRate + '%' : '—'" note="Of decided applications" />
      <Stat label="Financing requested" :value="mkShort(stats.requested)" />
      <Stat label="Financing funded" :value="mkShort(stats.funded)" :note="`${stats.fundedSmes} SMEs funded`" />
      <Stat label="Open listings" :value="String(stats.listings)" />
      <Stat label="Profit to date" :value="mk(stats.profit)" :warn="+stats.profit < 0" :note="`Revenue ${mkShort(stats.revenue)} · expenses ${mkShort(stats.expenses)}`" />
    </div>
    <div class="mt-8 grid gap-5 lg:grid-cols-3">
      <Card title="Applications submitted by month" class="lg:col-span-2"><BarChart label="Applications by month" :data="applicationsByMonth" /></Card>
      <Card title="Funded principal by risk class"><Distribution label="Funded principal by class" :data="byClass" :fmt="(n) => mkShort(n)" /></Card>
      <Card title="Application pipeline" class="lg:col-span-3">
        <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-5">
          <div v-for="(n, s) in pipeline" :key="s"><dt class="text-[13px] text-muted">{{ label(String(s)) }}</dt><dd class="num text-[17px] font-medium">{{ n }}</dd></div>
        </dl>
      </Card>
    </div>
  </StaffLayout>
</template>
