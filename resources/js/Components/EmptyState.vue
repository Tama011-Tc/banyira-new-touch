<script setup lang="ts">
import Icon from './Icon.vue'

withDefaults(defineProps<{ title: string; body?: string; icon?: string; compact?: boolean }>(), { icon: 'inbox' })
</script>

<template>
  <div class="flex flex-col items-center text-center" :class="compact ? 'px-6 py-8' : 'surface-card px-6 py-14'">
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-fill text-label-2"><Icon :n="icon" :size="22" /></span>
    <p class="mt-4 text-[17px] font-semibold tracking-[-0.02em]">{{ title }}</p>
    <p v-if="body" class="mt-1 max-w-sm text-[15px] text-label-2">{{ body }}</p>
    <div v-if="$slots.default" class="mt-5 flex flex-wrap justify-center gap-2"><slot /></div>
  </div>
</template>
