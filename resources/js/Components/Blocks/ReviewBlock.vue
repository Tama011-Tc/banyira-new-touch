<script setup lang="ts">
import BlockShell from './BlockShell.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import { usePageContext } from '@/lib/blocks'
import { mkShort, months } from '@/lib/format'

defineProps<{ data: Record<string, any> }>()
const page = usePageContext()
</script>

<template>
  <BlockShell :data="data">
    <ol class="space-y-3 text-[15px]">
      <li v-for="(item, i) in data.items" :key="i" class="grid grid-cols-[2rem_1fr]">
        <span class="num pt-px text-[13px] text-label-3">{{ String(Number(i) + 1).padStart(2, '0') }}</span>{{ item.title }}
      </li>
    </ol>
    <table class="mt-10 w-full text-sm">
      <caption class="sr-only">Risk classes and limits</caption>
      <thead class="text-left text-xs text-muted"><tr><th class="pb-2 font-medium">Class</th><th class="pb-2 font-medium">Meaning</th><th class="pb-2 text-right font-medium">Limits</th></tr></thead>
      <tbody>
        <tr v-for="b in page.live.rules.bands" :key="b.class" class="border-t border-line">
          <td class="py-2.5"><RiskBadge :r="b.class" /></td>
          <td class="py-2.5">{{ b.label }}</td>
          <td class="num py-2.5 text-right text-muted">{{ [b.maxAmount && 'max ' + mkShort(b.maxAmount), b.maxMonths && months(b.maxMonths)].filter(Boolean).join(', ') || 'None' }}</td>
        </tr>
      </tbody>
    </table>
    <p v-if="data.footnote" class="mt-3 text-xs text-muted">{{ data.footnote }}</p>
  </BlockShell>
</template>
