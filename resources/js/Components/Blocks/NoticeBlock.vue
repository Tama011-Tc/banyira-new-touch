<script setup lang="ts">
import Notice from '@/Components/Notice.vue'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 pb-12 sm:px-6">
    <div class="max-w-3xl"><Notice :tone="data.tone" :title="data.title || undefined"><span class="whitespace-pre-line">{{ data.body }}</span></Notice></div>
  </div>
</template>
