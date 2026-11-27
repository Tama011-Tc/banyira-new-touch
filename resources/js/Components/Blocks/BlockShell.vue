<script setup lang="ts">
import Section from '@/Components/Section.vue'

// The two layouts a section can take: a wide band with a large heading, or a
// compact row with a small heading beside the content.
defineProps<{ data: { heading?: string; intro?: string; layout?: string; shaded?: boolean; anchor?: string } }>()
</script>

<template>
  <Section v-if="data.layout !== 'compact' && data.heading" :id="data.anchor || undefined" class="scroll-mt-16" :title="data.heading" :sub="data.intro || undefined" :tone="data.shaded ? 'white' : 'plain'">
    <slot />
  </Section>
  <section v-else :id="data.anchor || undefined" class="mx-auto max-w-6xl scroll-mt-20 px-4 pb-16 sm:px-6">
    <div v-if="data.heading" class="grid gap-4 border-t border-line pt-10 md:grid-cols-[200px_1fr] md:gap-10">
      <h2 class="text-[17px]">{{ data.heading }}</h2>
      <div>
        <p v-if="data.intro" class="mb-4 max-w-2xl text-muted">{{ data.intro }}</p>
        <slot />
      </div>
    </div>
    <slot v-else />
  </section>
</template>
