<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Icon from './Icon.vue'

// Toasts enter from where they rest, leave quickly, and pause while hovered
// or while the tab is hidden, so nobody misses one.
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
  <div class="fixed bottom-4 left-4 right-4 z-[70] sm:left-auto" role="status" aria-live="polite" @mouseenter="pause" @mouseleave="resume">
    <TransitionGroup name="toast" tag="div" class="flex flex-col items-end gap-2">
      <div v-for="x in items" :key="x.id" class="flex max-w-sm items-start gap-2.5 rounded-lg py-2.5 pl-3.5 pr-2 text-sm text-white shadow-[0_12px_32px_-8px_rgb(23_25_26/0.4)]" :class="x.ok ? 'bg-ink' : 'bg-danger'">
        <Icon :n="x.ok ? 'check' : 'alert'" :size="16" class="mt-0.5" />
        <span class="flex-1">{{ x.msg }}</span>
        <button type="button" class="press -my-0.5 rounded p-1 text-white/60 hover:text-white" aria-label="Dismiss" @click="remove(x.id)"><Icon n="x" :size="14" /></button>
      </div>
    </TransitionGroup>
  </div>
</template>
