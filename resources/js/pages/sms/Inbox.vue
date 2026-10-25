<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Sim from '@/components/pw/Sim.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, useForm } from '@inertiajs/vue3';
import { Smartphone } from 'lucide-vue-next';

const reply = useForm({ text: '' });
defineProps<{ phone: string | null; messages: { id: number; body: string; kind: string; at: string; simulated: boolean }[] }>();
</script>

<template>
    <Head title="Phone (simulated)" />
    <AppLayout>
        <PageHeader title="Your phone (simulated)" :description="phone ? `Messages that would be sent to ${phone}. No real SMS is sent in this build.` : 'Add a mobile number to your profile to receive SMS.'"><Sim /></PageHeader>
        <form v-if="phone" class="mb-6 flex max-w-md gap-2" @submit.prevent="reply.post(route('sms.reply'), { preserveScroll: true, onSuccess: () => reply.reset() })"><label class="sr-only" for="txt">Reply by SMS</label><Input id="txt" v-model="reply.text" placeholder="Reply by SMS, e.g. Y 123456" autocomplete="off" /><Button type="submit" :disabled="reply.processing || !reply.text">Send</Button></form>
        <p v-if="phone" class="-mt-3 mb-6 max-w-md text-xs text-muted-foreground">To answer an offer you were texted about, reply Y or N and the 6-digit code from the message. Larger offers must be accepted on the website.</p>
        <ul v-if="messages.length" class="max-w-md space-y-3"><li v-for="m in messages" :key="m.id" class="rounded-2xl rounded-tl-sm bg-muted px-4 py-3 text-sm"><p>{{ m.body }}</p><p class="mt-1 text-xs text-muted-foreground">{{ m.at }}</p></li></ul>
        <EmptyState v-else :icon="Smartphone" title="No messages yet" description="Status updates, offers and one-time codes appear here." />
    </AppLayout>
</template>
