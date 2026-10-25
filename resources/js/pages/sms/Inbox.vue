<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Sim from '@/components/pw/Sim.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Smartphone } from 'lucide-vue-next';

defineProps<{ phone: string | null; messages: { id: number; body: string; kind: string; at: string; simulated: boolean }[] }>();
</script>

<template>
    <Head title="Phone (simulated)" />
    <AppLayout>
        <PageHeader title="Your phone (simulated)" :description="phone ? `Messages that would be sent to ${phone}. No real SMS is sent in this build.` : 'Add a mobile number to your profile to receive SMS.'"><Sim /></PageHeader>
        <ul v-if="messages.length" class="max-w-md space-y-3"><li v-for="m in messages" :key="m.id" class="rounded-2xl rounded-tl-sm bg-muted px-4 py-3 text-sm"><p>{{ m.body }}</p><p class="mt-1 text-xs text-muted-foreground">{{ m.at }}</p></li></ul>
        <EmptyState v-else :icon="Smartphone" title="No messages yet" description="Status updates, offers and one-time codes appear here." />
    </AppLayout>
</template>
