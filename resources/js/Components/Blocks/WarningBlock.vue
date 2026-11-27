<script setup lang="ts">
import BlockShell from './BlockShell.vue'
import { paragraphs } from '@/lib/blocks'

// Wide: statements with an amber rule. Compact: a boxed disclosure.
defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <section v-if="data.layout === 'compact'" :id="data.anchor || undefined" class="mx-auto max-w-6xl scroll-mt-20 px-4 pb-14 sm:px-6">
    <div class="rounded-xl border border-clay/30 bg-clay-soft p-6 md:p-8">
      <h2 v-if="data.heading" class="text-[19px] text-orange-text">{{ data.heading }}</h2>
      <div class="mt-3 max-w-3xl space-y-3 text-[15px] text-orange-text">
        <p v-for="(p, i) in paragraphs(data.body)" :key="i" class="whitespace-pre-line">{{ p }}</p>
      </div>
    </div>
  </section>
  <BlockShell v-else :data="data">
    <div class="max-w-2xl space-y-3 border-l-2 border-orange pl-5 text-[17px] text-label">
      <p v-for="(p, i) in paragraphs(data.body)" :key="i" class="whitespace-pre-line">{{ p }}</p>
    </div>
  </BlockShell>
</template>
