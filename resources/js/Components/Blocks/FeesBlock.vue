<script setup lang="ts">
import BlockShell from './BlockShell.vue'
import FeeTable from '@/Components/FeeTable.vue'
import { usePageContext } from '@/lib/blocks'

defineProps<{ data: Record<string, any> }>()
const page = usePageContext()
</script>

<template>
  <BlockShell :data="data">
    <FeeTable :rules="page.live.rules" />
  </BlockShell>
</template>
