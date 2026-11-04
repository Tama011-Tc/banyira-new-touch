<script setup lang="ts">
withDefaults(defineProps<{ tone?: 'info' | 'warn' | 'ok' | 'risk' | 'error'; title?: string }>(), { tone: 'info' })
</script>

<template>
  <div
    :role="tone === 'error' ? 'alert' : tone === 'warn' || tone === 'risk' ? 'note' : undefined"
    class="fade-in flex gap-3 rounded-lg border px-4 py-3 text-sm"
    :class="{
      info: 'border-skyink/15 bg-sky text-skyink',
      ok: 'border-ok/20 bg-ok-soft text-ok',
      warn: 'border-maize/25 bg-maize-soft text-[#6b4208]',
      risk: 'border-clay/25 bg-clay-soft text-[#5e2611]',
      error: 'border-danger/25 bg-danger-soft text-danger',
    }[tone]"
  >
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" class="mt-0.5 shrink-0">
      <path v-if="tone === 'ok'" d="M5 12l5 5 9-10" />
      <template v-else-if="tone === 'info'"><circle cx="12" cy="12" r="9" /><path d="M12 11v6M12 7.5h.01" /></template>
      <path v-else d="M12 3l10 18H2zM12 10v5M12 18h.01" />
    </svg>
    <div class="min-w-0">
      <p v-if="title" class="font-semibold">{{ title }}</p>
      <div><slot /></div>
    </div>
  </div>
</template>
