<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Sim from '@/components/pw/Sim.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { label, mk } from '@/lib/format';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ report: Record<string, any> }>();
const r = props.report;
const max = (rows: { total: number }[]) => Math.max(1, ...rows.map((x) => x.total));
</script>

<template>
    <Head title="Executive overview" />
    <AppLayout>
        <PageHeader title="Executive overview" description="Operational and financial position. All money figures are simulated in this build."><Sim /></PageHeader>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">Applications submitted</p><p class="text-2xl font-semibold">{{ r.applications.total }}</p><p class="text-xs text-muted-foreground">{{ r.applications.awaiting }} awaiting review</p></div>
            <div class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">Approved · rejected</p><p class="text-2xl font-semibold">{{ r.applications.approved }} · {{ r.applications.rejected }}</p></div>
            <div class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">Campaigns active · completed</p><p class="text-2xl font-semibold">{{ r.campaigns.active }} · {{ r.campaigns.completed }}</p></div>
            <div class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">Investors · businesses</p><p class="text-2xl font-semibold">{{ r.people.investors }} · {{ r.people.businesses }}</p></div>
        </div>
        <h2 class="mb-3 mt-8 font-semibold">Funding</h2>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4"><div v-for="(v, k) in { Requested: r.money.requested, Committed: r.money.committed, Funded: r.money.funded, Disbursed: r.money.disbursed }" :key="k" class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">{{ k }}</p><p class="tabular text-lg font-semibold">{{ mk(v) }}</p></div></div>
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border bg-card p-5">
                <h2 class="font-semibold">Repayment performance</h2>
                <p class="mt-2 text-3xl font-semibold" :class="r.repayment.rate !== null && r.repayment.rate < 90 ? 'text-expense' : 'text-primary'">{{ r.repayment.rate === null ? 'n/a' : r.repayment.rate + '%' }}</p><p class="text-xs text-muted-foreground">paid of {{ mk(r.repayment.scheduled_due) }} scheduled to date</p>
                <p class="mt-3 text-sm">Overdue loans: <strong>{{ r.repayment.overdue_loans }}</strong> · Defaults: <strong>{{ r.repayment.defaults }}</strong></p>
            </section>
            <section class="rounded-lg border bg-card p-5">
                <h2 class="font-semibold">Platform revenue</h2>
                <ul class="mt-2 space-y-1 text-sm"><li v-for="(v, k) in r.revenue" :key="k" class="flex justify-between"><span>{{ label(k as string) }} fees</span><span class="tabular">{{ mk(v as number) }}</span></li><li v-if="!Object.keys(r.revenue).length" class="text-muted-foreground">No fees yet.</li></ul>
                <p class="mt-3 text-xs text-muted-foreground">Provider processing costs (borne by the platform): {{ mk(r.provider_costs) }}</p>
            </section>
            <section v-for="(title, key) in { by_sector: 'Funding by sector', by_district: 'Funding by district' }" :key="key" class="rounded-lg border bg-card p-5">
                <h2 class="font-semibold">{{ title }}</h2>
                <ul class="mt-3 space-y-2 text-sm"><li v-for="row in r[key]" :key="row.name"><div class="flex justify-between"><span>{{ row.name }}</span><span class="tabular text-muted-foreground">{{ mk(row.total) }}</span></div><div class="mt-1 h-1.5 rounded-full bg-muted"><div class="h-full rounded-full bg-chart-3" :style="{ width: (row.total * 100) / max(r[key]) + '%' }" /></div></li><li v-if="!r[key].length" class="text-muted-foreground">No agreements yet.</li></ul>
            </section>
            <section class="rounded-lg border bg-card p-5">
                <h2 class="font-semibold">Credit turnaround</h2><p class="mt-2 text-3xl font-semibold">{{ r.turnaround_hours === null ? 'n/a' : r.turnaround_hours + ' h' }}</p><p class="text-xs text-muted-foreground">average from submission to decision</p>
            </section>
            <section class="rounded-lg border bg-card p-5">
                <h2 class="font-semibold">Risk and operations</h2>
                <ul class="mt-2 space-y-1 text-sm"><li class="flex justify-between"><span>Open complaints</span><strong>{{ r.risk.complaints_open }}</strong></li><li class="flex justify-between"><span>Open fraud / abuse reports</span><strong>{{ r.risk.fraud_flags }}</strong></li><li class="flex justify-between"><span>Rejected (bad signature) webhooks</span><strong>{{ r.risk.rejected_webhooks }}</strong></li></ul>
            </section>
        </div>
    </AppLayout>
</template>
