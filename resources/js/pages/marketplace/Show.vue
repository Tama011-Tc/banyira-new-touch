<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import ProgressBar from '@/components/pw/ProgressBar.vue';
import Risk from '@/components/pw/Risk.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { closes, date, method, mk, pct } from '@/lib/format';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, BadgeCheck, Bookmark, CheckCircle2, Circle, Lock, TriangleAlert } from 'lucide-vue-next';

const props = defineProps<{ listing: Record<string, any>; investor: { kyc_status: string | null; risk_acknowledged: boolean }; conversation_id: number | null }>();
const l = props.listing;
const role = (usePage().props.auth.user as { role: string }).role;
const ready = props.investor.kyc_status === 'verified' && props.investor.risk_acknowledged;
const fees = l.fees;
const steps = [
    { done: props.investor.kyc_status === 'verified', text: props.investor.kyc_status === 'submitted' ? 'Identity verification (under review)' : 'Verify your identity' },
    { done: props.investor.risk_acknowledged, text: 'Accept the risk statement' },
];
</script>

<template>
    <Head :title="l.title" />
    <AppLayout>
        <Link :href="route('marketplace.index')" class="mb-3 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"><ArrowLeft class="size-4" aria-hidden="true" />All opportunities</Link>
        <PageHeader :title="l.title"><Risk :category="l.risk_category" /></PageHeader>
        <p class="-mt-4 mb-6 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
            <span class="inline-flex items-center gap-1 font-medium text-foreground">{{ l.business }}<BadgeCheck v-if="l.verified" class="size-4 text-primary" aria-label="Business verified" /></span>
            <span aria-hidden="true">·</span><span>{{ l.sector }}</span><span aria-hidden="true">·</span><span>{{ l.district }}</span><span aria-hidden="true">·</span><span>{{ l.years_operating }} years trading</span>
        </p>

        <div class="grid gap-6 lg:grid-cols-3">
            <aside class="lg:order-last">
                <div class="space-y-3 lg:sticky lg:top-4">
                    <section class="rounded-lg border bg-card p-5">
                        <p class="text-sm text-muted-foreground">Seeking</p>
                        <p class="tabular text-3xl font-semibold tracking-tight">{{ mk(l.target) }}</p>
                        <div class="mt-4"><ProgressBar :value="l.percent" label="Funding progress" /></div>
                        <div class="mt-2 flex justify-between text-xs text-muted-foreground"><span><span class="font-medium text-foreground">{{ l.percent }}%</span> agreed</span><span>{{ closes(l.deadline) }}</span></div>

                        <dl class="mt-4 grid grid-cols-3 gap-2 border-y py-3 text-center">
                            <div><dt class="text-xs text-muted-foreground">Rate</dt><dd class="tabular font-semibold text-primary">{{ pct(l.rate_bp) }}</dd></div>
                            <div class="border-x"><dt class="text-xs text-muted-foreground">Term</dt><dd class="font-semibold">{{ l.term_months }} mo</dd></div>
                            <div><dt class="text-xs text-muted-foreground">Available</dt><dd class="tabular text-sm font-semibold">{{ mk(l.remaining) }}</dd></div>
                        </dl>
                        <p class="mt-3 text-xs text-muted-foreground">You can lend {{ mk(l.min_investment) }} to {{ mk(l.max_investment) }}.</p>

                        <div v-if="role === 'investor'" class="mt-4 space-y-2">
                            <Button v-if="conversation_id" class="w-full" size="lg" as-child><Link :href="route('conversations.show', conversation_id)">Open conversation</Link></Button>
                            <Button v-else-if="ready && l.status === 'published'" class="w-full" size="lg" @click="router.post(route('marketplace.contact', l.id))">Ask a question or make an offer</Button>
                            <div v-else-if="!ready" class="rounded-md border bg-muted/50 p-3 text-sm">
                                <p class="flex items-center gap-1.5 font-medium"><Lock class="size-3.5" aria-hidden="true" />Two steps before you can make an offer</p>
                                <ul class="mt-2 space-y-1.5">
                                    <li v-for="s in steps" :key="s.text" class="flex items-center gap-2" :class="s.done ? 'text-muted-foreground line-through' : ''"><CheckCircle2 v-if="s.done" class="size-4 text-primary" aria-label="Done" /><Circle v-else class="size-4 text-muted-foreground" aria-label="To do" />{{ s.text }}</li>
                                </ul>
                                <Button size="sm" class="mt-3 w-full" as-child><Link :href="route('investor.profile')">Complete my profile</Link></Button>
                            </div>
                            <Button variant="outline" class="w-full" :aria-pressed="l.saved" @click="router.post(route('watchlist.toggle', l.id), {}, { preserveScroll: true })"><Bookmark class="size-4" :class="l.saved ? 'fill-gold text-gold' : ''" aria-hidden="true" />{{ l.saved ? 'Saved to watchlist' : 'Save to watchlist' }}</Button>
                        </div>
                    </section>
                    <p class="px-1 text-xs text-muted-foreground">Negotiation happens on-platform. Business contact details are never shared.</p>
                </div>
            </aside>

            <div class="space-y-6 lg:col-span-2">
                <div class="flex gap-3 rounded-lg border border-gold/50 bg-gold/10 p-4 text-sm"><TriangleAlert class="mt-0.5 size-4 shrink-0 text-gold" aria-hidden="true" /><p><strong>Risk warning.</strong> You can lose some or all of the money you lend. The credit category is an estimate made on {{ date(l.assessed_on) }} and does not guarantee repayment. Interest shown is the contractual rate if the loan performs; it is not guaranteed income.</p></div>

                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">About the business</h2>
                    <p class="mt-2 text-sm leading-relaxed">{{ l.summary }}</p>
                    <h3 class="mt-5 text-sm font-semibold">What the money is for</h3>
                    <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ l.purpose }}</p>
                </section>

                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Key risks and limitations</h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ l.risk_disclosure }}</p>
                </section>

                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Terms and fees</h2>
                    <dl class="mt-3 divide-y text-sm">
                        <div class="flex flex-col gap-0.5 py-2.5 sm:flex-row sm:justify-between sm:gap-4"><dt class="text-muted-foreground">Indicative rate</dt><dd class="sm:text-right">{{ pct(l.rate_bp) }} a year <span class="text-muted-foreground">(negotiable up to {{ pct(l.max_rate_bp) }})</span></dd></div>
                        <div class="flex flex-col gap-0.5 py-2.5 sm:flex-row sm:justify-between sm:gap-4"><dt class="text-muted-foreground">How interest is calculated</dt><dd class="sm:text-right">{{ method(l.interest_method) }}</dd></div>
                        <div class="flex flex-col gap-0.5 py-2.5 sm:flex-row sm:justify-between sm:gap-4"><dt class="text-muted-foreground">Term and repayments</dt><dd class="sm:text-right">Up to {{ l.term_months }} months, paid {{ l.frequency }}</dd></div>
                        <div class="flex flex-col gap-0.5 py-2.5 sm:flex-row sm:justify-between sm:gap-4"><dt class="text-muted-foreground">Your investment</dt><dd class="sm:text-right">{{ mk(l.min_investment) }} to {{ mk(l.max_investment) }}</dd></div>
                        <div class="flex flex-col gap-0.5 py-2.5 sm:flex-row sm:justify-between sm:gap-4"><dt class="text-muted-foreground">Payment-provider charges</dt><dd class="sm:text-right">{{ pct(fees.processing_bp) }} per payment, paid by the platform</dd></div>
                    </dl>
                    <h3 class="mt-4 text-sm font-semibold">Platform fees</h3>
                    <p v-if="!fees.approved" class="mt-1 text-sm text-muted-foreground">No platform fee is currently approved, so none would be charged. Fees are always shown on an offer before anyone accepts it.</p>
                    <template v-else>
                        <div class="mt-2 overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/60 text-left text-xs text-muted-foreground"><tr><th class="px-3 py-2 font-medium">Loan size</th><th class="px-3 py-2 font-medium">Business pays</th><th class="px-3 py-2 font-medium">You pay</th></tr></thead>
                                <tbody class="divide-y">
                                    <tr v-for="(t, i) in l.fee_tiers" :key="i">
                                        <td class="whitespace-nowrap px-3 py-2">{{ mk(t.min) }} – {{ t.max === null ? 'above' : mk(t.max) }}</td>
                                        <td class="px-3 py-2">{{ pct(t.origination_bp) }}<span v-if="t.origination_fixed"> + {{ mk(t.origination_fixed) }}</span></td>
                                        <td class="px-3 py-2">{{ pct(t.investor_bp) }}<span v-if="t.investor_fixed"> + {{ mk(t.investor_fixed) }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">The business fee is deducted from what the business receives. Your fee is added to your payment.</p>
                    </template>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
