<script setup lang="ts">
import { computed } from 'vue'

// Lightweight SVG bars with a text alternative. Optional second series (v2).
const props = withDefaults(defineProps<{ data: { l: string; v: number; v2?: number }[]; label: string; legend?: [string, string]; fmt?: (n: number) => string }>(), {
  fmt: (n: number) => n.toLocaleString('en-US'),
})
const H = 140
const max = computed(() => Math.max(1, ...props.data.flatMap((d) => [d.v, d.v2 ?? 0])))
const W = computed(() => Math.max(1, props.data.length) * 56)
const alt = computed(() => props.label + ': ' + props.data.map((d) => `${d.l} ${props.fmt(d.v)}${d.v2 !== undefined ? ' / ' + props.fmt(d.v2) : ''}`).join(', '))
</script>

<template>
  <figure>
    <svg :viewBox="`0 0 ${W} ${H + 24}`" role="img" :aria-label="alt" class="w-full">
      <line x1="0" :x2="W" :y1="H + 0.5" :y2="H + 0.5" style="stroke: var(--separator)" />
      <g v-for="(d, i) in data" :key="i" :transform="`translate(${i * 56 + 12},0)`">
        <title>{{ d.l }}: {{ fmt(d.v) }}<template v-if="d.v2 !== undefined"> / {{ fmt(d.v2) }}</template></title>
        <rect :y="H - (d.v / max) * H" :width="d.v2 !== undefined ? 15 : 32" :height="(d.v / max) * H" rx="3" style="fill: var(--chart-1)" />
        <rect v-if="d.v2 !== undefined" x="17" :y="H - (d.v2 / max) * H" width="15" :height="(d.v2 / max) * H" rx="3" style="fill: var(--chart-3)" />
        <text x="16" :y="H + 16" text-anchor="middle" font-size="11" style="fill: var(--label-2)">{{ d.l }}</text>
      </g>
    </svg>
    <figcaption v-if="legend" class="mt-2 flex gap-4 text-[13px] text-label-2">
      <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[var(--chart-1)]" />{{ legend[0] }}</span>
      <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[var(--chart-3)]" />{{ legend[1] }}</span>
    </figcaption>
  </figure>
</template>
