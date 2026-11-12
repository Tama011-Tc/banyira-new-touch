<script setup lang="ts">
import { mk, mkShort } from '@/lib/format'

// Example of one request funded by several investors. Static: it is an
// illustration, so it never counts up or animates like live data.
const props = defineProps<{ total: number; parts: { who: string; amount: number; rate?: string }[] }>()

const COLORS = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-5)']
const offset = (i: number) => props.parts.slice(0, i).reduce((a, p) => a + p.amount, 0) / props.total * 100
</script>

<template>
  <div>
    <div class="flex items-baseline justify-between gap-4">
      <p class="num text-[28px] font-semibold tracking-[-0.03em]">{{ mk(total) }}</p>
      <p class="flex items-center gap-1.5 text-[13px] text-green-text"><span class="h-2 w-2 rounded-full bg-green" aria-hidden="true" />Fully funded</p>
    </div>

    <div class="relative mt-4 h-9 overflow-hidden rounded-md bg-fill" role="img" :aria-label="`${mk(total)} funded by ${parts.length} investors: ` + parts.map((p) => `${p.who} ${mk(p.amount)}`).join(', ')">
      <div v-for="(p, i) in parts" :key="p.who" class="absolute inset-y-0 flex items-center justify-center border-r-2 border-surface text-[12px] font-semibold last:border-r-0"
        :class="i === 0 ? 'text-white' : 'text-chart-ink'"
        :style="{ left: offset(i) + '%', width: (p.amount / total) * 100 + '%', background: COLORS[i % COLORS.length] }">
        {{ mkShort(p.amount) }}
      </div>
    </div>

    <ul class="mt-4 space-y-1.5 text-sm">
      <li v-for="(p, i) in parts" :key="p.who" class="flex items-center gap-2">
        <span class="h-2 w-2 rounded-sm" :style="{ background: COLORS[i % COLORS.length] }" aria-hidden="true" />
        <span class="flex-1">{{ p.who }}</span>
        <span v-if="p.rate" class="num text-label-2">{{ p.rate }}</span>
        <span class="num w-16 text-right text-label-2">{{ mkShort(p.amount) }}</span>
      </li>
    </ul>
  </div>
</template>
