<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Pager from '@/components/pw/Pager.vue';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ logs: { data: any[]; links: any[] }; chain_ok: boolean; filters: { action?: string } }>();
const q = ref(props.filters.action ?? '');
const filter = () => router.get(route('audit.index'), { action: q.value }, { preserveState: true });
</script>

<template>
    <Head title="Audit log" />
    <AppLayout>
        <PageHeader title="Audit log" description="Append-only and hash-chained. Rows cannot be edited or removed through the application or by the app's database user." />
        <p class="mb-4 inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm" :class="chain_ok ? 'bg-accent text-accent-foreground' : 'bg-expense/10 text-expense'"><CircleCheck v-if="chain_ok" class="size-4" aria-hidden="true" /><CircleAlert v-else class="size-4" aria-hidden="true" />{{ chain_ok ? 'Hash chain verified: no entry missing or altered.' : 'Hash chain broken: investigate immediately.' }}</p>
        <form class="mb-4 max-w-sm" @submit.prevent="filter"><Input v-model="q" placeholder="Filter by action, e.g. application.status" aria-label="Filter by action" /></form>
        <div class="overflow-x-auto rounded-lg border bg-card"><table class="w-full min-w-[48rem] text-sm"><caption class="sr-only">Audit entries</caption><thead class="bg-muted/60 text-left text-xs uppercase text-muted-foreground"><tr><th class="px-3 py-2">When</th><th class="px-3 py-2">Who</th><th class="px-3 py-2">Action</th><th class="px-3 py-2">Subject</th><th class="px-3 py-2">Detail</th></tr></thead>
            <tbody class="divide-y"><tr v-for="l in logs.data" :key="l.id"><td class="whitespace-nowrap px-3 py-2">{{ l.at }}</td><td class="px-3 py-2">{{ l.actor }} <span class="text-xs text-muted-foreground">{{ l.role }}</span></td><td class="px-3 py-2 font-mono text-xs">{{ l.action }}</td><td class="px-3 py-2">{{ l.subject }}</td><td class="max-w-xs truncate px-3 py-2 text-xs text-muted-foreground" :title="JSON.stringify(l.meta)">{{ l.meta ? JSON.stringify(l.meta) : '' }}</td></tr></tbody></table></div>
        <Pager :links="logs.links" />
    </AppLayout>
</template>
