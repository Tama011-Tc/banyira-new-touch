<script setup lang="ts">
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Status from '@/Components/Status.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import Notice from '@/Components/Notice.vue'
import { date, dateTime, mk } from '@/lib/format'

const props = defineProps<{ agreements: { id: number; reference: string; outstanding: string }[]; schedule: any[]; payments: any[]; idempotencyKey: string }>()
const demo = usePage().props.demoPayments
const open = ref(false)
const form = useForm({ agreement: props.agreements[0]?.id, amount: props.schedule[0] ? String(Math.ceil(+props.schedule[0].total)) : '', msisdn: '', idempotency_key: props.idempotencyKey })
const pay = () => form.transform(({ agreement, ...d }) => d).post(`/business/agreements/${form.agreement}/payments`, { preserveScroll: true, onSuccess: () => (open.value = false) })
</script>

<template>
  <BusinessLayout>
    <PageHead title="Repayments" sub="Your combined monthly schedule across all investors, and every payment you have made.">
      <Btn v-if="agreements.length" @click="open = true">Make a payment</Btn>
    </PageHead>

    <Notice v-if="demo" tone="warn" title="Demonstration payments">Payments are not connected to a mobile-money or bank provider. Nothing you do here moves real money; finance staff confirm payments manually.</Notice>

    <h2 class="mb-3 mt-8 text-[15px]">Schedule</h2>
    <DataTable caption="Repayment schedule" :rows="schedule" empty="No repayments scheduled."
      :columns="[{ key: 'due', label: 'Due date' }, { key: 'principal', label: 'Principal', align: 'right' }, { key: 'interest', label: 'Interest', align: 'right' }, { key: 'total', label: 'Total due', align: 'right' }, { key: 'status', label: 'Status' }]">
      <template #cell-due="{ row }">{{ date(row.due) }}</template>
      <template #cell-principal="{ row }">{{ mk(row.principal, 2) }}</template>
      <template #cell-interest="{ row }">{{ mk(row.interest, 2) }}</template>
      <template #cell-total="{ row }"><span class="font-medium">{{ mk(row.total, 2) }}</span></template>
      <template #cell-status="{ row }"><Status :s="row.overdue ? 'overdue' : 'scheduled'" /></template>
    </DataTable>

    <h2 class="mb-3 mt-10 text-[15px]">Payments</h2>
    <DataTable caption="Payments" :rows="payments" empty="No payments yet."
      :columns="[{ key: 'reference', label: 'Reference' }, { key: 'created_at', label: 'Started' }, { key: 'amount', label: 'Amount', align: 'right' }, { key: 'status', label: 'Status' }, { key: 'confirmed_at', label: 'Confirmed' }]">
      <template #cell-reference="{ row }"><span class="font-mono text-[13px]">{{ row.reference }}</span><span v-if="row.is_demo" class="ml-2 text-xs text-muted">demo</span></template>
      <template #cell-created_at="{ row }">{{ dateTime(row.created_at) }}</template>
      <template #cell-amount="{ row }">{{ mk(row.amount, 2) }}</template>
      <template #cell-status="{ row }"><Status :s="row.status === 'pending' ? 'awaiting confirmation' : row.status" /></template>
      <template #cell-confirmed_at="{ row }">{{ dateTime(row.confirmed_at) }}</template>
    </DataTable>

    <Modal :open="open" title="Make a payment" @close="open = false">
      <form id="pay" class="space-y-4 text-ink" @submit.prevent="pay">
        <SelectInput v-if="agreements.length > 1" v-model="form.agreement" label="Agreement" :options="agreements.map((a) => ({ value: a.id, label: `${a.reference} · ${mk(a.outstanding)} outstanding` }))" />
        <TextInput v-model="form.amount" label="Amount (MK)" inputmode="decimal" :error="form.errors.amount" :hint="`Outstanding: ${mk(agreements.find((a) => a.id === form.agreement)?.outstanding, 2)}`" />
        <TextInput v-model="form.msisdn" label="Mobile money number" type="tel" inputmode="tel" optional :error="form.errors.msisdn" hint="Approve the prompt on your phone. The payment shows as confirmed only once the provider or our finance team confirms it." />
      </form>
      <template #actions>
        <Btn variant="outline" @click="open = false">Cancel</Btn>
        <Btn type="submit" form="pay" :disabled="form.processing">{{ form.processing ? 'Starting…' : 'Pay' }}</Btn>
      </template>
    </Modal>
  </BusinessLayout>
</template>
