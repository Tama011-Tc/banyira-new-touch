<script setup lang="ts">
import Icon from './Icon.vue'

// A square button showing only an icon. The label is read aloud and shown on hover.
defineProps<{ icon: string; label: string; disabled?: boolean }>()
</script>

<template>
  <button type="button" class="press flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-label-2 hover:bg-fill-2 hover:text-label disabled:pointer-events-none disabled:opacity-30"
    :aria-label="label" :title="label" :disabled="disabled">
    <Icon :n="icon" :size="16" />
  </button>
</template>
