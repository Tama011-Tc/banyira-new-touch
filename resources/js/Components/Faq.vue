<script setup lang="ts">
import { ref, useId } from 'vue'
import Icon from './Icon.vue'

defineProps<{ items: [string, string][] }>()
const open = ref<number | null>(null)
const id = useId()
</script>

<template>
  <div class="max-w-3xl divide-y divide-separator border-y border-separator">
    <div v-for="([q, a], i) in items" :key="q">
      <h3 class="text-[17px] font-medium tracking-[-0.02em]">
        <button type="button" class="group flex min-h-14 w-full items-center justify-between gap-6 py-4 text-left" :aria-expanded="open === i" :aria-controls="`${id}-${i}`" @click="open = open === i ? null : i">
          <span>{{ q }}</span>
          <span class="shrink-0 text-label-3 transition-[transform,color] duration-300 ease-[var(--ease-smooth)] group-hover:text-label" :class="open === i && 'rotate-45'"><Icon n="plus" :size="16" /></span>
        </button>
      </h3>
      <div :id="`${id}-${i}`" class="reveal" :data-open="open === i" role="region" :aria-hidden="open !== i">
        <div><p class="max-w-[62ch] pb-5 pr-10 text-[15px] text-label-2">{{ a }}</p></div>
      </div>
    </div>
  </div>
</template>
