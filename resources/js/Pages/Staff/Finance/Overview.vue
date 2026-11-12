<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Stat from '@/Components/Stat.vue'
import Card from '@/Components/Card.vue'
import Notice from '@/Components/Notice.vue'
import BarChart from '@/Components/BarChart.vue'
import { mk, mkShort } from '@/lib/format'
import { useCan } from '@/lib/permissions'

const can = useCan()
defineProps<{ banyira: Record<string, string>; client: Record<string, string>; monthly: { l: string; v: number; v2: number }[]; pending: { checker: number; reconcile: number; funding: number } }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Finance overview" sub="All figures come from the ledger." />
    <Notice tone="warn" title="Banyira money and client money are reported separately">Investor capital, borrower obligations and repayments held for investors are never counted as Banyira revenue.</Notice>

    <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm">
      <Link v-if="can('finance.approve')" href="/internal/finance/approvals" class="link">{{ pending.checker }} awaiting approval</Link>
      <Link v-if="can('payments.reconcile')" href="/internal/finance/reconciliation" class="link">{{ pending.reconcile }} payments to reconcile</Link>
      <Link v-if="can('finance.record')" href="/internal/finance/funding" class="link">{{ pending.funding }} investor transfers to confirm</Link>
    </div>

    <h2 class="mb-4 mt-8 text-[15px]">Banyira's own accounts</h2>
    <div class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-5">
      <Stat label="Revenue this month" :value="mk(banyira.revenueMonth)" note="Service fees" />
      <Stat label="Expenses this month" :value="mk(banyira.expensesMonth)" />
      <Stat label="Revenue to date" :value="mk(banyira.revenueTotal)" />
      <Stat label="Expenses to date" :value="mk(banyira.expensesTotal)" />
      <Stat label="Operating cash" :value="mk(banyira.cash)" />
    </div>

    <h2 class="mb-4 mt-10 text-[15px]">Client money (held for others)</h2>
    <div class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-5">
      <Stat label="Client account balance" :value="mk(client.cash)" />
      <Stat label="Investor capital awaiting disbursement" :value="mk(client.awaitingDisbursement)" />
      <Stat label="Borrower principal outstanding" :value="mk(client.borrowerOutstanding)" />
      <Stat label="Principal due to investors" :value="mk(client.principalDue)" />
      <Stat label="Interest due to investors" :value="mk(client.interestDue)" />
    </div>

    <Card class="mt-10" title="Banyira revenue and expenses by month"><BarChart label="Revenue and expenses" :data="monthly" :legend="['Revenue', 'Expenses']" :fmt="(n) => mkShort(n)" /></Card>
  </StaffLayout>
</template>
