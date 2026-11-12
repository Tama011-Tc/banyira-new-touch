<script lang="ts">
// Remembers the thumb position per option set, so link-driven controls (which
// remount on navigation) slide from the previous segment instead of jumping.
const last = new Map<string, { x: number; w: number }>()
</script>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps<{ options: { key: string; label: string; href?: string; count?: number }[]; label?: string; full?: boolean }>()
const model = defineModel<string>()
const track = ref<HTMLElement>()
const setKey = props.options.map((o) => o.key).join('|')
const prev = last.get(setKey)
const thumb = ref({ x: prev?.x ?? 0, w: prev?.w ?? 0, ready: false, show: !!prev })

const place = () => {
  const el = track.value?.querySelector<HTMLElement>('[aria-selected="true"]')
  if (!el) { thumb.value.show = false; return }
  const next = { x: el.offsetLeft, w: el.offsetWidth }
  last.set(setKey, next)
  if (!thumb.value.ready && prev) {
    requestAnimationFrame(() => { thumb.value.ready = true; requestAnimationFrame(() => Object.assign(thumb.value, next, { show: true })) })
    return
  }
  Object.assign(thumb.value, next, { show: true })
  requestAnimationFrame(() => (thumb.value.ready = true))
}
watch(() => [model.value, props.options.length], () => nextTick(place))
onMounted(() => { place(); window.addEventListener('resize', place) })
onBeforeUnmount(() => window.removeEventListener('resize', place))

const onKey = (e: KeyboardEvent) => {
  if (!['ArrowLeft', 'ArrowRight'].includes(e.key)) return
  const i = props.options.findIndex((o) => o.key === model.value)
  const n = props.options[(i + (e.key === 'ArrowRight' ? 1 : props.options.length - 1)) % props.options.length]
  if (!n.href) { model.value = n.key; e.preventDefault() }
}
</script>

<template>
  <div class="max-w-full relative overflow-x-auto [scrollbar-width:none]" :class="full ? 'w-full' : 'inline-flex'">
    <div ref="track" role="tablist" :aria-label="label" class="relative inline-flex min-w-full rounded-[11px] bg-fill p-[3px]" @keydown="onKey">
      <span aria-hidden="true" class="absolute bottom-[3px] left-0 top-[3px] rounded-[8px] bg-[var(--seg-thumb)] shadow-[0_3px_8px_rgb(0_0_0/0.08),0_1px_1px_rgb(0_0_0/0.06)]"
        :class="[thumb.ready && 'transition-[transform,width] duration-300 ease-[var(--ease-drawer)]', thumb.show ? 'opacity-100' : 'opacity-0']"
        :style="{ width: thumb.w + 'px', transform: `translateX(${thumb.x}px)` }" />
      <template v-for="o in options" :key="o.key">
        <Link v-if="o.href" :href="o.href" preserve-scroll role="tab" :aria-selected="model === o.key" :tabindex="model === o.key ? 0 : -1"
          class="relative z-[1] flex h-8 flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-[8px] px-3.5 text-[14px] transition-colors duration-200"
          :class="model === o.key ? 'font-semibold text-label' : 'text-label-2 hover:text-label'">
          {{ o.label }}<span v-if="o.count" class="num text-[12px] text-label-2">{{ o.count }}</span>
        </Link>
        <button v-else type="button" role="tab" :aria-selected="model === o.key" :tabindex="model === o.key ? 0 : -1" @click="model = o.key"
          class="relative z-[1] flex h-8 flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-[8px] px-3.5 text-[14px] transition-colors duration-200"
          :class="model === o.key ? 'font-semibold text-label' : 'text-label-2 hover:text-label'">
          {{ o.label }}<span v-if="o.count" class="num text-[12px] text-label-2">{{ o.count }}</span>
        </button>
      </template>
    </div>
  </div>
</template>
