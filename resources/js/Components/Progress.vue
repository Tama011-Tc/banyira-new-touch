<script setup lang="ts">
import { onMounted, ref } from 'vue'

// Fills to its value on first paint, so funding progress reads as movement
// toward a goal; later changes glide to the new value.
const props = withDefaults(defineProps<{ value: number; label?: string }>(), { label: 'Progress' })
const ready = ref(false)
onMounted(() => requestAnimationFrame(() => (ready.value = true)))
</script>

<template>
  <div role="progressbar" :aria-valuenow="Math.round(value)" aria-valuemin="0" aria-valuemax="100" :aria-label="label" class="h-1 w-full overflow-hidden rounded-full bg-line">
    <div class="fill h-full origin-left rounded-full bg-forest" :style="{ transform: `scaleX(${ready ? Math.min(100, Math.max(0, props.value)) / 100 : 0})` }" />
  </div>
</template>

<style scoped>
.fill { transition: transform 900ms var(--ease-out); }
@media (prefers-reduced-motion: reduce) { .fill { transition-duration: 1ms; } }
</style>
