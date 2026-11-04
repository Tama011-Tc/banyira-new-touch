<script setup lang="ts">
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Status from '@/Components/Status.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import TextInput from '@/Components/TextInput.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import Notice from '@/Components/Notice.vue'
import { date, mk } from '@/lib/format'

defineProps<{ agreements: any[] }>()
const page = usePage()
const canConfirm = (page.props.auth.user?.permissions ?? []).includes('payments.reconcile')
const party = ref<any>(null)
const form = useForm({ provider_reference: '' })
const confirm = () => form.post(`/internal/finance/parties/${party.value.id}/confirm`, { preserveScroll: true, onSuccess: () => { party.value = null; form.reset() } })
</script>

<template>
  <StaffLayout>
    <PageHead title="Funding and disbursement" sub="Signatures → investor funds confirmed → disbursement requested → approved by a second officer." />
    <div v-if="!agreements.length" class="rounded-lg border border-dashed border-line-2 px-6 py-12 text-center text-sm text-muted">No agreements in progress.</div>
    <div class="space-y-5">
      <Card v-for="a in agreements" :key="a.id">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div><p class="font-mono text-[12px] text-muted">{{ a.reference }}</p><p class="mt-1 font-medium">{{ a.business }}</p><p class="mt-1"><Status :s="a.status" /></p></div>
          <dl class="grid grid-cols-3 gap-6 text-right text-sm">
            <div><dt class="text-muted">Principal</dt><dd class="num">{{ mk(a.principal) }}</dd></div>
            <div><dt class="text-muted">Fee {{ Number(a.feeRate) }}%</dt><dd class="num">{{ mk(a.fee) }}</dd></div>
            <div><dt class="text-muted">Net to business</dt><dd class="num font-medium">{{ mk(a.net) }}</dd></div>
          </dl>
        </div>
        <table class="mt-5 w-full text-sm">
          <thead class="text-left text-xs text-muted"><tr><th class="pb-2 font-medium">Party</th><th class="pb-2 font-medium">Role</th><th class="pb-2 text-right font-medium">Principal</th><th class="pb-2 font-medium">Signed</th><th class="pb-2 font-medium">Funds</th><th class="pb-2" /></tr></thead>
          <tbody>
            <tr v-for="p in a.parties" :key="p.id" class="border-t border-line">
              <td class="py-2">{{ p.name }}</td><td class="py-2 capitalize text-muted">{{ p.role }}</td>
              <td class="num py-2 text-right">{{ p.principal ? mk(p.principal) : '—' }}</td>
              <td class="py-2">{{ p.signed ? date(p.signed) : 'Waiting' }}</td>
              <td class="py-2">{{ p.role === 'borrower' ? '—' : p.funded ? 'Confirmed ' + date(p.funded) : 'Not received' }}</td>
              <td class="py-2 text-right"><Btn v-if="canConfirm && a.status === 'awaiting_funding' && p.role === 'investor' && !p.funded" size="sm" variant="outline" @click="party = p">Confirm received</Btn></td>
            </tr>
          </tbody>
        </table>
        <div v-if="a.status === 'awaiting_disbursement'" class="mt-4 border-t border-line pt-4">
          <p v-if="a.requested" class="text-sm text-muted">Disbursement requested. Waiting for a second finance officer to approve it.</p>
          <ConfirmAction v-else :href="`/internal/finance/agreements/${a.id}/disbursement`" variant="primary" size="md" title="Request disbursement?" confirm="Send for approval">
            Request disbursement
            <template #body>{{ mk(a.net) }} will be released to {{ a.business }} and {{ mk(a.fee) }} moved to Banyira's operating account, once another finance officer approves.</template>
          </ConfirmAction>
        </div>
      </Card>
    </div>

    <Modal :open="!!party" title="Confirm investor funds received" @close="party = null">
      <div class="space-y-4 text-ink">
        <p class="text-muted">Confirm only after the money shows in the client bank or mobile-money account. This posts to the ledger and cannot be edited.</p>
        <Notice v-if="page.props.demoPayments" tone="warn">Demonstration mode. Enter any unique reference.</Notice>
        <p class="text-sm">{{ party?.name }} · <span class="num font-medium">{{ party && mk(party.principal) }}</span></p>
        <TextInput v-model="form.provider_reference" label="Bank or mobile-money transaction reference" :error="form.errors.provider_reference ?? (form.errors as Record<string, string>).funds" />
      </div>
      <template #actions><Btn variant="outline" @click="party = null">Cancel</Btn><Btn :disabled="!form.provider_reference || form.processing" @click="confirm">Confirm</Btn></template>
    </Modal>
  </StaffLayout>
</template>
