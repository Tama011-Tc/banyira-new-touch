<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import Status from '@/Components/Status.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import Btn from '@/Components/Btn.vue'
import { date, mk } from '@/lib/format'

const props = defineProps<{ expenses: any; categories: string[] }>()
const form = useForm({ category: props.categories[0], description: '', amount: '', incurred_on: new Date().toISOString().slice(0, 10) })
</script>

<template>
  <StaffLayout>
    <PageHead title="Expenses" sub="Banyira's operating costs. Each expense is posted to the ledger only after a second officer approves it." />
    <Card title="Record an expense">
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_2fr_1fr_1fr_auto] lg:items-end" @submit.prevent="form.post('/internal/finance/expenses', { preserveScroll: true, onSuccess: () => form.reset('description', 'amount') })">
        <SelectInput v-model="form.category" label="Category" :options="categories" />
        <TextInput v-model="form.description" label="Description" :error="form.errors.description" />
        <TextInput v-model="form.amount" label="Amount (MK)" inputmode="decimal" :error="form.errors.amount" />
        <TextInput v-model="form.incurred_on" label="Date" type="date" :error="form.errors.incurred_on" />
        <Btn type="submit" :disabled="form.processing">Send for approval</Btn>
      </form>
    </Card>
    <div class="mt-6">
      <DataTable caption="Expenses" :rows="expenses.data" empty="No expenses recorded."
        :columns="[{ key: 'incurred_on', label: 'Date' }, { key: 'category', label: 'Category' }, { key: 'description', label: 'Description' }, { key: 'by', label: 'Recorded by' }, { key: 'amount', label: 'Amount', align: 'right' }, { key: 'status', label: 'Status' }]">
        <template #cell-incurred_on="{ row }">{{ date(row.incurred_on) }}</template>
        <template #cell-amount="{ row }">{{ mk(row.amount, 2) }}</template>
        <template #cell-status="{ row }"><Status :s="row.status" /></template>
      </DataTable>
      <Pagination :page="expenses" />
    </div>
  </StaffLayout>
</template>
