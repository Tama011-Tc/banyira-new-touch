<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

defineProps<{ title: string; sub?: string; back?: { href: string; label: string } }>()
</script>

<template>
  <Head :title="title" />
  <div class="mb-7">
    <Link v-if="back" :href="back.href" class="mb-2 inline-block text-[13px] text-muted hover:text-ink">← {{ back.label }}</Link>
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-[22px]">{{ title }}</h1>
        <p v-if="sub" class="mt-0.5 max-w-2xl text-sm text-muted">{{ sub }}</p>
      </div>
      <div v-if="$slots.default" class="flex flex-wrap gap-2"><slot /></div>
    </div>
  </div>
</template>
