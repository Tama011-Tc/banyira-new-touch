<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageTop from '@/Components/PageTop.vue'
import Section from '@/Components/Section.vue'
import FeeTable from '@/Components/FeeTable.vue'
import { mk, months } from '@/lib/format'

defineProps<{ products: any[]; rules: any }>()
</script>

<template>
  <PublicLayout>
    <PageTop title="Financing for different business needs." sub="Choose the product that matches how you will use the funds. Final amounts, rates and terms depend on your assessment and on investor offers." />
    <div class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
      <div class="grid gap-px overflow-hidden rounded-lg border border-line bg-line sm:grid-cols-2 lg:grid-cols-3">
        <Link v-for="p in products" :key="p.slug" :href="`/products/${p.slug}`" class="group flex flex-col bg-surface p-5 transition-colors duration-200 hover:bg-surface-2">
          <span class="font-medium transition-colors duration-150 group-hover:text-forest">{{ p.name }}</span>
          <span class="mt-1 flex-1 text-sm text-muted">{{ p.summary }}</span>
          <dl class="mt-5 grid grid-cols-2 gap-2 border-t border-line pt-3 text-[13px]">
            <div><dt class="text-muted">Amount</dt><dd class="num">{{ mk(p.min_amount) }} – {{ mk(p.max_amount) }}</dd></div>
            <div><dt class="text-muted">Repayment</dt><dd class="num">{{ p.min_months }} – {{ months(p.max_months) }}</dd></div>
          </dl>
        </Link>
      </div>
    </div>
    <Section title="What it costs, stated plainly."><FeeTable :rules="rules" /></Section>
  </PublicLayout>
</template>
