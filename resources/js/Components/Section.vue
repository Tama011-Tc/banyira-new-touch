<script setup lang="ts">
// Public-site section, opened by a hairline rule. "split" sets the heading
// beside the content; "stack" sets it above. Headings are always left-aligned.
withDefaults(defineProps<{ id?: string; title: string; sub?: string; tone?: 'plain' | 'white'; layout?: 'split' | 'stack' }>(), { layout: 'split' })
</script>

<template>
  <section :id="id" :class="tone === 'white' ? 'bg-surface' : ''">
    <div class="mx-auto max-w-6xl px-5 sm:px-8">
      <div class="border-t border-separator-2 pb-20 pt-8 md:pb-32 md:pt-10">
        <div v-if="layout === 'split'" class="grid gap-x-16 gap-y-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
          <div>
            <h2 class="t-title max-w-[14ch]">{{ title }}</h2>
            <p v-if="sub" class="mt-5 max-w-md text-[17px] text-label-2">{{ sub }}</p>
          </div>
          <div class="lg:pt-2"><slot /></div>
        </div>
        <template v-else>
          <h2 class="t-title max-w-[20ch]">{{ title }}</h2>
          <p v-if="sub" class="t-lead mt-5 max-w-[56ch]">{{ sub }}</p>
          <div class="mt-12"><slot /></div>
        </template>
      </div>
    </div>
  </section>
</template>
