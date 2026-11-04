<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Tabs from '@/Components/Tabs.vue'
import Card from '@/Components/Card.vue'
import Pagination from '@/Components/Pagination.vue'
import Status from '@/Components/Status.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextArea from '@/Components/TextArea.vue'
import { date, dateTime, label } from '@/lib/format'

const props = defineProps<{ leads: any; stage: string | null; campaigns: { id: number; name: string }[] }>()
const stages = ['new', 'contacted', 'qualified', 'onboarding', 'converted', 'lost']
const tabs = computed(() => [{ key: '', label: 'All', href: '/internal/sales/leads' }, ...stages.map((s) => ({ key: s, label: label(s), href: `/internal/sales/leads?stage=${s}` }))])
const adding = ref(false)
const add = useForm({ name: '', type: 'business', phone: '', email: '', source: 'Referral', next_follow_up: '', campaign_id: null as number | null })
const sel = ref<any>(null)
const upd = useForm({ stage: 'contacted', next_follow_up: '', note: '' })
const edit = (l: any) => { sel.value = l; upd.stage = l.stage; upd.next_follow_up = l.next_follow_up?.slice(0, 10) ?? ''; upd.note = '' }
const overdue = (d: string | null) => d && new Date(d) < new Date(new Date().toDateString())
</script>

<template>
  <StaffLayout>
    <PageHead title="Leads and follow-ups" sub="Prospects from campaigns, referrals, the website and support requests.">
      <Btn @click="adding = true">Add lead</Btn>
    </PageHead>
    <Tabs :model-value="stage ?? ''" :tabs="tabs" />
    <ul class="mt-4 divide-y divide-line rounded-lg border border-line bg-white">
      <li v-if="!leads.data.length" class="px-5 py-10 text-center text-sm text-muted">No leads in this stage.</li>
      <li v-for="l in leads.data" :key="l.id" class="grid gap-3 px-5 py-4 md:grid-cols-[1.4fr_1fr_auto] md:items-center">
        <div>
          <p class="text-sm font-medium">{{ l.name }} <span class="ml-2 text-xs font-normal text-muted">{{ label(l.type) }} · {{ l.source }}</span></p>
          <p class="text-[13px] text-muted">{{ [l.phone, l.email].filter(Boolean).join(' · ') || 'No contact details' }}</p>
          <p v-if="l.message" class="mt-1 line-clamp-2 text-[13px]">{{ l.message }}</p>
        </div>
        <div class="text-[13px]">
          <Status :s="l.stage" />
          <p :class="overdue(l.next_follow_up) ? 'text-danger' : 'text-muted'">{{ l.next_follow_up ? 'Follow up ' + date(l.next_follow_up) : 'No follow-up set' }}</p>
          <p v-if="l.activities[0]" class="text-muted">Last: {{ l.activities[0].note }}</p>
        </div>
        <Btn size="sm" variant="outline" @click="edit(l)">Update</Btn>
      </li>
    </ul>
    <Pagination :page="leads" />

    <Modal :open="adding" title="Add a lead" @close="adding = false">
      <form id="add-lead" class="space-y-3 text-ink" @submit.prevent="add.post('/internal/sales/leads', { preserveScroll: true, onSuccess: () => { adding = false; add.reset() } })">
        <TextInput v-model="add.name" label="Name" :error="add.errors.name" />
        <div class="grid grid-cols-2 gap-3"><SelectInput v-model="add.type" label="Type" :options="[{ value: 'business', label: 'Business' }, { value: 'investor', label: 'Investor' }, { value: 'other', label: 'Other' }]" /><TextInput v-model="add.source" label="Source" :error="add.errors.source" /></div>
        <div class="grid grid-cols-2 gap-3"><TextInput v-model="add.phone" label="Phone" optional /><TextInput v-model="add.email" label="Email" optional :error="add.errors.email" /></div>
        <div class="grid grid-cols-2 gap-3"><TextInput v-model="add.next_follow_up" label="Follow up on" type="date" optional /><SelectInput v-model="add.campaign_id" label="Campaign" :options="[{ value: '' as any, label: 'None' }, ...campaigns.map((c) => ({ value: c.id, label: c.name }))]" /></div>
      </form>
      <template #actions><Btn variant="outline" @click="adding = false">Cancel</Btn><Btn type="submit" form="add-lead" :disabled="add.processing">Add lead</Btn></template>
    </Modal>

    <Modal :open="!!sel" :title="sel?.name ?? ''" @close="sel = null">
      <form id="upd-lead" class="space-y-3 text-ink" @submit.prevent="upd.put(`/internal/sales/leads/${sel.id}`, { preserveScroll: true, onSuccess: () => (sel = null) })">
        <div class="grid grid-cols-2 gap-3"><SelectInput v-model="upd.stage" label="Stage" :options="stages.map((s) => ({ value: s, label: label(s) }))" /><TextInput v-model="upd.next_follow_up" label="Next follow-up" type="date" optional /></div>
        <TextArea v-model="upd.note" label="Log activity" optional :rows="2" hint="For example: called, explained verification, will upload ID tomorrow." />
      </form>
      <ul v-if="sel?.activities.length" class="mt-4 space-y-1 border-t border-line pt-3 text-[13px]"><li v-for="(a, i) in sel.activities" :key="i"><span class="text-muted">{{ dateTime(a.at) }} · {{ a.by }}:</span> {{ a.note }}</li></ul>
      <template #actions><Btn variant="outline" @click="sel = null">Cancel</Btn><Btn type="submit" form="upd-lead" :disabled="upd.processing">Save</Btn></template>
    </Modal>
  </StaffLayout>
</template>
