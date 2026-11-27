<script setup lang="ts">
import Btn from '@/Components/Btn.vue'
import { isInternal } from '@/lib/blocks'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <section v-if="data.panels.length > 1" class="border-t border-line bg-forest text-white">
    <div class="mx-auto grid max-w-6xl md:grid-cols-2">
      <div v-for="(p, i) in data.panels" :key="i" class="px-5 py-16 sm:px-8 md:py-24" :class="Number(i) === 0 ? 'md:border-r md:border-white/15' : 'border-t border-white/15 md:border-t-0 md:pl-12'">
        <h2 class="t-title">{{ p.title }}</h2>
        <p v-if="p.text" class="mt-3 max-w-sm whitespace-pre-line text-white/75">{{ p.text }}</p>
        <div class="mt-7"><Btn :href="p.button_href" :external="!isInternal(p.button_href)" :variant="Number(i) === 0 ? 'light' : 'ghost-light'">{{ p.button_label }}</Btn></div>
      </div>
    </div>
  </section>
  <section v-else-if="data.panels.length" class="border-t border-line bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-20">
      <h2 class="text-[28px] uppercase tracking-tight md:text-[36px]">{{ data.panels[0].title }}</h2>
      <p v-if="data.panels[0].text" class="mt-3 max-w-lg whitespace-pre-line text-muted">{{ data.panels[0].text }}</p>
      <div class="mt-6"><Btn size="lg" :href="data.panels[0].button_href" :external="!isInternal(data.panels[0].button_href)">{{ data.panels[0].button_label }}</Btn></div>
    </div>
  </section>
</template>
