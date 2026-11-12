<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Status from '@/Components/Status.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import { mk, months } from '@/lib/format'

defineProps<{ products: any[] }>()
const sel = ref<any>(null)
const form = useForm<any>({})
const lines = (a: string[]) => a.join('\n')
const edit = (p: any) => {
  sel.value = p
  Object.assign(form, { ...p, purposes: lines(p.purposes), eligibility: lines(p.eligibility), requirements: lines(p.requirements), min_amount: String(Math.round(+p.min_amount)), max_amount: String(Math.round(+p.max_amount)) })
  form.clearErrors()
}
const split = (s: string) => s.split('\n').map((x) => x.trim()).filter(Boolean)
const save = () => form
  .transform((d: any) => ({ name: d.name, summary: d.summary, description: d.description, audience: d.audience, repayment_info: d.repayment_info, purposes: split(d.purposes), eligibility: split(d.eligibility), requirements: split(d.requirements), min_amount: d.min_amount, max_amount: d.max_amount, min_months: d.min_months, max_months: d.max_months, is_active: d.is_active }))
  .put(`/internal/products/${sel.value.id}`, { preserveScroll: true, onSuccess: () => (sel.value = null) })
</script>

<template>
  <StaffLayout>
    <PageHead title="Products" sub="What the public website and application form show. Changes appear immediately and are logged." />
    <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
      <li v-for="p in products" :key="p.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm">
        <span><span class="mr-2 font-mono text-[12px] text-muted">{{ p.code }}</span><span class="font-medium">{{ p.name }}</span><span class="num block text-[13px] text-muted">{{ mk(p.min_amount) }}–{{ mk(p.max_amount) }} · {{ p.min_months }}–{{ months(p.max_months) }}</span></span>
        <span class="flex items-center gap-4"><Status :s="p.is_active ? 'published' : 'hidden'" /><Btn size="sm" variant="outline" @click="edit(p)">Edit</Btn></span>
      </li>
    </ul>

    <Modal :open="!!sel" :title="sel?.name ?? ''" @close="sel = null">
      <form id="prod" class="max-h-[60vh] space-y-3 overflow-y-auto pr-1 text-ink" @submit.prevent="save">
        <TextInput v-model="form.name" label="Name" :error="form.errors.name" />
        <TextInput v-model="form.summary" label="One-line summary" :error="form.errors.summary" />
        <TextArea v-model="form.description" label="Description" :rows="3" :error="form.errors.description" />
        <TextArea v-model="form.audience" label="Who it is for" :rows="2" :error="form.errors.audience" />
        <div class="grid grid-cols-2 gap-3"><TextInput v-model="form.min_amount" label="Minimum (MK)" inputmode="numeric" :error="form.errors.min_amount" /><TextInput v-model="form.max_amount" label="Maximum (MK)" inputmode="numeric" :error="form.errors.max_amount" /></div>
        <div class="grid grid-cols-2 gap-3"><TextInput v-model="form.min_months" label="Shortest (months)" inputmode="numeric" :error="form.errors.min_months" /><TextInput v-model="form.max_months" label="Longest (months)" inputmode="numeric" :error="form.errors.max_months" /></div>
        <TextArea v-model="form.repayment_info" label="Repayment information" :rows="2" :error="form.errors.repayment_info" />
        <TextArea v-model="form.purposes" label="Eligible purposes (one per line)" :rows="3" :error="form.errors.purposes" />
        <TextArea v-model="form.eligibility" label="Eligibility (one per line)" :rows="3" :error="form.errors.eligibility" />
        <TextArea v-model="form.requirements" label="Requirements (one per line)" :rows="4" :error="form.errors.requirements" />
        <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" class="h-4 w-4" />Published on the website</label>
      </form>
      <template #actions><Btn variant="outline" @click="sel = null">Cancel</Btn><Btn type="submit" form="prod" :disabled="form.processing">Save</Btn></template>
    </Modal>
  </StaffLayout>
</template>
