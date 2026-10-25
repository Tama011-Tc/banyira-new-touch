<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps<{ can_review: boolean; held: any[]; reports: any[] }>();
</script>

<template>
    <Head title="Moderation" />
    <AppLayout>
        <PageHeader title="Moderation" description="Held messages and user reports. Every action is logged." />
        <h2 class="mb-3 font-semibold">Held messages</h2>
        <ul class="mb-8 divide-y rounded-lg border bg-card"><li v-for="m in held" :key="m.id" class="p-4 text-sm"><div class="flex flex-wrap items-center justify-between gap-2"><p class="font-medium">{{ m.sender }} · {{ m.listing }}</p><div class="flex items-center gap-2"><Status :value="m.status" /><span class="text-xs text-muted-foreground">{{ m.flags.join(', ') }}</span></div></div><p class="mt-2 rounded-md bg-muted p-3">{{ m.body }}</p><div v-if="can_review" class="mt-3 flex gap-2"><Button size="sm" @click="router.post(route('moderation.resolve', m.id), { outcome: 'release' })">Release (false positive)</Button><Button size="sm" variant="outline" @click="router.post(route('moderation.resolve', m.id), { outcome: 'uphold' })">Uphold block</Button></div></li><li v-if="!held.length" class="p-5 text-center text-muted-foreground">Nothing held.</li></ul>
        <h2 class="mb-3 font-semibold">Open reports</h2>
        <ul class="divide-y rounded-lg border bg-card"><li v-for="r in reports" :key="r.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm"><div><p class="font-medium capitalize">{{ r.reason.replace('_', ' ') }} <span class="font-normal text-muted-foreground">· by {{ r.reporter }} · {{ r.at }}</span></p><p v-if="r.detail" class="text-muted-foreground">{{ r.detail }}</p></div><div v-if="can_review" class="flex gap-2"><Button size="sm" @click="router.post(route('moderation.report', r.id), { status: 'actioned' })">Actioned</Button><Button size="sm" variant="outline" @click="router.post(route('moderation.report', r.id), { status: 'dismissed' })">Dismiss</Button></div></li><li v-if="!reports.length" class="p-5 text-center text-muted-foreground">No open reports.</li></ul>
    </AppLayout>
</template>
