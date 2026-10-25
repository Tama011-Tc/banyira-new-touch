<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Sim from '@/components/pw/Sim.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ messages: { id: number; to: string; user: string; kind: string; body: string; provider: string; simulated: boolean; status: string; at: string }[] }>();
</script>

<template>
    <Head title="SMS log" />
    <AppLayout>
        <PageHeader title="SMS log" description="Everything the platform sent by SMS. One-time codes are masked; staff never see them."><Sim /></PageHeader>
        <div class="overflow-x-auto rounded-lg border bg-card"><table class="w-full min-w-[44rem] text-sm"><caption class="sr-only">SMS messages</caption><thead class="bg-muted/60 text-left text-xs uppercase text-muted-foreground"><tr><th class="px-3 py-2">When</th><th class="px-3 py-2">To</th><th class="px-3 py-2">Kind</th><th class="px-3 py-2">Message</th><th class="px-3 py-2">Provider</th></tr></thead>
            <tbody class="divide-y"><tr v-for="m in messages" :key="m.id"><td class="whitespace-nowrap px-3 py-2">{{ m.at }}</td><td class="px-3 py-2">{{ m.user }}<br /><span class="text-xs text-muted-foreground">{{ m.to }}</span></td><td class="px-3 py-2 text-xs">{{ m.kind }}</td><td class="px-3 py-2">{{ m.body }}</td><td class="px-3 py-2 text-xs">{{ m.provider }}{{ m.simulated ? ' (simulated)' : '' }}</td></tr><tr v-if="!messages.length"><td colspan="5" class="px-3 py-8 text-center text-muted-foreground">No messages yet.</td></tr></tbody></table></div>
    </AppLayout>
</template>
