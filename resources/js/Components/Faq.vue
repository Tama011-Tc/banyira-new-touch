<script setup lang="ts">
import { ref, useId } from 'vue'

defineProps<{ items: [string, string][] }>()
const open = ref<number | null>(null)
const id = useId()
</script>

<template>
  <div class="max-w-3xl divide-y divide-line border-y border-line">
    <div v-for="([q, a], i) in items" :key="q">
      <h3 class="font-sans text-[15px] font-normal tracking-normal">
        <button type="button" class="group flex w-full items-center justify-between gap-6 py-4 text-left" :aria-expanded="open === i" :aria-controls="`${id}-${i}`" @click="open = open === i ? null : i">
          <span class="transition-colors duration-150 group-hover:text-forest">{{ q }}</span>
          <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true" class="shrink-0 text-muted transition-transform duration-300 ease-[var(--ease-out)]" :class="open === i && 'rotate-45'">
            <path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          </svg>
        </button>
      </h3>
      <div :id="`${id}-${i}`" class="collapse" :data-open="open === i" role="region" :aria-hidden="open !== i">
        <div><p class="pb-5 pr-10 text-muted">{{ a }}</p></div>
      </div>
    </div>
  </div>
</template>
