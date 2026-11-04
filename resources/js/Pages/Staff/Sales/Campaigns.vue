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
import SelectInput from '@/Components/SelectInput.vue'
import { date, label, mk } from '@/lib/format'

defineProps<{ campaigns: any[] }>()
const open = ref(false)
const form = useForm({ name: '', channel: 'SMS', audience: 'businesses', starts_on: new Date().toISOString().slice(0, 10), ends_on: '', budget: '' })
const setStatus = (c: any, status: string) => router.put(`/internal/sales/campaigns/${c.id}`, { status, reach: c.reach }, { preserveScroll: true })
</script>

<template>
  <StaffLayout>
    <PageHead title="Campaigns" sub="Outreach to businesses and investors. Never promise approval, funding or returns.">
      <Btn @click="open = true">New campaign</Btn>
    </PageHead>
    <DataTable caption="Campaigns" :rows="campaigns" empty="No campaigns yet."
      :columns="[{ key: 'name', label: 'Campaign' }, { key: 'audience', label: 'Audience' }, { key: 'dates', label: 'Dates' }, { key: 'reach', label: 'Reach', align: 'right' }, { key: 'leads', label: 'Leads', align: 'right' }, { key: 'converted', label: 'Converted', align: 'right' }, { key: 'budget', label: 'Budget', align: 'right' }, { key: 'status', label: 'Status' }]">
      <template #cell-name="{ row }">{{ row.name }}<span class="block text-xs text-muted">{{ row.channel }}</span></template>
      <template #cell-audience="{ row }">{{ label(row.audience) }}</template>
      <template #cell-dates="{ row }">{{ date(row.starts_on) }} – {{ date(row.ends_on) }}</template>
      <template #cell-reach="{ row }">{{ row.reach.toLocaleString() }}</template>
      <template #cell-budget="{ row }">{{ mk(row.budget) }}</template>
      <template #cell-status="{ row }"><Status :s="row.status" /></template>
      <template #actions="{ row }">
        <Btn v-if="row.status === 'scheduled'" size="sm" variant="outline" @click="setStatus(row, 'running')">Start</Btn>
        <Btn v-if="row.status === 'running'" size="sm" variant="outline" @click="setStatus(row, 'complete')">Complete</Btn>
      </template>
    </DataTable>

    <Modal :open="open" title="New campaign" @close="open = false">
      <form id="camp" class="space-y-3 text-ink" @submit.prevent="form.post('/internal/sales/campaigns', { preserveScroll: true, onSuccess: () => { open = false; form.reset() } })">
        <TextInput v-model="form.name" label="Name" :error="form.errors.name" />
        <div class="grid grid-cols-2 gap-3"><SelectInput v-model="form.channel" label="Channel" :options="['SMS', 'Radio', 'Facebook', 'WhatsApp', 'Events', 'SMS and radio']" /><SelectInput v-model="form.audience" label="Audience" :options="[{ value: 'businesses', label: 'Businesses' }, { value: 'investors', label: 'Investors' }, { value: 'both', label: 'Both' }]" /></div>
        <div class="grid grid-cols-2 gap-3"><TextInput v-model="form.starts_on" label="Starts" type="date" :error="form.errors.starts_on" /><TextInput v-model="form.ends_on" label="Ends" type="date" optional :error="form.errors.ends_on" /></div>
        <TextInput v-model="form.budget" label="Budget (MK)" inputmode="numeric" :error="form.errors.budget" />
      </form>
      <template #actions><Btn variant="outline" @click="open = false">Cancel</Btn><Btn type="submit" form="camp" :disabled="form.processing">Create</Btn></template>
    </Modal>
  </StaffLayout>
</template>
