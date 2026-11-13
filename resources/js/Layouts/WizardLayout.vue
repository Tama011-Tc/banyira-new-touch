<script lang="ts">
// Survives page changes, so the bar continues from where the previous
// sign-up screen left it instead of refilling from zero.
let lastProgress = 0
</script>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Icon from '@/Components/Icon.vue'
import Flash from '@/Components/Flash.vue'

// Sign-up as a sequence of single questions: progress on top, one heading,
// one answer, one button. Enter submits the step; the page decides what
// "next" means.
const props = defineProps<{
  title: string
  step: { key: string; title: string; sub?: string }
  progress: number
  dir?: 'next' | 'back'
  canBack?: boolean
  exit?: { href: string; label: string }
}>()
const emit = defineEmits<{ next: []; back: [] }>()

const shown = ref(lastProgress)
const show = (v: number) => { shown.value = Math.min(100, Math.max(0, v)); lastProgress = shown.value }
watch(() => props.progress, show)

const body = ref<HTMLElement>()
// Keyboard users land in the answer. Touch users see the question first:
// focusing would raise the keyboard over it.
const focusAnswer = (el: Element | undefined) => {
  if (!el || !window.matchMedia('(pointer: fine)').matches) return
  el.querySelector<HTMLElement>('input:checked, input, select, textarea')?.focus({ preventScroll: true })
}
onMounted(() => { requestAnimationFrame(() => show(props.progress)); focusAnswer(body.value) })
</script>

<template>
  <Head :title="title" />
  <form class="wizard flex min-h-dvh flex-col" novalidate @submit.prevent="emit('next')">
    <header class="mx-auto flex h-16 w-full max-w-2xl items-center gap-3 px-4 sm:px-6">
      <button v-if="canBack" type="button" aria-label="Back" class="press -ml-2 flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-muted hover:bg-fill-2 hover:text-ink" @click="emit('back')"><Icon n="back" :size="20" /></button>
      <Link v-else href="/" aria-label="Banyira home" class="press shrink-0"><Logo mark :size="24" /></Link>
      <div role="progressbar" :aria-valuenow="Math.round(shown)" aria-valuemin="0" aria-valuemax="100" aria-label="Sign-up progress" class="h-2 flex-1 overflow-hidden rounded-full bg-fill">
        <div class="bar h-full origin-left rounded-full bg-tint" :style="{ transform: `scaleX(${shown / 100})` }" />
      </div>
      <Link v-if="exit" :href="exit.href" class="press -mr-2 flex h-10 shrink-0 items-center rounded-md px-2 text-[13px] text-muted hover:bg-fill-2 hover:text-ink">{{ exit.label }}</Link>
    </header>

    <main id="main" class="flex flex-1 justify-center px-4 pb-10 pt-6 sm:px-6 sm:pt-14">
      <div ref="body" class="page-in w-full max-w-md">
        <Transition :name="`step-${dir ?? 'next'}`" mode="out-in" @after-enter="focusAnswer">
          <div :key="step.key">
            <h1 class="text-[28px] leading-[1.08] tracking-[-0.035em] sm:text-[34px]">{{ step.title }}</h1>
            <p v-if="step.sub" class="mt-2.5 text-[15px] text-muted">{{ step.sub }}</p>
            <div class="mt-8 space-y-4"><slot /></div>
          </div>
        </Transition>
      </div>
    </main>

    <footer class="safe-bottom sticky bottom-0 border-t border-line bg-bg">
      <div class="mx-auto flex max-w-2xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <div class="min-w-0 text-[13px] text-muted max-sm:hidden"><slot name="aside" /></div>
        <div class="flex max-sm:w-full sm:min-w-44 [&>*]:flex-1"><slot name="footer" /></div>
      </div>
    </footer>
    <Flash />
  </form>
</template>

<style scoped>
.bar { transition: transform 700ms var(--ease-smooth); }
/* Answers are the only thing on the screen, so they are sized for it. */
.wizard :deep(.input) { min-height: 48px; font-size: 16px; padding-left: 0.875rem; padding-right: 0.875rem; }
.wizard :deep(textarea.input) { padding-top: 0.75rem; padding-bottom: 0.75rem; }
@media (prefers-reduced-motion: reduce) { .bar { transition-duration: 1ms; } }
</style>
