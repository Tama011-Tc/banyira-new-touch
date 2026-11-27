<script setup lang="ts">
import BlockShell from './BlockShell.vue'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <BlockShell :data="data">
    <ol class="grid gap-x-8 gap-y-7 sm:grid-cols-2">
      <li v-for="(item, i) in data.items" :key="i" class="grid grid-cols-[2rem_1fr] border-t border-line pt-4">
        <span class="num pt-px text-[13px] text-label-3">{{ String(Number(i) + 1).padStart(2, '0') }}</span>
        <div><h3 class="text-[17px]">{{ item.title }}</h3><p class="mt-1 whitespace-pre-line text-sm text-muted">{{ item.text }}</p></div>
      </li>
    </ol>
  </BlockShell>
</template>
