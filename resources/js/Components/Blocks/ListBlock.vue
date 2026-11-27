<script setup lang="ts">
import BlockShell from './BlockShell.vue'
import BlockLinks from './BlockLinks.vue'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <BlockShell :data="data">
    <ul class="text-[15px]" :class="data.columns === '2' ? 'grid grid-cols-2 gap-x-8' : 'max-w-2xl divide-y divide-line border-y border-line'">
      <li v-for="(item, i) in data.items" :key="i" :class="data.columns === '2' ? 'border-t border-line py-3' : 'py-3.5'">
        <span :class="item.text && 'font-medium'">{{ item.title }}</span>
        <span v-if="item.text" class="block whitespace-pre-line text-sm text-muted">{{ item.text }}</span>
      </li>
    </ul>
    <BlockLinks :links="data.links" />
  </BlockShell>
</template>
