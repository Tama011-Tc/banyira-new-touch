<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageTop from '@/Components/PageTop.vue'
import Btn from '@/Components/Btn.vue'
import FeeTable from '@/Components/FeeTable.vue'
import Notice from '@/Components/Notice.vue'
import { mk, months } from '@/lib/format'

defineProps<{ product: any; others: { slug: string; name: string; summary: string }[]; rules: any }>()
</script>

<template>
  <PublicLayout>
    <PageTop :title="product.name" :sub="product.description">
      <div class="flex flex-wrap gap-3">
        <Btn size="lg" href="/register/business">Apply now</Btn>
        <Btn size="lg" variant="outline" href="/how-to-get-a-loan">How the process works</Btn>
      </div>
    </PageTop>

    <div class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
      <dl class="grid grid-cols-2 gap-x-8 gap-y-5 border-y border-line py-6 md:grid-cols-4">
        <div><dt class="text-[13px] text-muted">Financing limits</dt><dd class="num mt-0.5 font-medium">{{ mk(product.min_amount) }} – {{ mk(product.max_amount) }}</dd></div>
        <div><dt class="text-[13px] text-muted">Repayment period</dt><dd class="num mt-0.5 font-medium">{{ product.min_months }} – {{ months(product.max_months) }}</dd></div>
        <div><dt class="text-[13px] text-muted">Investor rate ceiling</dt><dd class="num mt-0.5 font-medium">{{ Number(rules.ceiling) }}% a year</dd></div>
        <div><dt class="text-[13px] text-muted">Repayments</dt><dd class="mt-0.5 font-medium">Monthly</dd></div>
      </dl>

      <div class="mt-12 grid gap-x-16 gap-y-12 md:grid-cols-2">
        <section><h2 class="text-[17px]">Who it is for</h2><p class="mt-2 text-muted">{{ product.audience }}</p></section>
        <section><h2 class="text-[17px]">Repayment</h2><p class="mt-2 text-muted">{{ product.repayment_info }}</p></section>
        <section>
          <h2 class="text-[17px]">Eligible purposes</h2>
          <ul class="mt-3 divide-y divide-line border-y border-line text-[15px]"><li v-for="x in product.purposes" :key="x" class="py-2.5">{{ x }}</li></ul>
        </section>
        <section>
          <h2 class="text-[17px]">Eligibility</h2>
          <ul class="mt-3 divide-y divide-line border-y border-line text-[15px]"><li v-for="x in product.eligibility" :key="x" class="py-2.5">{{ x }}</li></ul>
        </section>
        <section>
          <h2 class="text-[17px]">What you will need</h2>
          <ul class="mt-3 divide-y divide-line border-y border-line text-[15px]"><li v-for="x in product.requirements" :key="x" class="py-2.5">{{ x }}</li></ul>
        </section>
        <section v-if="product.sectors">
          <h2 class="text-[17px]">Sectors we support</h2>
          <ul class="mt-3 grid grid-cols-2 gap-x-6 text-[15px]"><li v-for="x in product.sectors" :key="x" class="border-t border-line py-2.5">{{ x }}</li></ul>
        </section>
      </div>

      <div class="mt-12"><Notice>Applying does not guarantee approval or funding. Your final amount, rate and term depend on your credit assessment and on the offers investors make.</Notice></div>

      <div class="mt-14 border-t border-line pt-10"><h2 class="mb-6 text-[17px]">Fees and limits</h2><FeeTable :rules="rules" /></div>

      <div class="mt-14 border-t border-line pt-8">
        <h2 class="text-[17px]">Other products</h2>
        <ul class="mt-3 grid gap-x-8 sm:grid-cols-2">
          <li v-for="o in others" :key="o.slug" class="border-b border-line py-3"><Link :href="`/products/${o.slug}`" class="hover:underline">{{ o.name }}</Link><span class="block text-[13px] text-muted">{{ o.summary }}</span></li>
        </ul>
      </div>
      <div class="mt-10"><Btn size="lg" href="/register/business">Apply now</Btn></div>
    </div>
  </PublicLayout>
</template>
