<script setup lang="ts">
import SegmentedControl from './SegmentedControl.vue'

// Tabs are a segmented control: one shape for switching views everywhere.
defineProps<{ tabs: { key: string; label: string; href?: string; count?: number }[] }>()
const model = defineModel<string>()
</script>

<template>
  <SegmentedControl v-model="model" :options="tabs" />
</template>
