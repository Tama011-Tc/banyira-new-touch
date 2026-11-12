<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Flash from '@/Components/Flash.vue'

defineProps<{ title: string; sub?: string }>()
</script>

<template>
  <Head :title="title" />
  <div class="flex min-h-screen items-start justify-center border-t-[3px] border-clay px-4 pt-16 sm:pt-24">
    <main id="main" class="w-full max-w-sm">
      <div class="flex items-center gap-2"><Logo /><span class="rounded-sm bg-orange-soft px-1.5 py-px font-mono text-[10px] font-semibold uppercase tracking-wider text-orange-text">Internal</span></div>
      <h1 class="mt-8 text-[22px]">{{ title }}</h1>
      <p v-if="sub" class="mt-1 text-sm text-muted">{{ sub }}</p>
      <div class="mt-6"><slot /></div>
      <ul class="mt-10 space-y-1 border-t border-line pt-4 text-xs text-muted">
        <li>Authorised Banyira employees only.</li>
        <li>Sessions end after 15 minutes of inactivity. Every sign-in and action is logged.</li>
      </ul>
    </main>
    <Flash />
  </div>
</template>
