<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Status from '@/Components/Status.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import TextInput from '@/Components/TextInput.vue'
import { dateTime, label, mk } from '@/lib/format'

defineProps<{ pending: any[]; recent: any[] }>()
const sel = ref<any>(null)
const form = useForm({ provider_reference: '' })
const confirm = () => form.post(`/internal/finance/payments/${sel.value.id}/confirm`, { preserveScroll: true, onSuccess: () => { sel.value = null; form.reset() } })
</script>

<template>
  <StaffLayout>
    <PageHead title="Reconciliation" sub="Match incoming repayments to statements. Confirming allocates the money to instalments, oldest first, interest before principal." />

    <h2 class="mb-3 text-[15px]">Awaiting confirmation</h2>
    <DataTable caption="Pending payments" :rows="pending" empty="Nothing to reconcile."
      :columns="[{ key: 'reference', label: 'Payment' }, { key: 'agreement', label: 'Agreement' }, { key: 'payer', label: 'Payer' }, { key: 'payer_msisdn', label: 'From number' }, { key: 'created_at', label: 'Started' }, { key: 'amount', label: 'Amount', align: 'right' }]">
      <template #cell-reference="{ row }"><span class="font-mono text-[13px]">{{ row.reference }}</span><span v-if="row.is_demo" class="ml-2 text-xs text-muted">demo</span></template>
      <template #cell-agreement="{ row }"><span class="font-mono text-[13px]">{{ row.agreement }}</span></template>
      <template #cell-created_at="{ row }">{{ dateTime(row.created_at) }}</template>
      <template #cell-amount="{ row }">{{ mk(row.amount, 2) }}</template>
      <template #actions="{ row }">
        <Btn size="sm" @click="sel = row">Confirm</Btn>
        <Btn size="sm" variant="ghost" @click="router.post(`/internal/finance/payments/${row.id}/fail`, {}, { preserveScroll: true })">Not received</Btn>
      </template>
    </DataTable>

    <h2 class="mb-3 mt-10 text-[15px]">Recent</h2>
    <DataTable caption="Recent payments" :rows="recent"
      :columns="[{ key: 'reference', label: 'Payment' }, { key: 'purpose', label: 'Purpose' }, { key: 'agreement', label: 'Agreement' }, { key: 'provider_reference', label: 'Provider ref.' }, { key: 'confirmed_at', label: 'Confirmed' }, { key: 'amount', label: 'Amount', align: 'right' }, { key: 'status', label: 'Status' }]">
      <template #cell-reference="{ row }"><span class="font-mono text-[13px]">{{ row.reference }}</span></template>
      <template #cell-purpose="{ row }">{{ label(row.purpose) }} <span class="text-xs text-muted">{{ row.direction === 'in' ? 'in' : 'out' }}</span></template>
      <template #cell-provider_reference="{ row }"><span class="font-mono text-[13px]">{{ row.provider_reference ?? '—' }}</span></template>
      <template #cell-confirmed_at="{ row }">{{ dateTime(row.confirmed_at) }}</template>
      <template #cell-amount="{ row }">{{ mk(row.amount, 2) }}</template>
      <template #cell-status="{ row }"><Status :s="row.status" /></template>
    </DataTable>

    <Modal :open="!!sel" title="Confirm payment received" @close="sel = null">
      <div class="space-y-4 text-ink">
        <p class="text-muted">{{ sel?.payer }} · {{ sel?.agreement }} · <span class="num font-medium text-ink">{{ sel && mk(sel.amount, 2) }}</span></p>
        <TextInput v-model="form.provider_reference" label="Statement or provider reference" :error="form.errors.provider_reference ?? (form.errors as Record<string, string>).payment" hint="Must match the line on the bank or mobile-money statement." />
      </div>
      <template #actions><Btn variant="outline" @click="sel = null">Cancel</Btn><Btn :disabled="!form.provider_reference || form.processing" @click="confirm">Confirm and allocate</Btn></template>
    </Modal>
  </StaffLayout>
</template>
