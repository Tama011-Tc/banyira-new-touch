<script setup lang="ts">
import { computed } from 'vue'
import BlockShell from './BlockShell.vue'
import Faq from '@/Components/Faq.vue'

const props = defineProps<{ data: Record<string, any> }>()
const items = computed<[string, string][]>(() => props.data.items.map((i: { question: string; answer: string }) => [i.question, i.answer]))
</script>

<template>
  <BlockShell :data="data">
    <Faq :items="items" />
  </BlockShell>
</template>
