<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Tabs from '@/Components/Tabs.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'
import { date, label, mk } from '@/lib/format'

const props = defineProps<{ entries: any; balances: any[]; category: string | null }>()
const view = ref('journal')
const cats = ['banyira_cash', 'service_charges', 'operating_expenses', 'client_cash', 'investor_capital', 'borrower_obligations', 'principal_repayments', 'interest_repayments']
const rev = ref<any>(null)
const form = useForm({ reason: '' })
const reverse = () => form.post(`/internal/finance/ledger/${rev.value.id}/reverse`, { preserveScroll: true, onSuccess: () => { rev.value = null; form.reset() } })
const filter = (c: string) => router.get('/internal/finance/ledger', c ? { category: c } : {}, { preserveScroll: true })
</script>

<template>
  <StaffLayout>
    <PageHead title="Ledger" sub="Double-entry and immutable. Every entry balances; corrections are new reversal entries.">
      <Btn variant="outline" href="/internal/finance/exports/transactions" external>Export CSV</Btn>
    </PageHead>
    <Tabs v-model="view" :tabs="[{ key: 'journal', label: 'Journal' }, { key: 'balances', label: 'Trial balance' }]" />

    <div v-if="view === 'journal'" class="mt-4">
      <select class="input mb-4 w-auto" :value="category ?? ''" aria-label="Category" @change="filter(($event.target as HTMLSelectElement).value)">
        <option value="">All categories</option><option v-for="c in cats" :key="c" :value="c">{{ label(c) }}</option>
      </select>
      <div class="space-y-3">
        <article v-for="e in entries.data" :key="e.id" class="rounded-lg border border-line bg-surface">
          <header class="flex flex-wrap items-baseline justify-between gap-2 border-b border-line px-4 py-2.5 text-sm">
            <span><span class="font-mono text-[13px]">{{ e.reference }}</span><span class="ml-3">{{ e.description }}</span><span v-if="e.is_demo" class="ml-2 text-xs text-muted">demo</span><span v-if="e.reverses_id" class="ml-2 text-xs text-maize">reversal</span></span>
            <span class="text-xs text-muted">{{ date(e.entry_date) }} · {{ e.by ?? 'System' }}<template v-if="e.approvedBy && e.approvedBy !== e.by"> · approved {{ e.approvedBy }}</template></span>
          </header>
          <table class="w-full text-[13px]">
            <tbody>
              <tr v-for="(l, i) in e.lines" :key="i" class="border-t border-line first:border-0">
                <td class="w-14 px-4 py-1.5 font-mono text-muted">{{ l.code }}</td>
                <td class="py-1.5" :class="+l.credit > 0 && 'pl-6'">{{ l.account }}</td>
                <td class="hidden py-1.5 text-xs text-muted sm:table-cell">{{ label(l.category) }}</td>
                <td class="num w-32 py-1.5 text-right">{{ +l.debit ? mk(l.debit, 2) : '' }}</td>
                <td class="num w-32 px-4 py-1.5 text-right">{{ +l.credit ? mk(l.credit, 2) : '' }}</td>
              </tr>
            </tbody>
          </table>
          <footer v-if="e.reversible" class="border-t border-line px-4 py-2 text-right"><button type="button" class="text-[13px] text-muted underline hover:text-ink" @click="rev = e">Reverse</button></footer>
        </article>
      </div>
      <Pagination :page="entries" />
    </div>

    <div v-else class="mt-4 relative overflow-x-auto rounded-lg border border-line bg-surface">
      <table class="w-full min-w-[640px] text-sm">
        <thead class="text-left text-xs text-muted"><tr><th class="px-4 py-2.5 font-medium">Account</th><th class="px-4 py-2.5 font-medium">Category</th><th class="px-4 py-2.5 text-right font-medium">Debits</th><th class="px-4 py-2.5 text-right font-medium">Credits</th><th class="px-4 py-2.5 text-right font-medium">Balance</th></tr></thead>
        <tbody>
          <tr v-for="b in balances" :key="b.code" class="border-t border-line">
            <td class="px-4 py-2"><span class="mr-2 font-mono text-[13px] text-muted">{{ b.code }}</span>{{ b.name }}<span v-if="b.client" class="ml-2 text-xs text-skyink">client money</span></td>
            <td class="px-4 py-2 text-[13px] text-muted">{{ label(b.category) }}</td>
            <td class="num px-4 py-2 text-right">{{ mk(b.debit, 2) }}</td>
            <td class="num px-4 py-2 text-right">{{ mk(b.credit, 2) }}</td>
            <td class="num px-4 py-2 text-right font-medium">{{ mk(b.balance, 2) }}</td>
          </tr>
          <tr class="border-t border-ink/70 font-medium">
            <td class="px-4 py-2" colspan="2">Totals</td>
            <td class="num px-4 py-2 text-right">{{ mk(balances.reduce((a, b) => a + +b.debit, 0), 2) }}</td>
            <td class="num px-4 py-2 text-right">{{ mk(balances.reduce((a, b) => a + +b.credit, 0), 2) }}</td>
            <td />
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :open="!!rev" title="Post a reversal?" @close="rev = null">
      <p class="text-muted">A mirror entry is posted. The original stays in the ledger.</p>
      <div class="mt-4"><TextInput v-model="form.reason" label="Reason" :error="form.errors.reason" /></div>
      <template #actions><Btn variant="outline" @click="rev = null">Cancel</Btn><Btn variant="danger" :disabled="!form.reason || form.processing" @click="reverse">Post reversal</Btn></template>
    </Modal>
  </StaffLayout>
</template>
