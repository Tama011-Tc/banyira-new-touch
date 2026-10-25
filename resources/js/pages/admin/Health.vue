<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck } from 'lucide-vue-next';

defineProps<{ checks: { name: string; ok: boolean; detail: string }[]; failed_jobs: any[]; errors: any[] }>();
</script>

<template>
    <Head title="System health" />
    <AppLayout>
        <PageHeader title="System health" description="Application, queue and integration status." />
        <ul class="divide-y rounded-lg border bg-card"><li v-for="c in checks" :key="c.name" class="flex items-center justify-between gap-3 p-4 text-sm"><span class="flex items-center gap-2"><CircleCheck v-if="c.ok" class="size-4 text-primary" aria-hidden="true" /><CircleAlert v-else class="size-4 text-expense" aria-hidden="true" />{{ c.name }}</span><span class="text-muted-foreground">{{ c.detail }}</span></li></ul>
        <h2 class="mb-3 mt-8 font-semibold">Failed jobs</h2><ul class="divide-y rounded-lg border bg-card text-sm"><li v-for="j in failed_jobs" :key="j.id" class="p-3">{{ j.queue }} · {{ j.failed_at }}</li><li v-if="!failed_jobs.length" class="p-4 text-muted-foreground">None.</li></ul>
        <h2 class="mb-3 mt-8 font-semibold">Rejected webhooks</h2><ul class="divide-y rounded-lg border bg-card text-sm"><li v-for="e in errors" :key="e.event_id" class="p-3">{{ e.created_at }} · {{ e.event_id }}</li><li v-if="!errors.length" class="p-4 text-muted-foreground">None.</li></ul>
    </AppLayout>
</template>
