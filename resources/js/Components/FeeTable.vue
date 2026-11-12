<script setup lang="ts">
import { mk, months } from '@/lib/format'
import RiskBadge from './RiskBadge.vue'

defineProps<{ rules: { version: string; effective: string; ceiling: string; fees: { upTo: string | null; rate: string }[]; bands: { class: string; label: string; maxAmount: string | null; maxMonths: number | null }[] } }>()
</script>

<template>
  <div>
    <div class="grid gap-4 md:grid-cols-3">
      <section class="surface-card p-5">
        <h3 class="text-[17px] font-semibold tracking-[-0.02em]">Business service fee</h3>
        <p class="mt-1 text-[13px] text-label-2">Charged once on the total financed, deducted when funds are released.</p>
        <dl class="mt-4 divide-y divide-separator text-[15px]">
          <div v-for="(t, i) in rules.fees" :key="i" class="flex items-baseline justify-between gap-3 py-2.5">
            <dt class="text-label-2">{{ t.upTo ? (i === 0 ? 'Below ' : mk(rules.fees[i - 1].upTo) + ' – ') + mk(t.upTo) : mk(rules.fees[i - 1]?.upTo ?? 0) + ' and above' }}</dt>
            <dd class="num font-semibold">{{ Number(t.rate) }}%</dd>
          </div>
        </dl>
      </section>
      <section class="surface-card flex flex-col p-5">
        <h3 class="text-[17px] font-semibold tracking-[-0.02em]">Investor interest ceiling</h3>
        <p class="mt-1 text-[13px] text-label-2">The highest annual rate an investor may offer.</p>
        <p class="num mt-auto pt-6 text-[48px] font-bold leading-none tracking-[-0.04em]">{{ Number(rules.ceiling) }}%<span class="ml-1.5 text-[17px] font-normal tracking-normal text-label-2">a year</span></p>
      </section>
      <section class="surface-card p-5">
        <h3 class="text-[17px] font-semibold tracking-[-0.02em]">Risk-class limits</h3>
        <p class="mt-1 text-[13px] text-label-2">Extra limits by assessed class.</p>
        <dl class="mt-4 divide-y divide-separator text-[15px]">
          <div v-for="b in rules.bands" :key="b.class" class="flex items-center justify-between gap-3 py-2">
            <dt><RiskBadge :r="b.class" /></dt>
            <dd class="num text-right text-label-2">{{ [b.maxAmount && 'max ' + mk(b.maxAmount), b.maxMonths && 'max ' + months(b.maxMonths)].filter(Boolean).join(', ') || 'No extra limits' }}</dd>
          </div>
        </dl>
      </section>
    </div>
    <p class="mt-4 text-[13px] text-label-2">Rules {{ rules.version }}, effective {{ rules.effective }}. Set by Banyira, approved before they change, and may be revised with notice.</p>
  </div>
</template>
