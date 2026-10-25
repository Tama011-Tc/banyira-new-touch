<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Sim from '@/components/pw/Sim.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { label, mk } from '@/lib/format';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps<{ trial_balance: { account: string; balance: number }[]; trial_total: number; fees: any[]; payments: any[]; disbursements: any[]; approvals: any[]; fee_settings: Record<string, number> }>();
const can = usePage().props.can as Record<string, boolean>;
const fee = useForm({ origination_bp: props.fee_settings['fees.origination_bp'], investor_service_bp: props.fee_settings['fees.investor_service_bp'], processing_bp: props.fee_settings['fees.processing_bp'], tax_bp: props.fee_settings['fees.tax_bp'], reason: '' });
const reverse = (id: number) => {
    const reason = prompt('Why should this payment be reversed? (a second person must approve)');
    if (reason) router.post(route('finance.reversal', id), { reason });
};
</script>

<template>
    <Head title="Finance" />
    <AppLayout>
        <PageHeader title="Finance" description="Reconciliation, fees, disbursements and the ledger. Entries are append-only; corrections are reversals."><Sim /><Button variant="outline" as-child><Link :href="route('finance.ledger')">Full ledger</Link></Button></PageHeader>

        <section v-if="disbursements.length" class="mb-8"><h2 class="mb-3 font-semibold">Disbursements awaiting approval</h2>
            <ul class="divide-y rounded-lg border-2 border-gold/50 bg-card"><li v-for="d in disbursements" :key="d.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm"><div><p class="font-medium">AGR-{{ d.agreement_id }} · {{ d.business }}</p><p class="text-muted-foreground">Gross {{ mk(d.gross) }} − fees {{ mk(d.fees) }} = <strong>{{ mk(d.net) }}</strong> to the business</p></div><Button v-if="can['finance.approve']" size="sm" @click="router.post(route('finance.disbursement', d.id))">Approve (simulated)</Button></li></ul></section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border bg-card p-5"><h2 class="font-semibold">Trial balance</h2><p class="text-xs text-muted-foreground">Debits minus credits per account. The total must be zero.</p>
                <table class="mt-3 w-full text-sm"><tbody class="divide-y"><tr v-for="t in trial_balance" :key="t.account"><td class="py-1.5">{{ label(t.account) }}</td><td class="tabular py-1.5 text-right">{{ mk(t.balance) }}</td></tr></tbody><tfoot><tr class="border-t-2 font-semibold"><td class="py-2">Total</td><td class="tabular py-2 text-right" :class="trial_total === 0 ? 'text-primary' : 'text-expense'">{{ mk(trial_total) }}</td></tr></tfoot></table></section>
            <section class="rounded-lg border bg-card p-5"><h2 class="font-semibold">Fees</h2>
                <table class="mt-3 w-full text-sm"><thead class="text-left text-xs uppercase text-muted-foreground"><tr><th>Type</th><th>Payer</th><th>Status</th><th class="text-right">Amount</th></tr></thead><tbody class="divide-y"><tr v-for="(f, i) in fees" :key="i"><td class="py-1.5">{{ label(f.fee_type) }}</td><td>{{ f.payer }}</td><td>{{ f.status }} ({{ f.n }})</td><td class="tabular text-right">{{ mk(Number(f.amount)) }}</td></tr><tr v-if="!fees.length"><td colspan="4" class="py-3 text-muted-foreground">No fees yet.</td></tr></tbody></table></section>
        </div>

        <section class="mt-8"><h2 class="mb-3 font-semibold">Recent payments</h2>
            <div class="overflow-x-auto rounded-lg border bg-card"><table class="w-full min-w-[40rem] text-sm"><thead class="bg-muted/60 text-left text-xs uppercase text-muted-foreground"><tr><th class="px-3 py-2">Reference</th><th class="px-3 py-2">By</th><th class="px-3 py-2">Purpose</th><th class="px-3 py-2 text-right">Amount</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Reconciled</th><th /></tr></thead>
                <tbody class="divide-y"><tr v-for="p in payments" :key="p.id"><td class="px-3 py-2 font-mono text-xs">{{ p.ref }}</td><td class="px-3 py-2">{{ p.who }}</td><td class="px-3 py-2 capitalize">{{ p.purpose }}</td><td class="tabular px-3 py-2 text-right">{{ mk(p.amount) }}</td><td class="px-3 py-2"><Status :value="p.status" /></td><td class="px-3 py-2"><Status v-if="p.reconciled" :value="p.reconciled" /><span v-else class="text-muted-foreground">-</span></td>
                    <td class="space-x-2 px-3 py-2 text-right"><Button v-if="can['finance.reconcile'] && p.status === 'succeeded'" size="sm" variant="outline" @click="router.post(route('finance.reconcile', p.id))">Reconcile</Button><Button v-if="can['finance.approve'] && p.status === 'succeeded'" size="sm" variant="ghost" @click="reverse(p.id)">Request reversal</Button></td></tr></tbody></table></div></section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border bg-card p-5"><h2 class="font-semibold">Approval requests (four-eyes)</h2>
                <ul class="mt-3 divide-y text-sm"><li v-for="r in approvals" :key="r.id" class="py-3"><div class="flex items-center justify-between gap-2"><strong>{{ label(r.type) }}</strong><Status :value="r.status" /></div><p class="text-muted-foreground">{{ r.reason }} · requested by {{ r.by }}</p>
                    <div v-if="r.status === 'pending' && !r.mine && can['finance.approve']" class="mt-2 flex gap-2"><Button size="sm" @click="router.post(route('finance.approval', r.id), { approve: true })">Approve</Button><Button size="sm" variant="outline" @click="router.post(route('finance.approval', r.id), { approve: false })">Reject</Button></div>
                    <Button v-if="r.status === 'approved' && r.type === 'reversal'" size="sm" class="mt-2" @click="router.post(route('finance.execute', r.id))">Execute reversal</Button><p v-if="r.status === 'pending' && r.mine" class="mt-1 text-xs text-muted-foreground">Waiting for someone else to decide.</p></li><li v-if="!approvals.length" class="py-3 text-muted-foreground">None.</li></ul></section>
            <section v-if="can['finance.approve']" class="rounded-lg border bg-card p-5"><h2 class="font-semibold">Request a fee change</h2><p class="text-xs text-muted-foreground">Rates in basis points (100 = 1%). Needs a second approver. Existing agreements keep the fees they were signed with.</p>
                <form class="mt-3 space-y-3" @submit.prevent="fee.post(route('finance.fees'))"><div class="grid grid-cols-2 gap-3"><FormField label="Borrower origination" for="f1" :error="fee.errors.origination_bp"><Input id="f1" v-model.number="fee.origination_bp" type="number" /></FormField><FormField label="Investor service" for="f2" :error="fee.errors.investor_service_bp"><Input id="f2" v-model.number="fee.investor_service_bp" type="number" /></FormField><FormField label="Provider processing" for="f3" :error="fee.errors.processing_bp"><Input id="f3" v-model.number="fee.processing_bp" type="number" /></FormField><FormField label="Tax on fees" for="f4" :error="fee.errors.tax_bp"><Input id="f4" v-model.number="fee.tax_bp" type="number" /></FormField></div><FormField label="Reason" for="f5" :error="fee.errors.reason"><Input id="f5" v-model="fee.reason" /></FormField><Button type="submit" :disabled="fee.processing">Request change</Button></form></section>
        </div>
    </AppLayout>
</template>
