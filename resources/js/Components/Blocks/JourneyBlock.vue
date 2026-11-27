<script setup lang="ts">
import Journey from '@/Components/Journey.vue'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
    <Journey :phases="data.phases" />
  </div>
</template>
