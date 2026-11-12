<script setup lang="ts">
// Numbered journey with phases, used by the borrowing and investing guides.
// The numbers carry the sequence, so they stay.
defineProps<{ phases: { title: string; steps: [string, string][] }[] }>()
</script>

<template>
  <div class="space-y-10">
    <section v-for="(ph, pi) in phases" :key="ph.title" class="grid gap-3 md:grid-cols-[220px_1fr] md:gap-10">
      <h2 class="text-[20px] font-semibold tracking-[-0.025em] md:pt-4">{{ ph.title }}</h2>
      <ol class="surface-card divide-y divide-separator overflow-hidden">
        <li v-for="([t, d], i) in ph.steps" :key="t" class="grid grid-cols-[2.25rem_1fr] gap-3 px-5 py-4">
          <span class="num flex h-7 w-7 items-center justify-center rounded-full bg-tint-soft text-[13px] font-semibold text-tint-text">{{ phases.slice(0, pi).reduce((a, p) => a + p.steps.length, 0) + i + 1 }}</span>
          <div><h3 class="text-[17px] font-semibold tracking-[-0.02em]">{{ t }}</h3><p class="mt-0.5 text-[15px] text-label-2">{{ d }}</p></div>
        </li>
      </ol>
    </section>
  </div>
</template>
