<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import BlockShell from './BlockShell.vue'
import { usePageContext } from '@/lib/blocks'
import { mk, mkShort, months } from '@/lib/format'

defineProps<{ data: Record<string, any> }>()
const page = usePageContext()
</script>

<template>
  <BlockShell :data="data">
    <div v-if="data.display === 'cards'" class="grid gap-px overflow-hidden rounded-lg border border-line bg-line sm:grid-cols-2 lg:grid-cols-3">
      <Link v-for="p in page.live.products" :key="p.slug" :href="`/products/${p.slug}`" class="group flex flex-col bg-surface p-5 transition-colors duration-200 hover:bg-surface-2">
        <span class="font-medium transition-colors duration-150 group-hover:text-forest">{{ p.name }}</span>
        <span class="mt-1 flex-1 text-sm text-muted">{{ p.summary }}</span>
        <dl class="mt-5 grid grid-cols-2 gap-2 border-t border-line pt-3 text-[13px]">
          <div><dt class="text-muted">Amount</dt><dd class="num">{{ mk(p.min_amount) }} – {{ mk(p.max_amount) }}</dd></div>
          <div><dt class="text-muted">Repayment</dt><dd class="num">{{ p.min_months }} – {{ months(p.max_months) }}</dd></div>
        </dl>
      </Link>
    </div>
    <div v-else class="divide-y divide-line border-y border-line">
      <Link v-for="p in page.live.products" :key="p.slug" :href="`/products/${p.slug}`" class="group grid gap-1 py-4 sm:grid-cols-[1fr_auto] sm:items-center sm:gap-6">
        <span>
          <span class="font-medium transition-colors duration-150 group-hover:text-forest">{{ p.name }}</span>
          <span class="block text-sm text-muted">{{ p.summary }}</span>
        </span>
        <span class="flex items-center gap-3 text-[13px] text-muted">
          <span class="num">{{ mkShort(p.min_amount) }}–{{ mkShort(p.max_amount) }} · {{ p.min_months }}–{{ months(p.max_months) }}</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="nudge hidden sm:block"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
        </span>
      </Link>
    </div>
  </BlockShell>
</template>

<style scoped>
@media (hover: hover) and (pointer: fine) {
  .nudge { transition: transform 200ms var(--ease-out); }
  .group:hover .nudge { transform: translateX(3px); }
}
</style>
