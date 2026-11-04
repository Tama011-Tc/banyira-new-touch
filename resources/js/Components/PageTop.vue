<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

defineProps<{ title: string; sub?: string }>()
</script>

<template>
  <Head :title="title" />
  <div class="mx-auto max-w-6xl px-4 pb-12 pt-14 sm:px-6 md:pt-20">
    <h1 class="max-w-3xl text-[36px] leading-[1.06] md:text-[52px]"><span class="line-mask"><span>{{ title }}</span></span></h1>
    <p v-if="sub" class="rise-in mt-5 max-w-2xl text-[17px] text-muted" style="--d: 120ms">{{ sub }}</p>
    <div v-if="$slots.default" class="rise-in mt-7" style="--d: 200ms"><slot /></div>
  </div>
</template>
