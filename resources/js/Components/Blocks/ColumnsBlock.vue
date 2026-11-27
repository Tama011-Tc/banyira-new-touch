<script setup lang="ts">
import BlockShell from './BlockShell.vue'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <BlockShell :data="data">
    <dl class="grid gap-8 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
      <div v-for="(item, i) in data.items" :key="i">
        <dt class="font-display text-[17px] font-medium">{{ item.title }}</dt>
        <dd class="mt-2 whitespace-pre-line text-muted">{{ item.text }}</dd>
      </div>
    </dl>
  </BlockShell>
</template>
