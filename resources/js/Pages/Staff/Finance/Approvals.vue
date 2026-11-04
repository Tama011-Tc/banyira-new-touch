<script setup lang="ts">
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Status from '@/Components/Status.vue'
import Btn from '@/Components/Btn.vue'
import { dateTime, label, mk } from '@/lib/format'

const props = defineProps<{ requests: any[] }>()
const notes = reactive<Record<number, string>>({})
const decide = (id: number, approve: boolean) => router.post(`/internal/finance/approvals/${id}`, { approve, note: notes[id] ?? '' }, { preserveScroll: true })
const pending = props.requests.filter((r) => r.status === 'pending')
const done = props.requests.filter((r) => r.status !== 'pending')
</script>

<template>
  <StaffLayout>
    <PageHead title="Maker-checker queue" sub="Disbursements, expenses and payroll prepared by one officer must be approved by another." />
    <h2 class="mb-3 text-[15px]">Waiting</h2>
    <div v-if="!pending.length" class="rounded-lg border border-dashed border-line-2 px-6 py-8 text-center text-sm text-muted">Nothing waiting for approval.</div>
    <ul v-else class="divide-y divide-line rounded-lg border border-line bg-white">
      <li v-for="r in pending" :key="r.id" class="grid gap-3 px-5 py-4 md:grid-cols-[1fr_auto] md:items-center">
        <div>
          <p class="text-sm"><span class="mr-2 text-xs uppercase tracking-wide text-muted">{{ label(r.type) }}</span>{{ r.summary }}</p>
          <p class="text-[13px] text-muted">Prepared by {{ r.by }} · {{ dateTime(r.created_at) }}<span v-if="r.amount" class="num ml-2 font-medium text-ink">{{ mk(r.amount, 2) }}</span></p>
        </div>
        <div v-if="!r.mine" class="flex flex-wrap items-center gap-2">
          <input v-model="notes[r.id]" class="input h-8 w-48 text-[13px]" placeholder="Note" aria-label="Decision note" />
          <Btn size="sm" @click="decide(r.id, true)">Approve</Btn>
          <Btn size="sm" variant="outline" @click="decide(r.id, false)">Reject</Btn>
        </div>
        <p v-else class="text-[13px] text-muted">You prepared this. Another officer must approve it.</p>
      </li>
    </ul>

    <h2 class="mb-3 mt-10 text-[15px]">Decided</h2>
    <DataTable caption="Decided requests" :rows="done"
      :columns="[{ key: 'type', label: 'Type' }, { key: 'summary', label: 'Item' }, { key: 'amount', label: 'Amount', align: 'right' }, { key: 'by', label: 'Maker' }, { key: 'decidedBy', label: 'Checker' }, { key: 'decided_at', label: 'Decided' }, { key: 'status', label: 'Outcome' }]">
      <template #cell-type="{ row }">{{ label(row.type) }}</template>
      <template #cell-amount="{ row }">{{ row.amount ? mk(row.amount, 2) : '—' }}</template>
      <template #cell-decided_at="{ row }">{{ dateTime(row.decided_at) }}</template>
      <template #cell-status="{ row }"><Status :s="row.status" /></template>
    </DataTable>
  </StaffLayout>
</template>
