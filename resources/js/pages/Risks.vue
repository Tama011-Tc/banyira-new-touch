<script setup lang="ts">
import Logo from '@/components/pw/Logo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const brand = computed(() => (usePage().props.brand as { name: string }).name);
const risks = [
    ['Default', 'A business may fail to repay some or all of what it owes. You could lose your capital.'],
    ['Late payment', 'Repayments can arrive late, in part, or not at all, and recovering money can take a long time or fail.'],
    ['No guaranteed return', 'Interest rates shown are the contractual rate if the loan performs. They are not promised income.'],
    ['Assessment is an estimate', 'The credit category reflects information available at the time. It reduces neither all risk nor the possibility of default.'],
    ['Illiquidity', 'A loan cannot normally be sold or withdrawn early. Your money is committed for the full term.'],
    ['Concentration', 'Lending to few businesses, one sector or one district increases the impact of a single failure. Diversification helps but does not remove loss.'],
    ['Platform and legal risk', 'Rules for this type of activity are evolving. Regulatory or operational changes could affect your loans.'],
];
</script>

<template>
    <Head title="How it works and the risks" />
    <div class="min-h-svh bg-background">
        <header class="border-b bg-card"><div class="mx-auto flex max-w-3xl items-center gap-2.5 px-4 py-4"><Link :href="route('home')" class="flex items-center gap-2.5"><Logo :size="28" /><span class="font-semibold">{{ brand }}</span></Link></div></header>
        <main class="mx-auto max-w-3xl px-4 py-10">
            <h1 class="text-3xl font-semibold tracking-tight">How it works and the risks</h1>
            <p class="mt-3 text-muted-foreground">Please read this before you lend. {{ brand }} never promises returns and never presents projected interest as guaranteed income.</p>
            <h2 class="mt-10 text-xl font-semibold">Risks of lending to businesses</h2>
            <dl class="mt-4 divide-y rounded-lg border bg-card">
                <div v-for="r in risks" :key="r[0]" class="p-4"><dt class="font-medium">{{ r[0] }}</dt><dd class="mt-1 text-sm text-muted-foreground">{{ r[1] }}</dd></div>
            </dl>
            <h2 class="mt-10 text-xl font-semibold">Fees</h2>
            <p class="mt-2 text-sm text-muted-foreground">Every fee is shown on each opportunity and on each offer before anyone accepts. A borrower origination fee is deducted from the amount they receive; an investor service fee is added to the amount an investor pays. Payment-provider charges are borne by the platform. Fees on an accepted agreement never change afterwards.</p>
            <h2 class="mt-10 text-xl font-semibold">Messaging rules</h2>
            <p class="mt-2 text-sm text-muted-foreground">Negotiation happens on the platform. Messages that contain phone numbers, emails, links, social handles or payment instructions are blocked, and requests to move outside the platform are reviewed by staff. If a message is blocked by mistake you can appeal it. You can report any conversation. No filter catches everything, so staff review reports and may suspend accounts.</p>
            <h2 class="mt-10 text-xl font-semibold">Simulation</h2>
            <p class="mt-2 text-sm text-muted-foreground">This build uses a simulated payment provider. Nothing here is a real loan, investment or payment.</p>
        </main>
    </div>
</template>
