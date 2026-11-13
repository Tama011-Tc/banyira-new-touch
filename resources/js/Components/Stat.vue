<script setup lang="ts">
defineProps<{ label: string; value: string | number; note?: string; warn?: boolean }>()
</script>

<template>
  <div class="surface-card px-4 py-3.5 sm:px-5 sm:py-4">
    <p class="text-[12px] text-label-2">{{ label }}</p>
    <p class="num mt-1 text-[22px] font-semibold leading-tight tracking-[-0.03em]" :class="warn ? 'text-orange-text' : 'text-label'">{{ value }}</p>
    <p v-if="note" class="mt-0.5 text-[12px] text-label-2">{{ note }}</p>
  </div>
</template>
