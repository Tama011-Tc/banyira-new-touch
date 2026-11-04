<script setup lang="ts">
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Stat from '@/Components/Stat.vue'
import Card from '@/Components/Card.vue'
import BarChart from '@/Components/BarChart.vue'
import Distribution from '@/Components/Distribution.vue'

defineProps<{ stats: Record<string, number>; registrations: { l: string; v: number; v2: number }[]; funnel: { l: string; v: number }[]; sources: { l: string; v: number }[] }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Growth" sub="Customer acquisition and marketplace growth, from live records." />
    <div class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
      <Stat label="Registered businesses" :value="String(stats.businesses)" />
      <Stat label="Registered investors" :value="String(stats.investors)" :note="`${stats.verifiedInvestors} verified`" />
      <Stat label="Applications submitted" :value="String(stats.applications)" />
      <Stat label="Open listings" :value="String(stats.listings)" />
      <Stat label="Open leads" :value="String(stats.openLeads)" />
      <Stat label="Lead conversion" :value="stats.conversion + '%'" note="Leads marked converted" />
    </div>
    <div class="mt-8 grid gap-5 lg:grid-cols-3">
      <Card title="New registrations by month" class="lg:col-span-2"><BarChart label="Registrations" :data="registrations" :legend="['Businesses', 'Investors']" /></Card>
      <Card title="Lead sources"><Distribution label="Lead sources" :data="sources" /></Card>
      <Card title="Lead pipeline" class="lg:col-span-3"><BarChart label="Leads by stage" :data="funnel" /></Card>
    </div>
  </StaffLayout>
</template>
