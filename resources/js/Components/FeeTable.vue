<script setup lang="ts">
import { mk, months } from '@/lib/format'
import RiskBadge from './RiskBadge.vue'

defineProps<{ rules: { version: string; effective: string; ceiling: string; fees: { upTo: string | null; rate: string }[]; bands: { class: string; label: string; maxAmount: string | null; maxMonths: number | null }[] } }>()
</script>

<template>
  <div class="grid gap-8 md:grid-cols-3">
    <div>
      <h3 class="text-[15px]">Business service fee</h3>
      <p class="mt-1 text-[13px] text-muted">Charged once on the total amount financed and deducted when funds are released.</p>
      <table class="mt-3 w-full text-sm">
        <tbody>
          <tr v-for="(t, i) in rules.fees" :key="i" class="border-t border-line">
            <td class="py-2">{{ t.upTo ? (i === 0 ? 'Below ' : mk(rules.fees[i - 1].upTo) + ' to below ') + mk(t.upTo) : mk(rules.fees[i - 1]?.upTo ?? 0) + ' and above' }}</td>
            <td class="num py-2 text-right font-medium">{{ Number(t.rate) }}%</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div>
      <h3 class="text-[15px]">Investor interest ceiling</h3>
      <p class="mt-1 text-[13px] text-muted">The highest annual rate an investor may offer.</p>
      <p class="num mt-3 text-[28px] font-semibold tracking-tight">{{ Number(rules.ceiling) }}% <span class="text-sm font-normal text-muted">a year</span></p>
    </div>
    <div>
      <h3 class="text-[15px]">Risk-class limits</h3>
      <p class="mt-1 text-[13px] text-muted">Additional limits by assessed class.</p>
      <table class="mt-3 w-full text-sm">
        <tbody>
          <tr v-for="b in rules.bands" :key="b.class" class="border-t border-line">
            <td class="py-2"><RiskBadge :r="b.class" /></td>
            <td class="num py-2 text-right text-muted">{{ [b.maxAmount && 'max ' + mk(b.maxAmount), b.maxMonths && 'max ' + months(b.maxMonths)].filter(Boolean).join(', ') || 'No additional limits' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p class="text-xs text-muted md:col-span-3">Rules {{ rules.version }}, effective {{ rules.effective }}. Fees and limits are set by Banyira, approved before they change, and may be revised with notice.</p>
  </div>
</template>
