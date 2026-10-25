<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import ScheduleTable from '@/components/pw/ScheduleTable.vue';
import Sim from '@/components/pw/Sim.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { date, method, mk, pct } from '@/lib/format';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{ agreement: Record<string, any>; actions: Record<string, boolean>; pending_payment: { id: number; purpose: string; amount: number } | null }>();
const a = props.agreement;
const key = crypto.randomUUID();
const repay = useForm({ purpose: 'repayment', amount: '', idempotency_key: crypto.randomUUID() });
const fund = useForm({ purpose: 'funding', amount: (a.funding_amount / 100).toFixed(2), idempotency_key: key });
const dflt = useForm({ reason: '' });
</script>

<template>
    <Head :title="`Agreement ${a.id}`" />
    <AppLayout>
        <PageHeader :title="`Agreement AGR-${a.id}`" :description="`${a.business} · ${mk(a.principal)} · ${pct(a.rate_bp)} a year`"><Sim v-if="a.simulated" /><Status :value="a.status" /></PageHeader>
        <p v-if="a.simulated" class="mb-6 rounded-lg border border-gold/50 bg-gold/10 p-3 text-sm">This is a <strong>simulated</strong> agreement. No real loan exists and no real money has moved or will move.</p>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">Principal</p><p class="tabular text-lg font-semibold">{{ mk(a.principal) }}</p></div>
            <div class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">Total interest</p><p class="tabular text-lg font-semibold">{{ mk(a.total_interest) }}</p></div>
            <div class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">Total payable</p><p class="tabular text-lg font-semibold">{{ mk(a.total_payable) }}</p></div>
            <div class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">Still to pay</p><p class="tabular text-lg font-semibold">{{ mk(a.outstanding) }}</p><p class="text-xs text-muted-foreground">paid so far {{ mk(a.paid) }}</p></div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section><h2 class="mb-3 font-semibold">Repayment schedule</h2><ScheduleTable :rows="a.schedule" show-paid /><p v-if="a.unapplied" class="mt-2 text-sm text-muted-foreground">Held (unapplied) overpayment: {{ mk(a.unapplied) }}</p></section>
                <section v-if="a.repayments.length"><h2 class="mb-3 font-semibold">Payments received</h2><ul class="divide-y rounded-lg border bg-card text-sm"><li v-for="r in a.repayments" :key="r.id" class="flex justify-between gap-3 p-3" :class="r.reversed ? 'opacity-50 line-through' : ''"><span>{{ date(r.at) }}</span><span class="tabular">{{ mk(r.amount) }} · interest {{ mk(r.interest) }} · principal {{ mk(r.principal) }}</span></li></ul></section>
            </div>
            <aside class="space-y-4">
                <section class="rounded-lg border bg-card p-5 text-sm">
                    <h2 class="font-semibold">Agreed terms</h2>
                    <dl class="mt-3 space-y-1.5"><div class="flex justify-between gap-2"><dt class="text-muted-foreground">Method</dt><dd class="text-right">{{ method(a.method) }}</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Term</dt><dd>{{ a.term_months }} months, {{ a.frequency }}</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Grace</dt><dd>{{ a.grace }} instalment(s)</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Borrower fee</dt><dd>{{ pct(a.fee_rates.origination_bp) }} = {{ mk(a.fees.borrower_total) }}</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Investor fee</dt><dd>{{ pct(a.fee_rates.investor_service_bp) }} = {{ mk(a.fees.investor_total) }}</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Business receives</dt><dd class="font-medium">{{ mk(a.fees.net_to_business) }}</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Investor pays in</dt><dd class="font-medium">{{ mk(a.fees.investor_pays) }}</dd></div></dl>
                    <p class="mt-3 text-xs text-muted-foreground">Fees were disclosed in the accepted offer and cannot change. Agreement fingerprint #{{ a.hash }}.</p>
                </section>
                <section v-if="actions.can_fund" class="rounded-lg border-2 border-primary bg-card p-5">
                    <h2 class="font-semibold">Fund this loan</h2><p class="mt-1 text-sm text-muted-foreground">Pay {{ mk(a.funding_amount) }} (principal plus the disclosed investor fee) through the sandbox. Nothing is confirmed until the provider verifies it.</p>
                    <Button class="mt-3 w-full" :disabled="fund.processing" @click="fund.post(route('agreements.pay', a.id))">Pay {{ mk(a.funding_amount) }} (sandbox)</Button>
                </section>
                <section v-if="actions.can_repay" class="rounded-lg border-2 border-primary bg-card p-5">
                    <h2 class="font-semibold">Make a repayment</h2>
                    <form class="mt-3 space-y-3" @submit.prevent="repay.post(route('agreements.pay', a.id))"><FormField label="Amount (MK)" for="ra" :error="repay.errors.amount" hint="Oldest instalment first; interest before principal."><Input id="ra" v-model="repay.amount" inputmode="decimal" required /></FormField><Button type="submit" class="w-full" :disabled="repay.processing">Pay (sandbox)</Button></form>
                </section>
                <section v-if="pending_payment" class="rounded-lg border bg-card p-4 text-sm"><p>A {{ pending_payment.purpose }} payment of {{ mk(pending_payment.amount) }} is waiting.</p><Link :href="route('payments.sandbox', pending_payment.id)" class="text-primary hover:underline">Continue in the sandbox</Link></section>
                <section v-if="a.disbursement" class="rounded-lg border bg-card p-4 text-sm"><p class="font-medium">Disbursement to the business: <Status :value="a.disbursement.status" /></p><p class="mt-1 text-muted-foreground">Net {{ mk(a.disbursement.net) }} after fees {{ mk(a.disbursement.fees) }}</p><Button v-if="actions.can_approve_disbursement" class="mt-3 w-full" @click="router.post(route('finance.disbursement', a.disbursement.id))">Approve release (finance)</Button></section>
                <section v-if="actions.can_default" class="rounded-lg border bg-card p-4 text-sm"><h2 class="font-semibold">Mark as defaulted</h2><p class="text-xs text-muted-foreground">Only after the contractual period past due, with a written reason.</p><form class="mt-2 space-y-2" @submit.prevent="dflt.post(route('agreements.default', a.id))"><Input v-model="dflt.reason" placeholder="Reason" /><p v-if="dflt.errors.default" class="text-xs text-destructive">{{ dflt.errors.default }}</p><Button type="submit" variant="destructive" size="sm" :disabled="dflt.processing">Record default</Button></form></section>
            </aside>
        </div>
    </AppLayout>
</template>
