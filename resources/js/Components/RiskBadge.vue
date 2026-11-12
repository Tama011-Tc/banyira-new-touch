<script setup lang="ts">
const LABELS: Record<string, string> = { 'A+': 'Very low risk', A: 'Low risk', B: 'Moderate risk', C: 'Average risk' }
defineProps<{ r: string | null; long?: boolean }>()
</script>

<template>
  <span v-if="r" class="inline-flex items-center gap-2" :title="`${LABELS[r]}. A rating is an assessment, not a guarantee of repayment.`">
    <span class="inline-flex items-center gap-1.5 text-[13px] font-semibold"
      :class="r === 'B' ? 'text-orange-text' : r === 'C' ? 'text-red-text' : 'text-green-text'">
      <span class="h-2 w-2 shrink-0 rounded-full" :class="r === 'B' ? 'bg-orange' : r === 'C' ? 'bg-red' : 'bg-green'" aria-hidden="true" />{{ r }}
    </span>
    <span v-if="long" class="text-[13px] text-label-2">{{ LABELS[r] }}</span>
  </span>
  <span v-else class="text-label-3">—</span>
</template>
