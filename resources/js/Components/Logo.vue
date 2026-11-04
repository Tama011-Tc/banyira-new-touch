<script setup lang="ts">
withDefaults(defineProps<{ size?: number; light?: boolean }>(), { size: 22 })
</script>

<template>
  <span class="inline-flex items-center gap-2">
    <svg :width="size" :height="size" viewBox="0 0 32 32" aria-hidden="true">
      <rect width="32" height="32" rx="6" :fill="light ? '#fff' : '#1d4a37'" />
      <path d="M10 24V8h6.5a4.25 4.25 0 010 8.5H10m6.5 0a3.75 3.75 0 010 7.5H10" :stroke="light ? '#1d4a37' : '#fff'" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span class="text-[17px] font-semibold tracking-tight" :class="light ? 'text-white' : 'text-ink'">Banyira</span>
  </span>
</template>
