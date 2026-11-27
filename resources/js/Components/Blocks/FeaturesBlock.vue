<script setup lang="ts">
import BlockShell from './BlockShell.vue'
import Icon from '@/Components/Icon.vue'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <BlockShell :data="data">
    <ul class="grid gap-x-10 gap-y-8 sm:grid-cols-2">
      <li v-for="(item, i) in data.items" :key="i" class="border-t border-line pt-4">
        <span class="flex h-9 w-9 items-center justify-center rounded-control bg-tint-soft text-tint-text" aria-hidden="true"><Icon :n="item.icon" :size="18" /></span>
        <h3 class="mt-4 text-[17px]">{{ item.title }}</h3>
        <p class="mt-1 whitespace-pre-line text-sm text-muted">{{ item.text }}</p>
      </li>
    </ul>
  </BlockShell>
</template>
