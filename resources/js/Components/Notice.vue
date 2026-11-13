<script setup lang="ts">
import Icon from './Icon.vue'

// Inline alert. The four kinds of feedback: status (info), completion (ok),
// warning (warn / risk) and error.
withDefaults(defineProps<{ tone?: 'info' | 'warn' | 'ok' | 'risk' | 'error'; title?: string }>(), { tone: 'info' })
</script>

<template>
  <div
    :role="tone === 'error' ? 'alert' : tone === 'warn' || tone === 'risk' ? 'note' : undefined"
    class="flex gap-2.5 rounded-lg px-3.5 py-3 text-[14px] leading-snug"
    :class="{ info: 'bg-tint-soft', ok: 'bg-green-soft', warn: 'bg-orange-soft', risk: 'bg-orange-soft', error: 'bg-red-soft' }[tone]"
  >
    <span :class="{ info: 'text-tint-text', ok: 'text-green-text', warn: 'text-orange-text', risk: 'text-orange-text', error: 'text-red-text' }[tone]">
      <Icon :n="tone === 'ok' ? 'ok' : tone === 'info' ? 'info' : tone === 'error' ? 'error' : 'alert'" :size="18" class="mt-px" />
    </span>
    <div class="min-w-0 text-label">
      <p v-if="title" class="font-semibold">{{ title }}</p>
      <div :class="title && 'text-label-2'"><slot /></div>
    </div>
  </div>
</template>
