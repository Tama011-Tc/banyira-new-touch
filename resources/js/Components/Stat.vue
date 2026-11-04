<script setup lang="ts">
defineProps<{ label: string; value: string | number; note?: string; warn?: boolean }>()
</script>

<template>
  <div class="border-t border-ink/80 pt-2.5">
    <p class="text-[13px] text-muted">{{ label }}</p>
    <p class="num mt-0.5 text-[22px] font-semibold tracking-tight" :class="warn && 'text-clay'">{{ value }}</p>
    <p v-if="note" class="text-xs text-muted">{{ note }}</p>
  </div>
</template>
