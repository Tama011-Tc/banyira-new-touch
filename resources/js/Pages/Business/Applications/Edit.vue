<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Tabs from '@/Components/Tabs.vue'
import Card from '@/Components/Card.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextArea from '@/Components/TextArea.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'
import Status from '@/Components/Status.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import { date, mk, months } from '@/lib/format'

const props = defineProps<{ application: any; products: any[]; fees: { upTo: string | null; rate: string }[]; documentTypes: string[] }>()
const a = props.application
const step = ref('needs')
const steps = [{ key: 'needs', label: '1 Financing' }, { key: 'business', label: '2 Business' }, { key: 'records', label: '3 Records' }, { key: 'documents', label: '4 Documents' }, { key: 'review', label: '5 Review' }]

const lastMonths = Array.from({ length: 6 }, (_, i) => { const d = new Date(); d.setDate(1); d.setMonth(d.getMonth() - 6 + i); return d.toISOString().slice(0, 7) })
const form = useForm({
  loan_product_id: a.loan_product_id, amount: a.amount ? String(Math.round(+a.amount)) : '', term_months: a.term_months ?? '', purpose: a.purpose ?? '',
  monthly_turnover: a.monthly_turnover ? String(Math.round(+a.monthly_turnover)) : '', employees: a.employees ?? '', main_customers: a.main_customers ?? 'Mixed',
  records: lastMonths.map((m) => { const r = a.records.find((x: any) => x.period === m); return { period: m, inflow: r ? String(Math.round(+r.inflow)) : '', outflow: r ? String(Math.round(+r.outflow)) : '' } }),
})
const product = computed(() => props.products.find((p) => p.id === form.loan_product_id))
const feeRate = computed(() => {
  const amt = +form.amount
  if (!amt) return null
  for (const t of props.fees) if (t.upTo === null || amt < +t.upTo) return +t.rate
  return +props.fees[props.fees.length - 1].rate
})
const save = (then?: () => void) => form
  .transform((d) => ({ ...d, records: d.records.filter((r) => r.inflow !== '' && r.outflow !== '') }))
  .put(`/business/applications/${a.id}`, { preserveScroll: true, onSuccess: then })

const upload = useForm<{ type: string; file: File | null }>({ type: props.documentTypes[0], file: null })
const fileInput = ref<HTMLInputElement>()
const sendFile = () => upload.post(`/business/applications/${a.id}/documents`, { preserveScroll: true, forceFormData: true, onSuccess: () => { upload.reset('file'); if (fileInput.value) fileInput.value.value = '' } })
const respond = useForm({ response: '' })
const page = usePage()
const submitError = computed(() => { const e = page.props.errors as Record<string, string>; return e.application ?? e.amount ?? e.status ?? null })
const nextStep = () => save(() => { const i = steps.findIndex((s) => s.key === step.value); step.value = steps[Math.min(i + 1, steps.length - 1)].key })
</script>

