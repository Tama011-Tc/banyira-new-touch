<script setup lang="ts">
import { paragraphs } from '@/lib/blocks'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-12 px-4 pb-20 sm:px-6">
    <section v-for="(item, i) in data.items" :id="item.anchor || undefined" :key="i" class="scroll-mt-20">
      <h2 class="text-[19px]">{{ item.title }}</h2>
      <p v-for="(p, pi) in paragraphs(item.body)" :key="pi" class="mt-2 whitespace-pre-line text-muted">{{ p }}</p>
    </section>
  </div>
</template>
