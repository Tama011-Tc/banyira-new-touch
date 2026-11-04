<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'

const props = defineProps<{ open: boolean; title: string }>()
const emit = defineEmits<{ close: [] }>()
const panel = ref<HTMLElement>()
let previous: HTMLElement | null = null

const onKey = (e: KeyboardEvent) => {
  if (e.key === 'Escape') emit('close')
  if (e.key === 'Tab' && panel.value) {
    const f = panel.value.querySelectorAll<HTMLElement>('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
    if (!f.length) return
    const first = f[0], last = f[f.length - 1]
    if (e.shiftKey && document.activeElement === first) { last.focus(); e.preventDefault() }
    else if (!e.shiftKey && document.activeElement === last) { first.focus(); e.preventDefault() }
  }
}

watch(() => props.open, async (o) => {
  if (o) {
    previous = document.activeElement as HTMLElement
    document.addEventListener('keydown', onKey)
    document.documentElement.style.overflow = 'hidden'
    await nextTick()
    panel.value?.querySelector<HTMLElement>('input, select, textarea, button')?.focus()
  } else {
    document.removeEventListener('keydown', onKey)
    document.documentElement.style.overflow = ''
    previous?.focus()
  }
}, { immediate: true })
</script>

<template>
  <Teleport to="body">
    <Transition name="backdrop">
      <div v-if="open" class="fixed inset-0 z-[60] bg-ink/35 backdrop-blur-[2px]" @click="emit('close')" />
    </Transition>
    <Transition name="sheet">
      <div v-if="open" class="pointer-events-none fixed inset-0 z-[61] flex items-end justify-center sm:items-center sm:p-4">
        <div ref="panel" role="dialog" aria-modal="true" :aria-label="title" class="pointer-events-auto max-h-[90vh] w-full max-w-md overflow-auto rounded-t-2xl bg-white p-6 shadow-[0_24px_64px_-16px_rgb(23_25_26/0.35)] sm:rounded-xl">
          <div class="mx-auto -mt-2 mb-4 h-1 w-9 rounded-full bg-line-2 sm:hidden" aria-hidden="true" />
          <h2 class="text-lg">{{ title }}</h2>
          <div class="mt-2 text-sm"><slot /></div>
          <div v-if="$slots.actions" class="mt-6 flex flex-wrap justify-end gap-2"><slot name="actions" /></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
