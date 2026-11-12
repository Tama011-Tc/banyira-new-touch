<script setup lang="ts">
// Status label: a coloured dot and text. Tone carries meaning, but the text always says it too.
withDefaults(defineProps<{ tone?: 'gray' | 'blue' | 'green' | 'orange' | 'red'; dot?: boolean }>(), { tone: 'gray' })
</script>

<template>
  <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-[13px] font-medium"
    :class="{ gray: 'text-label-2', blue: 'text-tint-text', green: 'text-green-text', orange: 'text-orange-text', red: 'text-red-text' }[tone]">
    <span v-if="dot" class="h-2 w-2 shrink-0 rounded-full" :class="{ gray: 'bg-label-3', blue: 'bg-tint', green: 'bg-green', orange: 'bg-orange', red: 'bg-red' }[tone]" aria-hidden="true" />
    <slot />
  </span>
</template>
