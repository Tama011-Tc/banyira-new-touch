<script setup lang="ts">
const P: Record<string, string> = {
  check: 'M5 12l5 5 9-10',
  x: 'M6 6l12 12M18 6L6 18',
  menu: 'M4 7h16M4 12h16M4 17h16',
  arrow: 'M5 12h14M13 6l6 6-6 6',
  back: 'M19 12H5M11 6l-6 6 6 6',
  search: 'M11 18a7 7 0 100-14 7 7 0 000 14zM21 21l-5-5',
  alert: 'M12 3l10 18H2zM12 10v5M12 18h.01',
  down: 'M12 4v12M6 11l6 6 6-6M5 21h14',
  plus: 'M12 5v14M5 12h14',
  chevdown: 'M6 9l6 6 6-6',
  chev: 'M9 6l6 6-6 6',
  lock: 'M6 11h12v9H6zM8 11V8a4 4 0 118 0v3',
  clip: 'M21 11l-8.5 8.5a5 5 0 01-7-7L14 4a3.5 3.5 0 015 5l-8.5 8.5a2 2 0 01-3-3L15 7',
  doc: 'M7 3h7l5 5v13H7zM14 3v5h5',
}
withDefaults(defineProps<{ n: string; size?: number }>(), { size: 18 })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0">
    <path :d="P[n] ?? P.alert" />
  </svg>
</template>
