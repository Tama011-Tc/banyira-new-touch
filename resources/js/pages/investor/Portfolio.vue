<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Risk from '@/components/pw/Risk.vue';
import Sim from '@/components/pw/Sim.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { mk, pct } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Download, PieChart, TriangleAlert } from 'lucide-vue-next';

const props = defineProps<{ summary: Record<string, any>; investments: { id: number; business: string; principal: number; rate_bp: number; term_months: number; status: string; risk: string; total_payable: number }[] }>();
const s = props.summary;
const secondary = [
    ['Committed', s.committed], ['Deployed (funded)', s.deployed], ['Principal repaid', s.principal_received], ['Interest received', s.interest_received],
    ['Paid out to your bank', s.paid_out], ['Awaiting payout', s.awaiting_payout], ['Fees paid', s.fees_paid],
] as [string, number][];
const breakdowns = { by_sector: 'By sector', by_risk: 'By risk category', by_district: 'By district' } as const;
const share = (rows: { total: number }[], total: number) => {
    const sum = rows.reduce((a, r) => a + r.total, 0);
    return sum ? Math.round((total * 100) / sum) : 0;
};
</script>

<template>
    <Head title="Portfolio" />
    <AppLayout>
        <PageHeader title="Portfolio" description="Realised amounts are what has actually been received. Projected interest is shown separately and is not guaranteed.">
            <Sim /><Button variant="outline" size="sm" as-child><a :href="route('portfolio.statement')"><Download class="size-4" aria-hidden="true" />Statement (CSV)</a></Button>
        </PageHeader>

        <EmptyState v-if="!investments.length" :icon="PieChart" title="No investments yet" description="Agree terms with a business to start building a portfolio."><Button as-child><Link :href="route('marketplace.index')">Browse opportunities</Link></Button></EmptyState>

        <template v-else>
            <div v-if="s.overdue_exposure > 0" class="mb-4 flex gap-3 rounded-lg border border-expense/40 bg-expense/10 p-4 text-sm" role="status">
                <TriangleAlert class="mt-0.5 size-4 shrink-0 text-expense" aria-hidden="true" />
                <p><strong>{{ mk(s.overdue_exposure) }} is overdue</strong> across your loans. We follow up with the businesses and will notify you of any change.</p>
            </div>

            <section class="grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-3" aria-label="Headline figures">
                <div class="bg-card p-5"><p class="text-sm text-muted-foreground">Principal outstanding</p><p class="tabular mt-1 text-3xl font-semibold tracking-tight">{{ mk(s.principal_outstanding) }}</p><p class="mt-1 text-xs text-muted-foreground">Still owed to you by businesses</p></div>
                <div class="bg-card p-5"><p class="text-sm text-muted-foreground">Net realised return</p><p class="tabular mt-1 text-3xl font-semibold tracking-tight" :class="s.net_realised > 0 ? 'text-income' : s.net_realised < 0 ? 'text-expense' : ''">{{ mk(s.net_realised) }}</p><p class="mt-1 text-xs text-muted-foreground">Interest received minus fees</p></div>
                <div class="bg-card p-5"><p class="text-sm text-muted-foreground">Projected interest</p><p class="tabular mt-1 text-3xl font-semibold tracking-tight text-muted-foreground">{{ mk(s.projected_interest) }}</p><p class="mt-1 text-xs text-muted-foreground">If every instalment is paid on time. Not a promise.</p></div>
            </section>

            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 rounded-lg border bg-card p-4 sm:grid-cols-4 lg:grid-cols-7">
                <div v-for="[name, value] in secondary" :key="name"><dt class="text-xs text-muted-foreground">{{ name }}</dt><dd class="tabular mt-0.5 font-semibold">{{ mk(value) }}</dd></div>
            </dl>

            <h2 class="mb-3 mt-8 font-semibold">Investments <span class="font-normal text-muted-foreground">({{ investments.length }})</span></h2>
            <ul class="divide-y overflow-hidden rounded-lg border bg-card">
                <li v-for="i in investments" :key="i.id">
                    <Link :href="route('agreements.show', i.id)" class="flex items-center gap-3 p-4 text-sm hover:bg-muted/50">
                        <div class="min-w-0 grow">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1"><p class="truncate font-medium">{{ i.business }}</p><Status :value="i.status" /></div>
                            <p class="mt-1 text-muted-foreground"><span class="tabular font-medium text-foreground">{{ mk(i.principal) }}</span> at {{ pct(i.rate_bp) }} for {{ i.term_months }} months · expected total {{ mk(i.total_payable) }}</p>
                            <div class="mt-1.5"><Risk :category="i.risk" /></div>
                        </div>
                        <ChevronRight class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                    </Link>
                </li>
            </ul>

            <h2 class="mb-3 mt-8 font-semibold">Where your money is</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <section v-for="(title, key) in breakdowns" :key="key" class="rounded-lg border bg-card p-4">
                    <h3 class="text-sm font-semibold">{{ title }}</h3>
                    <ul class="mt-3 space-y-2.5 text-sm">
                        <li v-for="r in s[key]" :key="r.name">
                            <div class="flex justify-between gap-2"><span class="truncate">{{ r.name }}</span><span class="tabular shrink-0 text-muted-foreground">{{ mk(r.total) }}</span></div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted" aria-hidden="true"><div class="h-full rounded-full bg-primary/70" :style="{ width: share(s[key], r.total) + '%' }" /></div>
                        </li>
                        <li v-if="!s[key].length" class="text-muted-foreground">No funded loans yet.</li>
                    </ul>
                </section>
            </div>
            <p class="mt-2 text-xs text-muted-foreground">Spreading money across businesses can reduce the impact of one failure. It does not remove the risk of loss.</p>
        </template>
    </AppLayout>
</template>
