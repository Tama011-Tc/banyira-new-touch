<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import ProgressBar from '@/components/pw/ProgressBar.vue';
import Risk from '@/components/pw/Risk.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { date, method, mk, pct } from '@/lib/format';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { TriangleAlert } from 'lucide-vue-next';

const props = defineProps<{ listing: Record<string, any>; investor: { kyc_status: string | null; risk_acknowledged: boolean }; conversation_id: number | null }>();
const l = props.listing;
const role = (usePage().props.auth.user as { role: string }).role;
const ready = props.investor.kyc_status === 'verified' && props.investor.risk_acknowledged;
const fees = l.fees;
</script>

<template>
    <Head :title="l.title" />
    <AppLayout>
        <PageHeader :title="l.title" :description="`${l.business} · ${l.district} · ${l.sector} · ${l.years_operating} years trading`"><Risk :category="l.risk_category" /></PageHeader>

        <div class="mb-6 flex gap-3 rounded-lg border border-gold/50 bg-gold/10 p-4 text-sm"><TriangleAlert class="mt-0.5 size-4 shrink-0 text-gold" aria-hidden="true" /><p><strong>Risk warning.</strong> You can lose some or all of the money you lend. The credit category is an estimate made on {{ date(l.assessed_on) }} and does not guarantee repayment. Interest shown is the contractual rate if the loan performs; it is not guaranteed income.</p></div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-lg border bg-card p-5"><h2 class="font-semibold">About the business</h2><p class="mt-2 text-sm">{{ l.summary }}</p><h3 class="mt-4 text-sm font-medium">What the money is for</h3><p class="mt-1 text-sm text-muted-foreground">{{ l.purpose }}</p></section>
                <section class="rounded-lg border bg-card p-5"><h2 class="font-semibold">Key risks and limitations</h2><p class="mt-2 text-sm text-muted-foreground">{{ l.risk_disclosure }}</p></section>
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Terms and fees</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-muted-foreground">Indicative rate</dt><dd>{{ pct(l.rate_bp) }} a year (negotiable up to {{ pct(l.max_rate_bp) }})</dd></div>
                        <div><dt class="text-muted-foreground">How interest is calculated</dt><dd>{{ method(l.interest_method) }}</dd></div>
                        <div><dt class="text-muted-foreground">Term · repayments</dt><dd>Up to {{ l.term_months }} months · {{ l.frequency }}</dd></div>
                        <div><dt class="text-muted-foreground">Your investment</dt><dd>{{ mk(l.min_investment) }} to {{ mk(l.max_investment) }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-muted-foreground">Platform fees</dt>
                            <dd v-if="!fees.approved">No platform fee is currently approved, so none would be charged. Fees are always shown on an offer before anyone accepts it.</dd>
                            <dd v-else><ul class="mt-1 space-y-0.5"><li v-for="(t, i) in l.fee_tiers" :key="i">Loans {{ mk(t.min) }} – {{ t.max === null ? 'and above' : mk(t.max) }}: borrower {{ pct(t.origination_bp) }}<span v-if="t.origination_fixed"> + {{ mk(t.origination_fixed) }}</span>, investor {{ pct(t.investor_bp) }}<span v-if="t.investor_fixed"> + {{ mk(t.investor_fixed) }}</span> (borrower fee is deducted from what the business receives; investor fee is added to your payment)</li></ul></dd></div>
                        <div class="sm:col-span-2"><dt class="text-muted-foreground">Payment-provider charges</dt><dd>{{ pct(fees.processing_bp) }} per payment, paid by the platform</dd></div>
                    </dl>
                </section>
            </div>
            <aside class="space-y-4">
                <section class="rounded-lg border bg-card p-5">
                    <p class="tabular text-2xl font-semibold">{{ mk(l.target) }}</p><p class="text-sm text-muted-foreground">target · {{ mk(l.remaining) }} still available</p>
                    <div class="mt-3"><ProgressBar :value="l.percent" :label="'Funding progress'" /></div><p class="mt-1 text-xs text-muted-foreground">{{ l.percent }}% agreed · closes {{ date(l.deadline) }}</p>
                    <div v-if="role === 'investor'" class="mt-5 space-y-2">
                        <Button v-if="conversation_id" class="w-full" as-child><Link :href="route('conversations.show', conversation_id)">Open conversation</Link></Button>
                        <Button v-else-if="ready && l.status === 'published'" class="w-full" @click="router.post(route('marketplace.contact', l.id))">Ask a question or make an offer</Button>
                        <div v-else-if="!ready" class="rounded-md bg-muted p-3 text-sm"><p>To contact the business or invest you need to:</p><ul class="mt-1 list-disc pl-5"><li :class="investor.kyc_status === 'verified' ? 'line-through opacity-60' : ''">verify your identity</li><li :class="investor.risk_acknowledged ? 'line-through opacity-60' : ''">accept the risk statement</li></ul><Button size="sm" class="mt-3" as-child><Link :href="route('investor.profile')">Complete my profile</Link></Button></div>
                        <Button variant="outline" class="w-full" @click="router.post(route('watchlist.toggle', l.id), {}, { preserveScroll: true })">{{ l.saved ? 'Remove from watchlist' : 'Save to watchlist' }}</Button>
                    </div>
                </section>
                <p class="text-xs text-muted-foreground">Negotiation happens on-platform. Business contact details are never shared.</p>
            </aside>
        </div>
    </AppLayout>
</template>
