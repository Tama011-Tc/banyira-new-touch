<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Risk from '@/components/pw/Risk.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { date, mk } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{ can_manage: boolean; approved: { id: number; business: string; amount: number; sector: string }[]; listings: { id: number; title: string; business: string; status: string; target: number; committed: number; risk: string; deadline: string }[] }>();
</script>

<template>
    <Head title="Listings" />
    <AppLayout>
        <PageHeader title="Listings" description="Approved applications become public only after the listing is prepared, independently reviewed and published." />
        <h2 class="mb-3 font-semibold">Approved, awaiting a listing</h2>
        <ul class="mb-8 divide-y rounded-lg border bg-card"><li v-for="a in approved" :key="a.id" class="flex items-center justify-between gap-3 p-4 text-sm"><span><strong>{{ a.business }}</strong> · {{ a.sector }} · {{ mk(a.amount) }}</span><Button v-if="can_manage" size="sm" as-child><Link :href="route('listings.prepare', a.id)">Prepare listing</Link></Button></li><li v-if="!approved.length" class="p-5 text-center text-sm text-muted-foreground">Nothing waiting.</li></ul>
        <h2 class="mb-3 font-semibold">All listings</h2>
        <ul class="divide-y rounded-lg border bg-card">
            <li v-for="l in listings" :key="l.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                <div class="min-w-0"><p class="font-medium">{{ l.title }}</p><p class="text-muted-foreground">{{ l.business }} · {{ mk(l.committed) }} of {{ mk(l.target) }} · closes {{ date(l.deadline) }}</p></div>
                <div class="flex items-center gap-3"><Risk :category="l.risk" /><Status :value="l.status" /><Button v-if="can_manage && l.status === 'draft'" size="sm" variant="outline" @click="router.post(route('listings.review', l.id))">Review (second person)</Button><Button v-if="can_manage && l.status === 'reviewed'" size="sm" @click="router.post(route('listings.publish', l.id))">Publish</Button></div>
            </li>
            <li v-if="!listings.length" class="p-5 text-center text-sm text-muted-foreground">No listings yet.</li>
        </ul>
    </AppLayout>
</template>
