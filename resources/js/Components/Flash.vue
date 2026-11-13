<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Icon from './Icon.vue'

// Toasts: banners at the top on phones (clear of the tab bar), bottom
// right on desktop. They pause while hovered or while the tab is hidden.
type Toast = { id: number; msg: string; ok: boolean; left: number; started: number }
const page = usePage()
const items = ref<Toast[]>([])
const timers = new Map<number, ReturnType<typeof setTimeout>>()
const LIFE = 5000

const remove = (id: number) => { clearTimeout(timers.get(id)); timers.delete(id); items.value = items.value.filter((x) => x.id !== id) }
const run = (t: Toast) => { t.started = Date.now(); timers.set(t.id, setTimeout(() => remove(t.id), t.left)) }
const pause = () => items.value.forEach((t) => { clearTimeout(timers.get(t.id)); t.left = Math.max(800, t.left - (Date.now() - t.started)) })
const resume = () => items.value.forEach(run)
const onVis = () => (document.hidden ? pause() : resume())

watch(() => page.props.flash, (f) => {
  for (const [msg, ok] of [[f?.success, true], [f?.error, false]] as const) {
    if (!msg) continue
    const t: Toast = { id: Date.now() + Math.random(), msg, ok, left: LIFE, started: 0 }
    items.value.push(t)
    run(t)
  }
}, { immediate: true, deep: true })

onMounted(() => document.addEventListener('visibilitychange', onVis))
onBeforeUnmount(() => { document.removeEventListener('visibilitychange', onVis); timers.forEach(clearTimeout) })
</script>

<template>
  <div class="pointer-events-none fixed inset-x-3 top-[calc(0.75rem+env(safe-area-inset-top))] z-[70] sm:inset-x-auto sm:bottom-6 sm:right-6 sm:top-auto" role="status" aria-live="polite">
    <TransitionGroup name="toast" tag="div" class="flex flex-col items-center gap-2 sm:items-end" @mouseenter="pause" @mouseleave="resume">
      <div v-for="x in items" :key="x.id" class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-separator-2 bg-surface py-2.5 pl-3.5 pr-1.5 text-[14px]">
        <span :class="x.ok ? 'text-green-text' : 'text-red-text'"><Icon :n="x.ok ? 'ok' : 'error'" :size="18" class="mt-px" /></span>
        <span class="flex-1 text-label">{{ x.msg }}</span>
        <button type="button" class="press -my-1 flex h-8 w-8 items-center justify-center rounded-md text-label-2 hover:bg-fill-2" aria-label="Dismiss" @click="remove(x.id)"><Icon n="x" :size="16" /></button>
      </div>
    </TransitionGroup>
  </div>
</template>
