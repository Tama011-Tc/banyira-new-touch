<script setup lang="ts">
// Public-site section. The heading carries the section; no kicker labels.
defineProps<{ id?: string; title: string; sub?: string; tone?: 'plain' | 'white' }>()
</script>

<template>
  <section :id="id" class="border-t border-line" :class="tone === 'white' && 'bg-white'">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-24">
      <div class="grid gap-x-16 gap-y-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <div>
          <h2 class="max-w-md text-[28px] leading-[1.12] md:text-[36px]">{{ title }}</h2>
          <p v-if="sub" class="mt-4 max-w-md text-muted">{{ sub }}</p>
        </div>
        <div class="lg:pt-2"><slot /></div>
      </div>
    </div>
  </section>
</template>
