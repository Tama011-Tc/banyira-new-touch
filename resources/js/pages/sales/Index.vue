<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { mk } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{ businesses: { id: number; display_name: string; sector: string; district: string; claimed: boolean; applications: number }[]; applications: { id: number; business: string; status: string; amount: number }[] }>();
</script>

<template>
    <Head title="Sales workspace" />
    <AppLayout>
        <PageHeader title="Sales workspace" description="Help businesses apply. You can register a business with the owner's consent and guide the application, but you cannot assess, approve or promise funding.">
            <Button as-child><Link :href="route('sales.create')">Register a business</Link></Button>
        </PageHeader>
        <div class="grid gap-6 lg:grid-cols-2">
            <section>
                <h2 class="mb-3 font-semibold">Businesses you registered</h2>
                <ul class="divide-y rounded-lg border bg-card"><li v-for="b in businesses" :key="b.id" class="flex items-center justify-between gap-3 p-4 text-sm"><div><p class="font-medium">{{ b.display_name }}</p><p class="text-muted-foreground">{{ b.sector }} · {{ b.district }} · {{ b.applications }} application(s)</p></div><Button size="sm" variant="outline" as-child><Link :href="route('applications.create', { business: b.id })">New application</Link></Button></li><li v-if="!businesses.length" class="p-6 text-center text-sm text-muted-foreground">None yet.</li></ul>
            </section>
            <section>
                <h2 class="mb-3 font-semibold">Application progress</h2>
                <ul class="divide-y rounded-lg border bg-card"><li v-for="a in applications" :key="a.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm"><div><p class="font-medium">{{ a.business }}</p><p class="text-muted-foreground">{{ mk(a.amount) }}</p></div><div class="flex items-center gap-2"><Status :value="a.status" /><Link :href="route('applications.show', a.id)" class="text-primary hover:underline">Open</Link><Button v-if="a.status === 'submitted'" size="sm" variant="outline" @click="router.post(route('sales.refer', a.id))">Refer to credit</Button></div></li><li v-if="!applications.length" class="p-6 text-center text-sm text-muted-foreground">None yet.</li></ul>
            </section>
        </div>
    </AppLayout>
</template>
