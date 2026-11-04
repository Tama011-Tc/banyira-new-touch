<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Flash from '@/Components/Flash.vue'

defineProps<{ title: string; sub?: string; back?: string; step?: string }>()
</script>

<template>
  <Head :title="title" />
  <div class="flex min-h-screen flex-col">
    <header class="mx-auto flex h-14 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
      <Link href="/" aria-label="Banyira home"><Logo /></Link>
      <Link v-if="back" :href="back" class="text-[13px] text-muted hover:text-ink">← Back</Link>
    </header>
    <main id="main" class="flex flex-1 justify-center px-4 pb-16 pt-8 sm:pt-16">
      <div class="w-full max-w-sm">
        <p v-if="step" class="eyebrow mb-2">{{ step }}</p>
        <h1 class="text-[24px]">{{ title }}</h1>
        <p v-if="sub" class="mt-1.5 text-sm text-muted">{{ sub }}</p>
        <div class="mt-7"><slot /></div>
      </div>
    </main>
    <p class="px-4 pb-6 text-center text-xs text-muted">Investing involves risk, including loss of capital.</p>
    <Flash />
  </div>
</template>