<template>
  <BusinessLayout>
    <PageHead :title="a.status === 'draft' ? 'Financing application' : 'Respond to Banyira'" :sub="`${a.reference} · ${a.status === 'draft' ? 'Draft, saved when you continue' : 'More information requested'}`" :back="{ href: '/business/applications', label: 'Applications' }" />

    <div v-if="a.openRequests.length" class="mb-6 space-y-3">
      <Card v-for="r in a.openRequests" :key="r.id" title="Banyira asked for more information" :sub="date(r.created_at)">
        <p class="text-[15px]">“{{ r.message }}”</p>
        <form class="mt-4 space-y-3" @submit.prevent="respond.post(`/business/information-requests/${r.id}`, { preserveScroll: true })">
          <TextArea v-model="respond.response" label="Your response" :rows="3" :error="respond.errors.response" hint="Upload any documents in step 4 first, then reply here." />
          <Btn type="submit" :disabled="respond.processing">Send response</Btn>
        </form>
      </Card>
    </div>

    <Tabs v-model="step" :tabs="steps" />

    <div class="mt-6 max-w-2xl">
      <section v-if="step === 'needs'" class="space-y-4">
        <SelectInput v-model="form.loan_product_id" label="Product" :options="products.map((p) => ({ value: p.id, label: p.name }))" :error="form.errors.loan_product_id" />
        <div class="grid gap-4 sm:grid-cols-2">
          <TextInput v-model="form.amount" label="Amount needed (MK)" inputmode="numeric" :error="form.errors.amount" :hint="product ? `Between ${mk(product.min_amount)} and ${mk(product.max_amount)}` : ''" />
          <TextInput v-model="form.term_months" label="Repayment period (months)" inputmode="numeric" :error="form.errors.term_months" :hint="product ? `${product.min_months} to ${months(product.max_months)}` : ''" />
        </div>
        <p v-if="feeRate !== null" class="text-[13px] text-muted">Estimated service fee: <span class="num text-ink">{{ feeRate }}% = {{ mk((+form.amount * feeRate) / 100) }}</span>, deducted when funds are released. Confirmed before you accept any offer.</p>
        <TextArea v-model="form.purpose" label="What will you use the money for?" :rows="4" :error="form.errors.purpose" hint="Be specific: what you will buy, from whom and how it will increase income." />
      </section>

      <section v-if="step === 'business'" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
          <TextInput v-model="form.monthly_turnover" label="Average monthly turnover (MK)" inputmode="numeric" :error="form.errors.monthly_turnover" />
          <TextInput v-model="form.employees" label="Number of employees" inputmode="numeric" :error="form.errors.employees" />
        </div>
        <SelectInput v-model="form.main_customers" label="Main customers" :options="['Individuals', 'Other businesses', 'Government and NGOs', 'Mixed']" />
      </section>

      <section v-if="step === 'records'">
        <p class="mb-4 text-sm text-muted">Money in and money out for each of the last six months, taken from your bank or mobile-money statements. Leave a month blank if you have no record.</p>
        <table class="w-full text-sm">
          <thead class="text-left text-xs text-muted"><tr><th class="pb-2 font-medium">Month</th><th class="pb-2 font-medium">Money in (MK)</th><th class="pb-2 font-medium">Money out (MK)</th></tr></thead>
          <tbody>
            <tr v-for="(r, i) in form.records" :key="r.period" class="border-t border-line">
              <td class="py-2 pr-3">{{ new Date(r.period + '-01').toLocaleDateString('en-GB', { month: 'short', year: 'numeric' }) }}</td>
              <td class="py-2 pr-3"><input v-model="r.inflow" inputmode="numeric" class="input" :aria-label="`Money in, ${r.period}`" /></td>
              <td class="py-2"><input v-model="r.outflow" inputmode="numeric" class="input" :aria-label="`Money out, ${r.period}`" /></td>
            </tr>
          </tbody>
        </table>
        <p v-if="Object.keys(form.errors).some((k) => k.startsWith('records'))" class="mt-2 text-[13px] text-danger">Check the amounts: numbers only, no commas.</p>
      </section>

      <section v-if="step === 'documents'" class="space-y-5">
        <div v-if="product" class="text-sm">
          <p class="font-medium">For this product you will need:</p>
          <ul class="mt-2 list-disc space-y-0.5 pl-5 text-muted"><li v-for="r in product.requirements" :key="r">{{ r }}</li></ul>
        </div>
        <form class="grid gap-3 rounded-lg border border-line bg-white p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end" @submit.prevent="sendFile">
          <SelectInput v-model="upload.type" label="Document type" :options="documentTypes" />
          <div>
            <label class="mb-1.5 block text-[13px] font-medium" for="doc-file">File</label>
            <input id="doc-file" ref="fileInput" type="file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm file:mr-3 file:rounded file:border file:border-line-2 file:bg-white file:px-3 file:py-1.5" @change="upload.file = ($event.target as HTMLInputElement).files?.[0] ?? null" />
          </div>
          <Btn type="submit" :disabled="!upload.file || upload.processing">{{ upload.processing ? 'Uploading…' : 'Upload' }}</Btn>
          <p class="text-xs text-muted sm:col-span-3">PDF, JPG or PNG, up to 5 MB. A clear phone photo is fine.</p>
          <p v-if="upload.errors.file" class="text-[13px] text-danger sm:col-span-3">{{ upload.errors.file }}</p>
        </form>
        <ul v-if="a.documents.length" class="divide-y divide-line rounded-lg border border-line bg-white">
          <li v-for="d in a.documents" :key="d.id" class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
            <span><span class="block">{{ d.type }}</span><span class="text-[13px] text-muted">{{ d.original_name }} · {{ Math.ceil(d.size / 1024) }} KB</span></span>
            <span class="flex items-center gap-3"><Status :s="d.status" /><button v-if="d.status === 'pending'" type="button" class="text-[13px] text-muted underline hover:text-danger" @click="router.delete(`/business/applications/${a.id}/documents/${d.id}`, { preserveScroll: true })">Remove</button></span>
          </li>
        </ul>
        <p v-else class="text-sm text-muted">No documents uploaded yet.</p>
      </section>

      <section v-if="step === 'review'" class="space-y-4">
        <dl class="divide-y divide-line rounded-lg border border-line bg-white text-sm">
          <div class="flex justify-between px-4 py-2.5"><dt class="text-muted">Product</dt><dd>{{ product?.name }}</dd></div>
          <div class="flex justify-between px-4 py-2.5"><dt class="text-muted">Amount</dt><dd class="num">{{ form.amount ? mk(form.amount) : 'Missing' }}</dd></div>
          <div class="flex justify-between px-4 py-2.5"><dt class="text-muted">Period</dt><dd>{{ form.term_months ? months(+form.term_months) : 'Missing' }}</dd></div>
          <div class="flex justify-between px-4 py-2.5"><dt class="text-muted">Monthly records</dt><dd>{{ form.records.filter((r) => r.inflow).length }} of 6</dd></div>
          <div class="flex justify-between px-4 py-2.5"><dt class="text-muted">Documents</dt><dd>{{ a.documents.length }}</dd></div>
        </dl>
        <Notice>After you submit, a credit officer verifies and assesses your application. Submitting does not guarantee approval or funding.</Notice>
        <Notice v-if="submitError" tone="error">{{ submitError }}</Notice>
      </section>

      <div class="mt-8 flex flex-wrap items-center gap-2 border-t border-line pt-5">
        <Btn v-if="step !== 'review'" :disabled="form.processing" @click="nextStep">Save and continue</Btn>
        <ConfirmAction v-else-if="a.status === 'draft'" :href="`/business/applications/${a.id}/submit`" title="Submit your application?" confirm="Submit" variant="primary" size="md">
          Submit for review
          <template #body>You will not be able to edit it while it is being reviewed. We may ask you for more information.</template>
        </ConfirmAction>
        <Btn variant="outline" :disabled="form.processing" @click="save()">Save draft</Btn>
        <span class="flex-1" />
        <ConfirmAction v-if="a.status === 'draft'" :href="`/business/applications/${a.id}`" method="delete" danger variant="ghost" title="Delete this draft?" confirm="Delete draft">
          Delete draft
          <template #body>The draft and its uploaded documents will be removed.</template>
        </ConfirmAction>
      </div>
    </div>
  </BusinessLayout>
</template>
