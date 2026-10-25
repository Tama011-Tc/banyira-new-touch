<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Pager from '@/components/pw/Pager.vue';
import Sim from '@/components/pw/Sim.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { label, mk } from '@/lib/format';
import { Head } from '@inertiajs/vue3';

defineProps<{ entries: { data: any[]; links: any[] } }>();
</script>

<template>
    <Head title="Ledger" />
    <AppLayout>
        <PageHeader title="Transaction ledger" description="Append-only double-entry record. Nothing is ever edited or deleted; mistakes are offset by reversing entries."><Sim /></PageHeader>
        <div class="overflow-x-auto rounded-lg border bg-card"><table class="w-full min-w-[44rem] text-sm"><caption class="sr-only">Ledger entries</caption>
            <thead class="bg-muted/60 text-left text-xs uppercase text-muted-foreground"><tr><th class="px-3 py-2">When</th><th class="px-3 py-2">Group</th><th class="px-3 py-2">Account</th><th class="px-3 py-2">Dr/Cr</th><th class="px-3 py-2 text-right">Amount</th><th class="px-3 py-2">Memo</th></tr></thead>
            <tbody class="divide-y"><tr v-for="e in entries.data" :key="e.id" :class="e.reversal ? 'bg-gold/5' : ''"><td class="px-3 py-2 whitespace-nowrap">{{ e.at }}</td><td class="px-3 py-2 font-mono text-xs">{{ e.group }}</td><td class="px-3 py-2">{{ label(e.account) }}</td><td class="px-3 py-2 capitalize">{{ e.direction }}</td><td class="tabular px-3 py-2 text-right">{{ mk(e.amount) }}</td><td class="px-3 py-2 text-muted-foreground">{{ e.memo }}<span v-if="e.simulated" class="ml-1 text-xs text-gold">· simulated</span></td></tr></tbody></table></div>
        <Pager :links="entries.links" />
    </AppLayout>
</template>
