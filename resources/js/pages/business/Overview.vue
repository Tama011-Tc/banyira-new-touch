<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { mk } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ business: { display_name: string; verification_status: string } | null; applications: { id: number; status: string; amount: number; purpose: string; info_request: string | null }[]; loans: number }>();
</script>

<template>
    <Head title="Overview" />
    <AppLayout>
        <PageHeader title="Your business" description="Apply for financing, respond to requests and track what you owe." />
        <div v-if="!business" class="rounded-lg border bg-card p-6">
            <h2 class="font-semibold">Set up your business first</h2>
            <p class="mt-1 text-sm text-muted-foreground">Tell us about your business. Only a short description and general location will ever be shown to investors.</p>
            <Button class="mt-4" as-child><Link :href="route('business.edit')">Add business details</Link></Button>
        </div>
        <template v-else>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border bg-card p-5"><p class="text-sm text-muted-foreground">Business</p><p class="mt-1 text-lg font-semibold">{{ business.display_name }}</p><div class="mt-2"><Status :value="business.verification_status" /></div></div>
                <div class="rounded-lg border bg-card p-5"><p class="text-sm text-muted-foreground">Applications</p><p class="mt-1 text-2xl font-semibold">{{ applications.length }}</p></div>
                <div class="rounded-lg border bg-card p-5"><p class="text-sm text-muted-foreground">Active loans</p><p class="mt-1 text-2xl font-semibold">{{ loans }}</p><Link v-if="loans" :href="route('agreements.index')" class="text-sm text-primary hover:underline">View loans</Link></div>
            </div>
            <div v-for="a in applications.filter((x) => x.info_request)" :key="a.id" class="mt-6 rounded-lg border border-gold/50 bg-gold/10 p-4 text-sm"><p class="font-medium">Action needed on application #{{ a.id }}</p><p class="mt-1">{{ a.info_request }}</p><Link :href="route('applications.show', a.id)" class="mt-2 inline-block font-medium text-primary hover:underline">Respond</Link></div>
            <h2 class="mb-3 mt-8 font-semibold">Recent applications</h2>
            <ul class="divide-y rounded-lg border bg-card">
                <li v-for="a in applications" :key="a.id" class="flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0"><p class="truncate text-sm font-medium">{{ mk(a.amount) }} · {{ a.purpose }}</p></div>
                    <div class="flex items-center gap-3"><Status :value="a.status" /><Link :href="route('applications.show', a.id)" class="text-sm text-primary hover:underline">Open</Link></div>
                </li>
                <li v-if="!applications.length" class="p-6 text-center text-sm text-muted-foreground">No applications yet. <Link :href="route('applications.create')" class="text-primary hover:underline">Start one</Link>.</li>
            </ul>
        </template>
    </AppLayout>
</template>
