<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Pager from '@/components/pw/Pager.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { date, mk } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{ applications: { data: any[]; links: any[] }; filters: { status?: string; mine?: string }; new_count: number }>();
const states = ['', 'submitted', 'screening', 'assessment', 'info_requested', 'approved', 'rejected'];
</script>

<template>
    <Head title="Credit review" />
    <AppLayout>
        <PageHeader title="Credit review queue" description="Assess evidence, record reasons, and keep recommendation and approval separate." />
        <p v-if="new_count" class="mb-4 rounded-lg border border-primary/40 bg-accent p-3 text-sm"><strong>{{ new_count }}</strong> new application{{ new_count === 1 ? '' : 's' }} waiting to be picked up. Open one and start screening to take the case.</p>
        <div class="mb-4 flex flex-wrap items-center gap-1.5" role="group" aria-label="Filter by status">
            <button class="mr-2 rounded-full border px-3 py-1 text-xs" :class="filters.mine ? 'border-primary bg-accent font-medium' : 'hover:bg-muted'" @click="router.get(route('credit.index'), filters.mine ? {} : { mine: 1 }, { preserveState: true })">My cases</button>
            <button v-for="s in states" :key="s" class="rounded-full border px-3 py-1 text-xs" :class="(filters.status ?? '') === s ? 'border-primary bg-accent font-medium' : 'hover:bg-muted'" @click="router.get(route('credit.index'), s ? { status: s } : {}, { preserveState: true })">{{ s ? s.replace('_', ' ') : 'Open & decided' }}</button>
        </div>
        <div class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full min-w-[40rem] text-sm">
                <caption class="sr-only">Applications</caption>
                <thead class="bg-muted/60 text-left text-xs uppercase tracking-wide text-muted-foreground"><tr><th scope="col" class="px-4 py-2">Business</th><th scope="col" class="px-4 py-2">Sector</th><th scope="col" class="px-4 py-2 text-right">Amount</th><th scope="col" class="px-4 py-2">Submitted</th><th scope="col" class="px-4 py-2">Status</th><th scope="col" class="px-4 py-2">Case owner</th><th class="px-4 py-2"><span class="sr-only">Open</span></th></tr></thead>
                <tbody class="divide-y">
                    <tr v-for="a in applications.data" :key="a.id">
                        <td class="px-4 py-3 font-medium">{{ a.business }} <span v-if="a.referred" class="ml-1 text-xs text-muted-foreground">(referred by sales)</span></td><td class="px-4 py-3">{{ a.sector }}</td><td class="tabular px-4 py-3 text-right">{{ mk(a.amount) }}</td><td class="px-4 py-3">{{ date(a.submitted_at) }}</td><td class="px-4 py-3"><Status :value="a.status" /></td><td class="px-4 py-3 text-xs">{{ a.mine ? 'You' : (a.assigned ?? 'Unassigned') }}</td>
                        <td class="px-4 py-3 text-right"><Button size="sm" variant="outline" as-child><Link :href="route('credit.show', a.id)">Review</Link></Button></td>
                    </tr>
                    <tr v-if="!applications.data.length"><td colspan="7" class="px-4 py-8 text-center text-muted-foreground">Nothing in this view.</td></tr>
                </tbody>
            </table>
        </div>
        <Pager :links="applications.links" />
    </AppLayout>
</template>
