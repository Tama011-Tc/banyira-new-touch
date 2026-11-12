<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'
import { chrome } from '@/lib/chrome'

// Large title. When it scrolls under the top bar, the bar takes the title.
const props = defineProps<{ title: string; sub?: string; back?: { href: string; label: string } }>()
const el = ref<HTMLElement>()
let io: IntersectionObserver | null = null

watch(() => props.title, (t) => (chrome.title = t), { immediate: true })
onMounted(() => {
  chrome.collapsed = false
  io = new IntersectionObserver(([e]) => (chrome.collapsed = !e.isIntersecting), { rootMargin: '-56px 0px 0px 0px' })
  if (el.value) io.observe(el.value)
})
onBeforeUnmount(() => { io?.disconnect(); chrome.collapsed = false })
</script>

<template>
  <Head :title="title" />
  <div class="mb-6 sm:mb-8">
    <Link v-if="back" :href="back.href" class="press -ml-1.5 mb-2 inline-flex h-9 items-center gap-0.5 rounded-lg pl-0.5 pr-2.5 text-[15px] text-tint-text hover:bg-fill-2">
      <Icon n="chevleft" :size="20" />{{ back.label }}
    </Link>
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div class="min-w-0">
        <h1 ref="el" class="text-[28px] leading-[1.1] tracking-[-0.03em] sm:text-[34px]">{{ title }}</h1>
        <p v-if="sub" class="mt-1.5 max-w-2xl text-[15px] text-label-2">{{ sub }}</p>
      </div>
      <div v-if="$slots.default" class="flex flex-wrap gap-2"><slot /></div>
    </div>
  </div>
</template>
