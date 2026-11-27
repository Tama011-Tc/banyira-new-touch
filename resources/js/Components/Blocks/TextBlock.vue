<script setup lang="ts">
import BlockShell from './BlockShell.vue'
import BlockLinks from './BlockLinks.vue'
import { paragraphs } from '@/lib/blocks'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <BlockShell :data="data">
    <div class="max-w-2xl space-y-4 text-[15px]">
      <p v-for="(p, i) in paragraphs(data.body)" :key="i" class="whitespace-pre-line">{{ p }}</p>
    </div>
    <BlockLinks :links="data.links" />
  </BlockShell>
</template>
