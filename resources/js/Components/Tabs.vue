<script lang="ts">
// Last underline position per tab set, kept across page loads for link tabs.
const last = new Map<string, { x: number; w: number }>()
</script>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'

// Link tabs (href) keep state in the URL; button tabs use v-model.
// A single underline slides between tabs so the change of view is legible.
const props = defineProps<{ tabs: { key: string; label: string; href?: string; count?: number }[] }>()
const model = defineModel<string>()
const list = ref<HTMLElement>()
const setKey = props.tabs.map((t) => t.key).join('|')
const prev = last.get(setKey)
const bar = ref({ x: prev?.x ?? 0, w: prev?.w ?? 0, ready: false })

const measure = () => {
  const el = list.value?.querySelector<HTMLElement>('[aria-selected="true"]')
  if (!el) return
  const next = { x: el.offsetLeft, w: el.offsetWidth }
  last.set(setKey, next)
  if (!bar.value.ready && prev) {
    requestAnimationFrame(() => { bar.value.ready = true; requestAnimationFrame(() => Object.assign(bar.value, next)) })
    return
  }
  Object.assign(bar.value, next)
  requestAnimationFrame(() => (bar.value.ready = true))
}
watch(() => [model.value, props.tabs.length], () => nextTick(measure))
onMounted(() => { measure(); window.addEventListener('resize', measure) })
onBeforeUnmount(() => window.removeEventListener('resize', measure))
</script>

<template>
  <div ref="list" role="tablist" class="relative flex gap-5 overflow-x-auto border-b border-line">
    <template v-for="t in tabs" :key="t.key">
      <Link v-if="t.href" :href="t.href" preserve-scroll role="tab" :aria-selected="model === t.key"
        class="flex h-10 items-center gap-1.5 whitespace-nowrap text-sm transition-colors duration-150"
        :class="model === t.key ? 'font-medium text-ink' : 'text-muted hover:text-ink'">
        {{ t.label }}<span v-if="t.count" class="num text-xs text-muted">{{ t.count }}</span>
      </Link>
      <button v-else type="button" role="tab" :aria-selected="model === t.key" @click="model = t.key"
        class="flex h-10 items-center gap-1.5 whitespace-nowrap text-sm transition-colors duration-150"
        :class="model === t.key ? 'font-medium text-ink' : 'text-muted hover:text-ink'">
        {{ t.label }}<span v-if="t.count" class="num text-xs text-muted">{{ t.count }}</span>
      </button>
    </template>
    <span aria-hidden="true" class="pointer-events-none absolute bottom-0 left-0 h-[2px] rounded-full bg-ink"
      :class="bar.ready && 'transition-[transform,width] duration-300 ease-[var(--ease-out)]'"
      :style="{ width: bar.w + 'px', transform: `translateX(${bar.x}px)` }" />
  </div>
</template>
