<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'

/*
 * Modal on desktop, bottom sheet on phones. The sheet follows the finger 1:1
 * from where it was grabbed, rubber-bands if pulled up, dismisses on a flick
 * or a long enough drag (decided by velocity, not only distance), and
 * otherwise springs home from wherever it was released.
 */
const props = defineProps<{ open: boolean; title: string }>()
const emit = defineEmits<{ close: [] }>()
const panel = ref<HTMLElement>()
const dragY = ref(0)
const dragging = ref(false)
let previous: HTMLElement | null = null
let startY = 0
let samples: { y: number; t: number }[] = []

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
    dragY.value = 0
    previous = document.activeElement as HTMLElement
    document.addEventListener('keydown', onKey)
    document.documentElement.style.overflow = 'hidden'
    await nextTick()
    panel.value?.querySelector<HTMLElement>('input, select, textarea, button')?.focus({ preventScroll: true })
  } else {
    document.removeEventListener('keydown', onKey)
    document.documentElement.style.overflow = ''
    previous?.focus()
  }
}, { immediate: true })

const rubber = (d: number) => -((Math.abs(d) * 0.55 * 200) / (200 + 0.55 * Math.abs(d)))
const isSheet = () => window.matchMedia('(max-width: 639px)').matches

const down = (e: PointerEvent) => {
  if (!isSheet() || e.button !== 0) return
  dragging.value = true
  startY = e.clientY - dragY.value
  samples = [{ y: e.clientY, t: e.timeStamp }]
  ;(e.currentTarget as HTMLElement).setPointerCapture(e.pointerId)
}
const move = (e: PointerEvent) => {
  if (!dragging.value) return
  const d = e.clientY - startY
  dragY.value = d < 0 ? rubber(d) : d
  samples.push({ y: e.clientY, t: e.timeStamp })
  if (samples.length > 5) samples.shift()
}
const up = () => {
  if (!dragging.value) return
  dragging.value = false
  const a = samples[0], b = samples[samples.length - 1]
  const v = b && a && b.t > a.t ? (b.y - a.y) / (b.t - a.t) : 0       // px per ms
  const h = panel.value?.offsetHeight ?? 400
  if (v > 0.5 || dragY.value > h * 0.35) emit('close')
  else dragY.value = 0
}
</script>

<template>
  <Teleport to="body">
    <Transition name="backdrop">
      <div v-if="open" class="fixed inset-0 z-[60] bg-scrim" @click="emit('close')" />
    </Transition>
    <Transition name="sheet">
      <div v-if="open" class="pointer-events-none fixed inset-0 z-[61] flex items-end justify-center sm:items-center sm:p-6">
        <div ref="panel" role="dialog" aria-modal="true" :aria-label="title"
          class="pointer-events-auto flex max-h-[92vh] w-full max-w-lg flex-col rounded-t-[var(--radius-sheet)] border border-separator bg-surface shadow-float sm:rounded-[var(--radius-sheet)]"
          :style="{ transform: dragY ? `translateY(${dragY}px)` : undefined, transition: dragging ? 'none' : 'transform 380ms var(--ease-drawer)' }">
          <div class="flex cursor-grab touch-none justify-center pb-1 pt-2.5 active:cursor-grabbing sm:hidden" @pointerdown="down" @pointermove="move" @pointerup="up" @pointercancel="up" aria-hidden="true">
            <span class="h-[5px] w-9 rounded-full bg-separator-2" />
          </div>
          <div class="overflow-y-auto px-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] pt-3 sm:pb-6 sm:pt-6">
            <h2 class="text-[20px] font-semibold tracking-[-0.025em]">{{ title }}</h2>
            <div class="mt-2 text-[15px]"><slot /></div>
            <div v-if="$slots.actions" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end [&>*]:w-full sm:[&>*]:w-auto"><slot name="actions" /></div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
