<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Status from '@/Components/Status.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'
import { mk } from '@/lib/format'

const props = defineProps<{ runs: any[]; canPrepare: boolean; nextPeriod: string; employees: { id: number; name: string; title: string }[] }>()
const open = ref<number | null>(props.runs[0]?.id ?? null)
const prep = ref(false)
const form = useForm({ period: props.nextPeriod.slice(0, 7), items: props.employees.map((e) => ({ user_id: e.id, gross: '', deductions: '' })) })
const period = (p: string) => new Date(p).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' })
</script>

<template>
  <StaffLayout>
    <PageHead title="Payroll" sub="Restricted to authorised finance and executive staff. Every view is logged.">
      <Btn v-if="canPrepare" @click="prep = true">Prepare payroll</Btn>
    </PageHead>
    <Notice tone="info">A payroll run is posted to the ledger only after a second authorised officer approves it.</Notice>
    <div class="mt-6 space-y-3">
      <Card v-for="r in runs" :key="r.id" flush>
        <button type="button" class="flex w-full flex-wrap items-center justify-between gap-3 px-5 py-3.5 text-left" :aria-expanded="open === r.id" @click="open = open === r.id ? null : r.id">
          <span class="font-medium">{{ period(r.period) }}</span>
          <span class="flex items-center gap-5 text-sm"><span class="num">Gross {{ mk(r.total_gross, 2) }}</span><span class="num text-muted">Net {{ mk(r.total_net, 2) }}</span><Status :s="r.status" /></span>
        </button>
        <table v-if="open === r.id" class="w-full border-t border-line text-sm">
          <thead class="text-left text-xs text-muted"><tr><th class="px-5 py-2 font-medium">Employee</th><th class="px-5 py-2 text-right font-medium">Gross</th><th class="px-5 py-2 text-right font-medium">Deductions</th><th class="px-5 py-2 text-right font-medium">Net</th></tr></thead>
          <tbody>
            <tr v-for="(i, k) in r.items" :key="k" class="border-t border-line"><td class="px-5 py-2">{{ i.name }}<span class="block text-xs text-muted">{{ i.title }}</span></td><td class="num px-5 py-2 text-right">{{ mk(i.gross, 2) }}</td><td class="num px-5 py-2 text-right">{{ mk(i.deductions, 2) }}</td><td class="num px-5 py-2 text-right">{{ mk(i.net, 2) }}</td></tr>
          </tbody>
        </table>
        <p v-if="open === r.id" class="border-t border-line px-5 py-2 text-xs text-muted">Prepared by {{ r.by }}</p>
      </Card>
    </div>

    <Modal :open="prep" title="Prepare payroll" @close="prep = false">
      <form id="payroll" class="space-y-3 text-ink" @submit.prevent="form.post('/internal/finance/payroll', { preserveScroll: true, onSuccess: () => (prep = false) })">
        <div><label class="mb-1.5 block text-[13px] font-medium" for="period">Month</label><input id="period" v-model="form.period" type="month" class="input" /><p v-if="form.errors.period" class="mt-1 text-[13px] text-danger">{{ form.errors.period }}</p></div>
        <table class="w-full text-[13px]">
          <thead class="text-left text-xs text-muted"><tr><th class="pb-1 font-medium">Employee</th><th class="pb-1 font-medium">Gross</th><th class="pb-1 font-medium">Deductions</th></tr></thead>
          <tbody><tr v-for="(it, i) in form.items" :key="it.user_id"><td class="py-1 pr-2">{{ employees[i].name }}</td><td class="py-1 pr-2"><input v-model="it.gross" class="input h-8" inputmode="decimal" :aria-label="`Gross for ${employees[i].name}`" /></td><td class="py-1"><input v-model="it.deductions" class="input h-8" inputmode="decimal" :aria-label="`Deductions for ${employees[i].name}`" /></td></tr></tbody>
        </table>
        <p v-if="Object.keys(form.errors).some((k) => k.startsWith('items'))" class="text-[13px] text-danger">Enter gross and deductions for every employee. Deductions cannot exceed gross.</p>
      </form>
      <template #actions><Btn variant="outline" @click="prep = false">Cancel</Btn><Btn type="submit" form="payroll" :disabled="form.processing">Send for approval</Btn></template>
    </Modal>
  </StaffLayout>
</template>
