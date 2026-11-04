<script setup lang="ts">
// Numbered journey with phases, used by the borrowing and investing guides.
defineProps<{ phases: { title: string; steps: [string, string][] }[] }>()
</script>

<template>
  <div class="space-y-12">
    <section v-for="(ph, pi) in phases" :key="ph.title" class="grid gap-4 md:grid-cols-[200px_1fr] md:gap-10">
      <h2 class="text-[17px] md:pt-3">{{ ph.title }}</h2>
      <ol class="divide-y divide-line border-y border-line">
        <li v-for="([t, d], i) in ph.steps" :key="t" class="grid grid-cols-[2.5rem_1fr] gap-2 py-4">
          <span class="num text-[13px] text-muted">{{ String(phases.slice(0, pi).reduce((a, p) => a + p.steps.length, 0) + i + 1).padStart(2, '0') }}</span>
          <div><h3 class="text-[15px]">{{ t }}</h3><p class="mt-0.5 text-sm text-muted">{{ d }}</p></div>
        </li>
      </ol>
    </section>
  </div>
</template>
