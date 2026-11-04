<script setup lang="ts">
import { computed, ref } from 'vue'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Btn from '@/Components/Btn.vue'
import { label, mk } from '@/lib/format'

const props = defineProps<{ balances: any[]; periods: string[] }>()
const month = ref(props.periods[0])
const revenue = computed(() => props.balances.filter((b) => b.type === 'revenue'))
const expenses = computed(() => props.balances.filter((b) => b.type === 'expense'))
const sum = (rows: any[]) => rows.reduce((a, b) => a + +b.balance, 0)
const client = computed(() => props.balances.filter((b) => b.client))
</script>

<template>
  <StaffLayout>
    <PageHead title="Statements and exports">
      <select v-model="month" class="input w-auto" aria-label="Month for exports"><option v-for="p in periods" :key="p" :value="p">{{ new Date(p + '-01').toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }) }}</option></select>
    </PageHead>
    <div class="grid gap-5 lg:grid-cols-2">
      <Card title="Banyira income and expenditure" sub="To date. Banyira's own accounts only.">
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="b in revenue" :key="b.code" class="border-t border-line first:border-0"><td class="py-1.5">{{ b.name }}</td><td class="num py-1.5 text-right">{{ mk(b.balance, 2) }}</td></tr>
            <tr class="border-t border-ink/70 font-medium"><td class="py-1.5">Total revenue</td><td class="num py-1.5 text-right">{{ mk(sum(revenue), 2) }}</td></tr>
            <tr v-for="b in expenses" :key="b.code" class="border-t border-line"><td class="py-1.5">{{ b.name }}</td><td class="num py-1.5 text-right">({{ mk(b.balance, 2) }})</td></tr>
            <tr class="border-t border-ink/70 font-medium"><td class="py-1.5">Total expenses</td><td class="num py-1.5 text-right">({{ mk(sum(expenses), 2) }})</td></tr>
            <tr class="border-t-2 border-ink font-semibold"><td class="py-2">Net result</td><td class="num py-2 text-right">{{ mk(sum(revenue) - sum(expenses), 2) }}</td></tr>
          </tbody>
        </table>
        <div class="mt-4"><Btn size="sm" variant="outline" :href="`/internal/finance/exports/income?month=${month}`" external>Download CSV</Btn></div>
      </Card>
      <Card title="Client funds statement" sub="Money held or owed on behalf of investors and borrowers.">
        <table class="w-full text-sm">
          <tbody><tr v-for="b in client" :key="b.code" class="border-t border-line first:border-0"><td class="py-1.5">{{ b.name }}<span class="block text-xs text-muted">{{ label(b.category) }}</span></td><td class="num py-1.5 text-right">{{ mk(b.balance, 2) }}</td></tr></tbody>
        </table>
        <div class="mt-4"><Btn size="sm" variant="outline" :href="`/internal/finance/exports/client-funds?month=${month}`" external>Download CSV</Btn></div>
      </Card>
      <Card title="Transaction export" sub="Every journal line for the selected month, with category and client-money flag." class="lg:col-span-2">
        <Btn size="sm" variant="outline" :href="`/internal/finance/exports/transactions?month=${month}`" external>Download CSV</Btn>
      </Card>
    </div>
  </StaffLayout>
</template>
