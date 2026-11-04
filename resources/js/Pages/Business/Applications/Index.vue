<script setup lang="ts">
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Status from '@/Components/Status.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import { date, mk } from '@/lib/format'

const props = defineProps<{ applications: any[]; products: { id: number; name: string; summary: string }[] }>()
const open = ref(false)
const form = useForm({ loan_product_id: props.products[0]?.id })
</script>

<template>
  <BusinessLayout>
    <PageHead title="My applications" sub="Drafts are saved automatically when you continue. Submitted applications are reviewed by our credit team.">
      <Btn @click="open = true">New application</Btn>
    </PageHead>

    <DataTable caption="Applications" :rows="applications" empty="No applications yet."
      :columns="[{ key: 'reference', label: 'Reference' }, { key: 'product', label: 'Product' }, { key: 'amount', label: 'Amount', align: 'right' }, { key: 'risk', label: 'Class' }, { key: 'status', label: 'Status' }, { key: 'updated', label: 'Updated' }]">
      <template #cell-reference="{ row }"><span class="font-mono text-[13px]">{{ row.reference }}</span></template>
      <template #cell-amount="{ row }">{{ row.amount ? mk(row.amount) : '—' }}</template>
      <template #cell-risk="{ row }"><RiskBadge :r="row.risk" /></template>
      <template #cell-status="{ row }"><Status :s="row.statusLabel" /></template>
      <template #cell-updated="{ row }">{{ date(row.updated) }}</template>
      <template #actions="{ row }">
        <Btn v-if="row.status === 'draft' || row.status === 'information_required'" size="sm" :href="`/business/applications/${row.id}/edit`">{{ row.status === 'draft' ? 'Continue' : 'Respond' }}</Btn>
        <Btn size="sm" variant="outline" :href="`/business/applications/${row.id}`">Track</Btn>
      </template>
      <template #emptyAction><Btn size="sm" @click="open = true">Start an application</Btn></template>
    </DataTable>

    <Modal :open="open" title="Which product do you need?" @close="open = false">
      <form id="new-app" class="space-y-2" @submit.prevent="form.post('/business/applications')">
        <label v-for="p in products" :key="p.id" class="flex cursor-pointer gap-3 rounded-md border px-3 py-2.5" :class="form.loan_product_id === p.id ? 'border-ink' : 'border-line'">
          <input v-model="form.loan_product_id" type="radio" :value="p.id" class="mt-1" />
          <span><span class="block font-medium text-ink">{{ p.name }}</span><span class="text-[13px] text-muted">{{ p.summary }}</span></span>
        </label>
        <p class="pt-1 text-xs text-muted">Not sure? <Link href="/products" class="link">Compare products</Link></p>
      </form>
      <template #actions>
        <Btn variant="outline" @click="open = false">Cancel</Btn>
        <Btn type="submit" form="new-app" :disabled="form.processing">Start application</Btn>
      </template>
    </Modal>
  </BusinessLayout>
</template>
