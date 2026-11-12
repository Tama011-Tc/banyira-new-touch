<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

defineProps<{ title: string; sub?: string }>()
</script>

<template>
  <Head :title="title" />
  <div class="mx-auto max-w-6xl px-5 pb-12 pt-14 sm:px-8 md:pt-20">
    <h1 class="max-w-3xl text-[40px] font-bold leading-[1.04] tracking-[-0.035em] md:text-[56px]">{{ title }}</h1>
    <p v-if="sub" class="mt-5 max-w-[62ch] text-[19px] leading-[1.42] text-label-2">{{ sub }}</p>
    <div v-if="$slots.default" class="mt-8"><slot /></div>
  </div>
</template>
