<script setup lang="ts">
// Public-site section. "split" puts the heading beside the content; "stack"
// puts a centred heading above it. Mixing the two gives the page rhythm.
withDefaults(defineProps<{ id?: string; title: string; sub?: string; tone?: 'plain' | 'white'; layout?: 'split' | 'stack' }>(), { layout: 'split' })
</script>

<template>
  <section :id="id" :class="tone === 'white' ? 'bg-surface' : ''">
    <div class="mx-auto max-w-6xl px-5 py-16 sm:px-8 md:py-24">
      <div v-if="layout === 'split'" class="grid gap-x-16 gap-y-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <div>
          <h2 class="max-w-md text-[30px] font-bold leading-[1.08] tracking-[-0.03em] md:text-[40px]">{{ title }}</h2>
          <p v-if="sub" class="mt-4 max-w-md text-[17px] text-label-2">{{ sub }}</p>
        </div>
        <div class="lg:pt-2"><slot /></div>
      </div>
      <template v-else>
        <div class="mx-auto max-w-2xl text-center">
          <h2 class="text-[30px] font-bold leading-[1.08] tracking-[-0.03em] md:text-[44px]">{{ title }}</h2>
          <p v-if="sub" class="mt-4 text-[19px] text-label-2">{{ sub }}</p>
        </div>
        <div class="mt-12"><slot /></div>
      </template>
    </div>
  </section>
</template>
