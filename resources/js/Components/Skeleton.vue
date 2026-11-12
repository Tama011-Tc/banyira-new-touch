<script setup lang="ts">
withDefaults(defineProps<{ rows?: number; card?: boolean }>(), { rows: 3 })
</script>

<template>
  <div :class="card && 'surface-card p-5'" aria-busy="true" aria-label="Loading" role="status">
    <div class="skeleton h-5 w-2/5" />
    <div v-for="i in rows" :key="i" class="skeleton mt-3 h-4" :style="{ width: `${96 - i * 13}%` }" />
  </div>
</template>
