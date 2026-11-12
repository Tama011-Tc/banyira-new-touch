<script setup lang="ts">
import { ref, useId } from 'vue'
import Icon from './Icon.vue'

defineProps<{ items: [string, string][] }>()
const open = ref<number | null>(null)
const id = useId()
</script>

<template>
  <div class="surface-card max-w-3xl divide-y divide-separator overflow-hidden">
    <div v-for="([q, a], i) in items" :key="q">
      <h3 class="text-[17px] font-medium tracking-[-0.02em]">
        <button type="button" class="flex min-h-14 w-full items-center justify-between gap-6 px-5 py-4 text-left transition-colors duration-150 hover:bg-fill-2" :aria-expanded="open === i" :aria-controls="`${id}-${i}`" @click="open = open === i ? null : i">
          <span>{{ q }}</span>
          <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-fill text-label-2 transition-transform duration-300 ease-[var(--ease-out)]" :class="open === i && 'rotate-45'"><Icon n="plus" :size="16" /></span>
        </button>
      </h3>
      <div :id="`${id}-${i}`" class="reveal" :data-open="open === i" role="region" :aria-hidden="open !== i">
        <div><p class="px-5 pb-5 pr-14 text-[15px] text-label-2">{{ a }}</p></div>
      </div>
    </div>
  </div>
</template>
