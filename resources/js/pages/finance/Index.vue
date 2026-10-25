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

const props = defineProps<{ payouts: { id: number; agreement_id: number; investor: string; amount: number; bank: string | null; account: string | null; ready: boolean; details_recent: boolean }[]; trial_balance: { account: string; balance: number }[]; trial_total: number; fees: any[]; payments: any[]; disbursements: any[]; approvals: any[]; fee_schedule: { name: string; effective_from: string; tax_bp: number; processing_bp: number; tiers: any[] } | null }>();
const can = usePage().props.can as Record<string, boolean>;
const blank = () => ({ min: '0', max: '', origination_bp: 0, origination_fixed: '0', investor_bp: 0, investor_fixed: '0' });
const fee = useForm({ name: '', effective_from: new Date().toISOString().slice(0, 10), tax_bp: props.fee_schedule?.tax_bp ?? 0, processing_bp: props.fee_schedule?.processing_bp ?? 0, reason: '', tiers: [blank()] });
const addTier = () => fee.tiers.push({ ...blank(), min: fee.tiers.at(-1)?.max ?? '0' });
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
            <ul class="divide-y rounded-lg border-2 border-gold/50 bg-card"><li v-for="d in disbursements" :key="d.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm"><div><p class="font-medium">AGR-{{ d.agreement_id }} · {{ d.business }}</p><p class="text-muted-foreground">Gross {{ mk(d.gross) }} − fees {{ mk(d.fees) }} = <strong>{{ mk(d.net) }}</strong> to the business</p></div><div class="text-right"><p class="text-xs" :class="d.ready ? 'text-primary' : 'text-gold'">Campaign {{ d.funded_pct }}% funded · minimum {{ d.min_pct }}%</p><Button v-if="can['finance.approve']" size="sm" class="mt-1" :disabled="!d.ready" @click="router.post(route('finance.disbursement', d.id))">{{ d.ready ? 'Approve (simulated)' : 'Waiting for the campaign' }}</Button></div></li></ul></section>

        <section v-if="payouts.length" class="mb-8"><h2 class="mb-3 font-semibold">Payouts to investors awaiting release</h2>
            <ul class="divide-y rounded-lg border bg-card"><li v-for="x in payouts" :key="x.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm"><div><p class="font-medium">{{ x.investor }} · AGR-{{ x.agreement_id }}</p><p class="text-muted-foreground">{{ mk(x.amount) }} to {{ x.bank ?? 'no bank on file' }} {{ x.account }}</p><p v-if="x.details_recent" class="text-xs text-gold">Bank details changed in the last 3 days: confirm with the investor before releasing.</p></div><Button v-if="can['finance.approve']" size="sm" :disabled="!x.ready" @click="router.post(route('finance.payout', x.id))">Release (simulated)</Button></li></ul></section>

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
            <section class="rounded-lg border bg-card p-5">
                <h2 class="font-semibold">Fee schedule</h2>
                <p v-if="!fee_schedule" class="mt-2 rounded-md border border-gold/50 bg-gold/10 p-3 text-sm"><strong>No approved fee schedule.</strong> No fees are being charged. Offers and agreements show zero fees until a schedule is approved.</p>
                <template v-else>
                    <p class="mt-1 text-xs text-muted-foreground">{{ fee_schedule.name }} · effective {{ fee_schedule.effective_from }} · provider processing {{ fee_schedule.processing_bp / 100 }}% (borne by the platform) · tax on fees {{ fee_schedule.tax_bp / 100 }}%</p>
                    <table class="mt-3 w-full text-sm"><thead class="text-left text-xs uppercase text-muted-foreground"><tr><th>Loan amount</th><th>Borrower fee</th><th>Investor fee</th></tr></thead><tbody class="divide-y"><tr v-for="(t, i) in fee_schedule.tiers" :key="i"><td class="py-1.5">{{ mk(t.min) }} – {{ t.max === null ? 'and above' : mk(t.max) }}</td><td>{{ t.origination_bp / 100 }}%<span v-if="t.origination_fixed"> + {{ mk(t.origination_fixed) }}</span></td><td>{{ t.investor_bp / 100 }}%<span v-if="t.investor_fixed"> + {{ mk(t.investor_fixed) }}</span></td></tr></tbody></table>
                </template>
                <details v-if="can['finance.approve']" class="mt-4"><summary class="cursor-pointer text-sm font-medium text-primary">Propose a new schedule (needs a second approver)</summary>
                    <p class="mt-2 text-xs text-muted-foreground">Policy from the business plan: one simple service charge of 4% to 15% (400–1500 bp) paid by the business. No investor fee, no fixed charges, and no monitoring, review, security or early-repayment fees.</p>
                    <form class="mt-3 space-y-3" @submit.prevent="fee.post(route('finance.fee-schedules'))">
                        <div class="grid grid-cols-2 gap-3"><FormField label="Name" for="fn" :error="fee.errors.name"><Input id="fn" v-model="fee.name" /></FormField><FormField label="Effective from" for="fe" :error="fee.errors.effective_from"><Input id="fe" v-model="fee.effective_from" type="date" /></FormField><FormField label="Provider processing (bp)" for="f3" :error="fee.errors.processing_bp"><Input id="f3" v-model.number="fee.processing_bp" type="number" /></FormField><FormField label="Tax on fees (bp)" for="f4" :error="fee.errors.tax_bp"><Input id="f4" v-model.number="fee.tax_bp" type="number" /></FormField></div>
                        <div v-for="(t, i) in fee.tiers" :key="i" class="grid grid-cols-3 gap-2 rounded-md border p-3 text-xs"><FormField label="From (MK)" :for="`tm${i}`"><Input :id="`tm${i}`" v-model="t.min" /></FormField><FormField label="Up to (MK, blank = no limit)" :for="`tx${i}`"><Input :id="`tx${i}`" v-model="t.max" /></FormField><span /><FormField label="Borrower % (bp)" :for="`ob${i}`"><Input :id="`ob${i}`" v-model.number="t.origination_bp" type="number" /></FormField><FormField label="Borrower fixed (MK)" :for="`of${i}`"><Input :id="`of${i}`" v-model="t.origination_fixed" /></FormField><span /><FormField label="Investor % (bp)" :for="`ib${i}`"><Input :id="`ib${i}`" v-model.number="t.investor_bp" type="number" /></FormField><FormField label="Investor fixed (MK)" :for="`if${i}`"><Input :id="`if${i}`" v-model="t.investor_fixed" /></FormField></div>
                        <p v-if="fee.errors.tiers" class="text-sm text-destructive">{{ fee.errors.tiers }}</p>
                        <Button type="button" size="sm" variant="outline" @click="addTier">Add tier</Button>
                        <FormField label="Reason" for="fr" :error="fee.errors.reason"><Input id="fr" v-model="fee.reason" /></FormField>
                        <Button type="submit" :disabled="fee.processing">Propose schedule</Button>
                    </form>
                </details>
            </section>
        </div>
    </AppLayout>
</template>
