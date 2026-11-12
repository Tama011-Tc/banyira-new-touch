<script setup lang="ts">
import { computed } from 'vue'
import SegmentedControl from './SegmentedControl.vue'
import { setTheme, theme } from '@/lib/theme'

const model = computed({ get: () => theme.value, set: (v) => setTheme(v as 'system' | 'light' | 'dark') })
</script>

<template>
  <SegmentedControl v-model="model" label="Appearance" :options="[{ key: 'system', label: 'Auto' }, { key: 'light', label: 'Light' }, { key: 'dark', label: 'Dark' }]" />
</template>
