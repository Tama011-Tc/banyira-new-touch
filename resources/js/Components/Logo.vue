<script setup lang="ts">
// The mark takes the label colour, so it is black in light mode and white in dark.
withDefaults(defineProps<{ size?: number; mark?: boolean }>(), { size: 24 })
</script>

<template>
  <span class="inline-flex items-center gap-2 text-label">
    <svg :width="size" :height="size" viewBox="0 0 32 32" aria-hidden="true">
      <rect width="32" height="32" rx="8" fill="currentColor" />
      <path d="M10 24V8h6.5a4.25 4.25 0 010 8.5H10m6.5 0a3.75 3.75 0 010 7.5H10" stroke="var(--surface)" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span v-if="!mark" class="text-[19px] font-semibold tracking-[-0.03em]">Banyira</span>
  </span>
</template>
