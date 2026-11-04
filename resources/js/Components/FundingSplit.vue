<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { mk, mkShort } from '@/lib/format'

/*
 * Banyira's signature moment: one request filled by several investors.
 * Each tranche wipes into the bar in turn while the total counts up, then the
 * request settles as fully funded. Plays once, on load or when scrolled into view.
 */
const props = withDefaults(defineProps<{
  total: number
  parts: { who: string; amount: number; rate?: string }[]
  trigger?: 'load' | 'view'
  delay?: number
}>(), { trigger: 'load', delay: 0 })

const COLORS = ['#1d4a37', '#3d6b57', '#5a8572', '#7e9f90']
const STEP = 420          // ms between tranches
const WIPE = 560          // ms per tranche wipe

const root = ref<HTMLElement>()
const filled = ref(-1)    // index of the last tranche that has landed
const shown = ref(0)      // the counting total
const started = ref(false)
const settled = ref(false)  // true once the last tranche has finished landing
const done = computed(() => settled.value)
let raf = 0
let timers: ReturnType<typeof setTimeout>[] = []
let io: IntersectionObserver | null = null

const reduced = () => typeof matchMedia !== 'undefined' && matchMedia('(prefers-reduced-motion: reduce)').matches

const countTo = (target: number, ms: number) => {
  cancelAnimationFrame(raf)
  const from = shown.value, t0 = performance.now()
  const tick = (t: number) => {
    // rAF timestamps can precede t0, so clamp to [0, 1] or the curve overshoots below zero.
    const k = Math.min(1, Math.max(0, (t - t0) / ms))
    shown.value = k === 1 ? target : Math.round(from + (target - from) * (1 - Math.pow(1 - k, 4)))
    if (k < 1) raf = requestAnimationFrame(tick)
  }
  raf = requestAnimationFrame(tick)
}

const play = () => {
  if (started.value) return
  started.value = true
  if (reduced()) { filled.value = props.parts.length - 1; shown.value = props.total; settled.value = true; return }
  let sum = 0
  props.parts.forEach((p, i) => {
    timers.push(setTimeout(() => { filled.value = i; sum += p.amount; countTo(sum, WIPE) }, props.delay + i * STEP))
  })
  // Settle on the exact figure even if animation frames were throttled.
  timers.push(setTimeout(() => { cancelAnimationFrame(raf); shown.value = props.total; settled.value = true }, props.delay + (props.parts.length - 1) * STEP + WIPE + 40))
}

onMounted(() => {
  if (props.trigger === 'load' || !('IntersectionObserver' in window)) return play()
  io = new IntersectionObserver((e) => { if (e[0].isIntersecting) { play(); io?.disconnect() } }, { rootMargin: '0px 0px -15% 0px' })
  io.observe(root.value!)
})
onBeforeUnmount(() => { timers.forEach(clearTimeout); cancelAnimationFrame(raf); io?.disconnect() })

const offset = (i: number) => props.parts.slice(0, i).reduce((a, p) => a + p.amount, 0) / props.total * 100
</script>

<template>
  <div ref="root">
    <div class="flex items-baseline justify-between gap-4">
      <p class="num font-display text-[30px] font-semibold tracking-tight">{{ mk(started ? shown : 0) }}</p>
      <p class="flex items-center gap-1.5 text-[13px] transition-colors duration-300" :class="done ? 'text-ok' : 'text-muted'" aria-live="polite">
        <span class="relative flex h-2 w-2">
          <span v-if="started && !done" class="absolute inset-0 animate-ping rounded-full bg-maize/60" />
          <span class="relative h-2 w-2 rounded-full transition-colors duration-300" :class="done ? 'bg-ok' : 'bg-maize'" />
        </span>
        {{ done ? 'Fully funded' : started ? 'Receiving offers' : 'Listed' }}
      </p>
    </div>

    <div class="relative mt-4 h-9 overflow-hidden rounded-[5px] bg-line/70" role="img" :aria-label="`${mk(total)} funded by ${parts.length} investors: ` + parts.map((p) => `${p.who} ${mk(p.amount)}`).join(', ')">
      <div v-for="(p, i) in parts" :key="p.who" class="tranche absolute inset-y-0 flex items-center justify-center border-r border-white/70 text-[11px] font-medium text-white last:border-r-0"
        :class="{ on: i <= filled }"
        :style="{ left: offset(i) + '%', width: (p.amount / total) * 100 + '%', background: COLORS[i % COLORS.length], '--wipe': WIPE + 'ms' }">
        {{ mkShort(p.amount) }}
      </div>
    </div>

    <ul class="mt-4 space-y-1.5 text-sm">
      <li v-for="(p, i) in parts" :key="p.who" class="row flex items-center gap-2" :class="{ on: i <= filled }">
        <span class="h-2 w-2 rounded-sm" :style="{ background: COLORS[i % COLORS.length] }" />
        <span class="flex-1">{{ p.who }}</span>
        <span v-if="p.rate" class="num text-muted">{{ p.rate }}</span>
        <span class="num w-16 text-right text-muted">{{ mkShort(p.amount) }}</span>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.tranche { clip-path: inset(0 100% 0 0); transition: clip-path var(--wipe) var(--ease-out); }
.tranche.on { clip-path: inset(0 0 0 0); }
.row { opacity: 0.35; transition: opacity 300ms ease; }
.row.on { opacity: 1; }
@media (prefers-reduced-motion: reduce) {
  .tranche { transition: clip-path 1ms; }
}
</style>
