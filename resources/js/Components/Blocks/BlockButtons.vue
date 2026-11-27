<script setup lang="ts">
import Btn from '@/Components/Btn.vue'
import { isInternal } from '@/lib/blocks'

// The first button is the main action; any others are outlined.
defineProps<{ buttons: { label: string; href: string }[] }>()
</script>

<template>
  <div v-if="buttons.length" class="flex flex-wrap gap-3">
    <Btn v-for="(b, i) in buttons" :key="i" size="lg" :variant="i === 0 ? 'primary' : 'outline'" :href="b.href" :external="!isInternal(b.href)">{{ b.label }}</Btn>
  </div>
</template>
