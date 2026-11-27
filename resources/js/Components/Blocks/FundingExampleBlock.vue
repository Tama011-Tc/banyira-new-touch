<script setup lang="ts">
import BlockShell from './BlockShell.vue'
import FundingSplit from '@/Components/FundingSplit.vue'
import { EXAMPLE_PARTS, EXAMPLE_TOTAL } from '@/lib/example'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <BlockShell :data="data">
    <h3 v-if="data.title" class="text-[19px]">{{ data.title }}</h3>
    <p v-if="data.body" class="mt-2 max-w-2xl whitespace-pre-line text-muted">{{ data.body }}</p>
    <div class="mt-8 max-w-xl rounded-xl border border-separator bg-surface p-6">
      <FundingSplit :total="EXAMPLE_TOTAL" :parts="EXAMPLE_PARTS" />
      <p class="mt-4 border-t border-line pt-3 text-xs text-muted">{{ data.caption }}</p>
    </div>
  </BlockShell>
</template>
