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
import { PieChart } from 'lucide-vue-next';

defineProps<{ summary: Record<string, any>; investments: { id: number; business: string; principal: number; rate_bp: number; term_months: number; status: string; risk: string; total_payable: number }[] }>();
const tiles = (s: Record<string, any>) => [
    ['Committed', s.committed], ['Deployed (funded)', s.deployed], ['Principal outstanding', s.principal_outstanding], ['Principal repaid', s.principal_received],
    ['Interest received', s.interest_received], ['Fees paid', s.fees_paid], ['Net realised return', s.net_realised], ['Overdue exposure', s.overdue_exposure],
];
</script>

<template>
    <Head title="Portfolio" />
    <AppLayout>
        <PageHeader title="Portfolio" description="Realised amounts are what has actually been received. Projected interest is shown separately and is not guaranded."><Sim /><Button variant="outline" as-child><a :href="route('portfolio.statement')">Download statement (CSV)</a></Button></PageHeader>
        <EmptyState v-if="!investments.length" :icon="PieChart" title="No investments yet" description="Agree terms with a business to start building a portfolio."><Button as-child><Link :href="route('marketplace.index')">Browse opportunities</Link></Button></EmptyState>
        <template v-else>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4"><div v-for="t in tiles(summary)" :key="t[0] as string" class="rounded-lg border bg-card p-4"><p class="text-xs text-muted-foreground">{{ t[0] }}</p><p class="tabular mt-1 text-lg font-semibold" :class="t[0] === 'Overdue exposure' && (t[1] as number) > 0 ? 'text-expense' : ''">{{ mk(t[1] as number) }}</p></div></div>
            <p class="mt-3 text-xs text-muted-foreground">Projected interest on funded loans if every instalment is paid on time: {{ mk(summary.projected_interest) }}. This is a projection, not a promise.</p>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div v-for="(title, key) in { by_sector: 'By sector', by_risk: 'By risk category', by_district: 'By district' }" :key="key" class="rounded-lg border bg-card p-4"><h2 class="text-sm font-semibold">{{ title }}</h2><ul class="mt-2 space-y-1 text-sm"><li v-for="r in summary[key]" :key="r.name" class="flex justify-between"><span>{{ r.name }}</span><span class="tabular text-muted-foreground">{{ mk(r.total) }}</span></li><li v-if="!summary[key].length" class="text-muted-foreground">No funded loans yet.</li></ul></div>
            </div>
            <p class="mt-2 text-xs text-muted-foreground">Spreading money across businesses can reduce the impact of one failure. It does not remove the risk of loss.</p>
            <h2 class="mb-3 mt-8 font-semibold">Investments</h2>
            <ul class="divide-y rounded-lg border bg-card"><li v-for="i in investments" :key="i.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm"><div><p class="font-medium">{{ i.business }}</p><p class="text-muted-foreground">{{ mk(i.principal) }} · {{ pct(i.rate_bp) }} · {{ i.term_months }} months · expected total {{ mk(i.total_payable) }}</p></div><div class="flex items-center gap-3"><Risk :category="i.risk" /><Status :value="i.status" /><Link :href="route('agreements.show', i.id)" class="text-primary hover:underline">Open</Link></div></li></ul>
        </template>
    </AppLayout>
</template>
