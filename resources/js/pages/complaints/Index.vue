<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps<{ staff: boolean; view_all: boolean; complaints: { id: number; category: string; subject: string; body: string; status: string; resolution: string | null; who: string; at: string }[] }>();
const form = useForm({ category: 'service', subject: '', body: '' });
</script>

<template>
    <Head title="Help and complaints" />
    <AppLayout>
        <PageHeader title="Help and complaints" description="Tell us if something went wrong. Every complaint gets a reference and a recorded outcome." />
        <div class="grid gap-6 lg:grid-cols-5">
            <section class="lg:col-span-3"><h2 class="mb-3 font-semibold">{{ view_all ? 'All complaints' : 'Your complaints' }}</h2>
                <ul class="divide-y rounded-lg border bg-card"><li v-for="c in complaints" :key="c.id" class="p-4 text-sm"><div class="flex items-center justify-between gap-2"><p class="font-medium">C-{{ String(c.id).padStart(5, '0') }} · {{ c.subject }}</p><Status :value="c.status" /></div><p class="text-xs text-muted-foreground">{{ c.category }} · {{ c.who }} · {{ c.at }}</p><p class="mt-2">{{ c.body }}</p><p v-if="c.resolution" class="mt-2 rounded-md bg-muted p-2"><strong>Outcome:</strong> {{ c.resolution }}</p>
                    <div v-if="staff && !['resolved', 'closed'].includes(c.status)" class="mt-3 flex gap-2"><Button size="sm" variant="outline" @click="router.put(route('complaints.update', c.id), { status: 'investigating' })">Investigating</Button><Button size="sm" @click="router.put(route('complaints.update', c.id), { status: 'resolved', resolution: prompt('Outcome for the complainant?') ?? '' })">Resolve</Button></div></li><li v-if="!complaints.length" class="p-6 text-center text-muted-foreground">No complaints.</li></ul></section>
            <section class="lg:col-span-2"><h2 class="mb-3 font-semibold">Make a complaint</h2>
                <form class="space-y-4 rounded-lg border bg-card p-5" @submit.prevent="form.post(route('complaints.store'), { onSuccess: () => form.reset('subject', 'body') })">
                    <FormField label="About" for="cc" :error="form.errors.category"><SelectField id="cc" v-model="form.category"><option value="service">Service</option><option value="payment">A payment</option><option value="fraud">Suspected fraud</option><option value="privacy">My data</option><option value="other">Something else</option></SelectField></FormField>
                    <FormField label="Subject" for="cs" :error="form.errors.subject"><Input id="cs" v-model="form.subject" required /></FormField>
                    <FormField label="What happened?" for="cb" :error="form.errors.body"><textarea id="cb" v-model="form.body" rows="5" required class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
                    <Button type="submit" :disabled="form.processing">Send complaint</Button></form></section>
        </div>
    </AppLayout>
</template>
