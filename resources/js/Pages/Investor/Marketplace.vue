<script setup lang="ts">
import { reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Progress from '@/Components/Progress.vue'
import Pagination from '@/Components/Pagination.vue'
import Icon from '@/Components/Icon.vue'
import { mk, months } from '@/lib/format'

const props = defineProps<{ listings: any; filters: Record<string, string>; options: { industries: string[]; regions: string[] } }>()
const f = reactive({ q: '', industry: '', region: '', risk: '', amount: '', term: '', sort: 'newest', ...props.filters })
let t: ReturnType<typeof setTimeout>
watch(f, () => {
  clearTimeout(t)
  t = setTimeout(() => router.get('/investor/marketplace', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true }), 300)
})
const clear = () => Object.assign(f, { q: '', industry: '', region: '', risk: '', amount: '', term: '', sort: 'newest' })
</script>

<template>
  <InvestorLayout>
    <PageHead title="Marketplace" sub="Businesses assessed and approved by Banyira, currently accepting offers." />

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(5,1fr)]">
      <div class="relative sm:col-span-2 lg:col-span-1">
        <span class="absolute left-3 top-3 text-muted"><Icon n="search" :size="16" /></span>
        <input v-model="f.q" class="input pl-9" placeholder="Search name or purpose" aria-label="Search" />
      </div>
      <select v-model="f.industry" class="input" aria-label="Industry"><option value="">All industries</option><option v-for="i in options.industries" :key="i">{{ i }}</option></select>
      <select v-model="f.region" class="input" aria-label="Location"><option value="">All regions</option><option v-for="r in options.regions" :key="r">{{ r }}</option></select>
      <select v-model="f.risk" class="input" aria-label="Risk class"><option value="">All classes</option><option v-for="r in ['A+', 'A', 'B', 'C']" :key="r" :value="r">Class {{ r }}</option></select>
      <select v-model="f.amount" class="input" aria-label="Amount"><option value="">Any amount</option><option value="under1m">Under MK1m</option><option value="1to5m">MK1m – 5m</option><option value="over5m">Over MK5m</option></select>
      <select v-model="f.term" class="input" aria-label="Repayment period"><option value="">Any period</option><option value="upto12">Up to 12 months</option><option value="13to24">13 – 24 months</option><option value="over24">Over 24 months</option></select>
    </div>
    <div class="mt-3 flex items-center justify-between text-[13px] text-muted">
      <span>{{ listings.meta?.total ?? listings.data.length }} businesses</span>
      <span class="flex items-center gap-3">
        <button v-if="Object.entries(f).some(([k, v]) => k !== 'sort' && v)" type="button" class="link" @click="clear">Clear filters</button>
        <select v-model="f.sort" class="input h-8 w-auto text-[13px]" aria-label="Sort"><option value="newest">Newest</option><option value="remaining">Most remaining</option><option value="rate">Highest indicative rate</option><option value="term">Shortest period</option></select>
      </span>
    </div>

    <div v-if="!listings.data.length" class="mt-6 rounded-lg border border-dashed border-line-2 px-6 py-12 text-center text-sm text-muted">No businesses match these filters.</div>
    <ul class="mt-4 divide-y divide-line rounded-lg border border-line bg-surface">
      <li v-for="l in listings.data" :key="l.id">
        <Link :href="`/investor/marketplace/${l.id}`" class="grid gap-4 px-5 py-5 transition-colors duration-200 hover:bg-fill-2 md:grid-cols-[1.6fr_1fr_auto] md:items-center">
          <div>
            <div class="flex items-center gap-3"><span class="font-medium">{{ l.name }}</span><RiskBadge :r="l.risk" /></div>
            <p class="text-[13px] text-muted">{{ l.industry }} · {{ l.region }}</p>
            <p class="mt-2 line-clamp-2 text-sm">{{ l.purpose }}</p>
          </div>
          <div>
            <div class="flex justify-between text-[13px]"><span class="num">{{ mk(l.funded) }}</span><span class="num text-muted">of {{ mk(l.requested) }}</span></div>
            <div class="mt-1.5"><Progress :value="l.progress" :label="`${l.name} funded`" /></div>
            <p class="num mt-1.5 text-[13px] text-muted">{{ mk(l.remaining) }} remaining</p>
          </div>
          <dl class="grid grid-cols-2 gap-x-6 text-sm md:text-right">
            <div><dt class="text-xs text-muted">Indicative rate</dt><dd class="num">{{ Number(l.rate) }}%</dd></div>
            <div><dt class="text-xs text-muted">Period</dt><dd>{{ months(l.term) }}</dd></div>
          </dl>
        </Link>
      </li>
    </ul>
    <Pagination v-if="listings.meta" :page="{ ...listings.meta, links: listings.meta.links ?? [] }" />
    <p class="mt-4 text-xs text-muted">Risk classes are Banyira's assessments, not guarantees. Indicative rates are a guide; you propose your own rate.</p>
  </InvestorLayout>
</template>
