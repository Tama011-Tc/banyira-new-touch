<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { date, mk } from '@/lib/format';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';

defineProps<{ has_business: boolean; applications: { id: number; status: string; amount: number; purpose: string; term_months: number; info_request: string | null; submitted_at: string | null }[] }>();
const role = (usePage().props.auth.user as { role: string }).role;
</script>

<template>
    <Head title="Applications" />
    <AppLayout>
        <PageHeader title="Financing applications" description="Drafts are saved automatically as you work. Nothing is shown to investors until credit staff approve and publish it.">
            <Button v-if="has_business && role === 'business_owner'" as-child><Link :href="route('applications.create')">New application</Link></Button>
        </PageHeader>
        <EmptyState v-if="!has_business" :icon="FileText" title="Add your business first" description="We need your business details before you can apply.">
            <Button as-child><Link :href="route('business.edit')">Add business details</Link></Button>
        </EmptyState>
        <ul v-else-if="applications.length" class="divide-y rounded-lg border bg-card">
            <li v-for="a in applications" :key="a.id" class="p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0"><p class="font-medium">{{ mk(a.amount) }} over {{ a.term_months }} months</p><p class="line-clamp-1 text-sm text-muted-foreground">{{ a.purpose }}</p></div>
                    <div class="flex items-center gap-3"><span class="text-xs text-muted-foreground">{{ a.submitted_at ? 'Submitted ' + date(a.submitted_at) : 'Draft' }}</span><Status :value="a.status" /><Button size="sm" variant="outline" as-child><Link :href="route('applications.show', a.id)">Open</Link></Button></div>
                </div>
                <p v-if="a.info_request" class="mt-3 rounded-md bg-gold/10 p-3 text-sm"><strong>We need more information:</strong> {{ a.info_request }}</p>
            </li>
        </ul>
        <EmptyState v-else :icon="FileText" title="No applications yet" description="Start an application and add your documents."><Button as-child><Link :href="route('applications.create')">Start an application</Link></Button></EmptyState>
    </AppLayout>
</template>
