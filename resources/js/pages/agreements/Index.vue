<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Sim from '@/components/pw/Sim.vue';
import Status from '@/components/pw/Status.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { mk, pct } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';
import { Wallet } from 'lucide-vue-next';

defineProps<{ agreements: { id: number; business: string; investor: string | null; principal: number; status: string; rate_bp: number; term_months: number; simulated: boolean }[] }>();
</script>

<template>
    <Head title="Loans" />
    <AppLayout>
        <PageHeader title="Loans and agreements" description="Agreed terms, funding status and repayment schedules." />
        <ul v-if="agreements.length" class="divide-y rounded-lg border bg-card"><li v-for="a in agreements" :key="a.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm"><div><p class="font-medium">AGR-{{ a.id }} · {{ a.business }}<template v-if="a.investor"> ← {{ a.investor }}</template></p><p class="text-muted-foreground">{{ mk(a.principal) }} · {{ pct(a.rate_bp) }} · {{ a.term_months }} months</p></div><div class="flex items-center gap-3"><Sim v-if="a.simulated" /><Status :value="a.status" /><Link :href="route('agreements.show', a.id)" class="text-primary hover:underline">Open</Link></div></li></ul>
        <EmptyState v-else :icon="Wallet" title="No loans yet" description="A loan appears here when an investor and a business accept the same offer." />
    </AppLayout>
</template>
