<script setup lang="ts">
const LABELS: Record<string, string> = { 'A+': 'Very low risk', A: 'Low risk', B: 'Moderate risk', C: 'Average risk' }
defineProps<{ r: string | null; long?: boolean }>()
</script>

<template>
  <span v-if="r" class="inline-flex items-center gap-2" :title="`${LABELS[r]}. A rating is an assessment, not a guarantee of repayment.`">
    <span
      class="inline-flex min-w-8 justify-center rounded border px-1 font-mono text-xs font-semibold leading-5"
      :class="r === 'B' ? 'border-maize/40 text-maize' : r === 'C' ? 'border-clay/40 text-clay' : 'border-ok/40 text-ok'"
    >{{ r }}</span>
    <span v-if="long" class="text-[13px] text-muted">{{ LABELS[r] }}</span>
  </span>
  <span v-else class="text-muted">—</span>
</template>
