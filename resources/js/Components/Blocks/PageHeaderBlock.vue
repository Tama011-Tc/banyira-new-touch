<script setup lang="ts">
import PageTop from '@/Components/PageTop.vue'
import BlockButtons from './BlockButtons.vue'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <PageTop :title="data.title" :sub="data.subtitle || undefined" :head="false">
    <BlockButtons v-if="data.buttons.length" :buttons="data.buttons" />
  </PageTop>
</template>
