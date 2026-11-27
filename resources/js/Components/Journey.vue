<script setup lang="ts">
// Numbered journey with phases, used by the borrowing and investing guides.
// The numbers carry the sequence, so they stay.
defineProps<{ phases: { title: string; steps: { title: string; text: string }[] }[] }>()
</script>

<template>
  <div class="space-y-10">
    <section v-for="(ph, pi) in phases" :key="pi" class="grid gap-3 md:grid-cols-[220px_1fr] md:gap-10">
      <h2 class="border-t border-label pt-4 text-[20px] font-semibold tracking-[-0.025em]">{{ ph.title }}</h2>
      <ol class="divide-y divide-separator border-y border-separator">
        <li v-for="(step, i) in ph.steps" :key="i" class="grid grid-cols-[2.5rem_1fr] gap-3 py-4">
          <span class="num pt-0.5 text-[13px] text-label-3">{{ String(phases.slice(0, pi).reduce((a, p) => a + p.steps.length, 0) + i + 1).padStart(2, '0') }}</span>
          <div><h3 class="text-[17px] font-semibold tracking-[-0.02em]">{{ step.title }}</h3><p class="mt-0.5 max-w-[60ch] whitespace-pre-line text-[15px] text-label-2">{{ step.text }}</p></div>
        </li>
      </ol>
    </section>
  </div>
</template>
