<script setup lang="ts">
import { computed } from 'vue'

// Proportion bar with a legend. Readable on small screens, no pie charts.
const props = defineProps<{ data: { l: string; v: number }[]; label: string; fmt?: (n: number) => string }>()
const colors = ['#1d4a37', '#5a8572', '#9db5a8', '#cfdbd4', '#8a8f91', '#c9b48a']
const total = computed(() => props.data.reduce((a, d) => a + d.v, 0) || 1)
const share = (v: number) => Math.round((v / total.value) * 100)
</script>

<template>
  <div>
    <div v-if="data.length" class="flex h-2.5 w-full overflow-hidden rounded-sm" role="img" :aria-label="label + ': ' + data.map((d) => `${d.l} ${share(d.v)}%`).join(', ')">
      <div v-for="(d, i) in data" :key="d.l" class="h-full border-r border-white last:border-0" :style="{ width: (d.v / total) * 100 + '%', background: colors[i % colors.length] }" />
    </div>
    <p v-else class="text-sm text-muted">No data yet.</p>
    <ul class="mt-3 space-y-1.5 text-sm">
      <li v-for="(d, i) in data" :key="d.l" class="flex items-center gap-2">
        <span class="h-2 w-2 rounded-sm" :style="{ background: colors[i % colors.length] }" />
        <span class="flex-1">{{ d.l }}</span>
        <span v-if="fmt" class="num text-muted">{{ fmt(d.v) }}</span>
        <span class="num w-10 text-right text-muted">{{ share(d.v) }}%</span>
      </li>
    </ul>
  </div>
</template>
