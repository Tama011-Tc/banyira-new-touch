<script setup lang="ts">
import BlockShell from './BlockShell.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import { usePageContext } from '@/lib/blocks'
import { mk, months } from '@/lib/format'

defineProps<{ data: Record<string, any> }>()
const page = usePageContext()
</script>

<template>
  <BlockShell :data="data">
    <dl class="grid max-w-2xl grid-cols-2 gap-6 sm:grid-cols-3">
      <div><dt class="text-[13px] text-muted">Minimum offer</dt><dd class="num mt-0.5 font-medium">{{ mk(page.live.minOffer) }}</dd></div>
      <div><dt class="text-[13px] text-muted">Maximum annual rate</dt><dd class="num mt-0.5 font-medium">{{ Number(page.live.rules.ceiling) }}%</dd></div>
      <div v-for="b in page.live.rules.bands.filter((x: any) => x.maxMonths)" :key="b.class"><dt class="text-[13px] text-muted">Longest period, class <RiskBadge :r="b.class" /></dt><dd class="num mt-0.5 font-medium">{{ months(b.maxMonths) }}</dd></div>
    </dl>
  </BlockShell>
</template>
