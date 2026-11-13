<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

defineProps<{ title: string; sub?: string }>()
</script>

<template>
  <Head :title="title" />
  <div class="mx-auto max-w-6xl px-5 pb-14 pt-16 sm:px-8 md:pb-20 md:pt-28">
    <h1 class="t-display max-w-[16ch]">{{ title }}</h1>
    <p v-if="sub" class="t-lead mt-7 max-w-[56ch]">{{ sub }}</p>
    <div v-if="$slots.default" class="mt-8"><slot /></div>
  </div>
</template>
