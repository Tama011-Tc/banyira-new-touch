<script setup lang="ts">
import Icon from './Icon.vue'

withDefaults(defineProps<{ title: string; body?: string; icon?: string; compact?: boolean }>(), { icon: 'inbox' })
</script>

<template>
  <div class="flex flex-col items-center text-center" :class="compact ? 'px-6 py-8' : 'surface-card px-6 py-14'">
    <span class="text-label-3"><Icon :n="icon" :size="22" :stroke="1.5" /></span>
    <p class="mt-3 text-[15px] font-semibold">{{ title }}</p>
    <p v-if="body" class="mt-1 max-w-sm text-[14px] text-label-2">{{ body }}</p>
    <div v-if="$slots.default" class="mt-5 flex flex-wrap justify-center gap-2"><slot /></div>
  </div>
</template>
