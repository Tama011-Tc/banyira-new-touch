<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import InviteLink from '@/components/pw/InviteLink.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { date } from '@/lib/format';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

defineProps<{ leads: { id: number; owner: string; email: string; business: string | null; sector: string | null; district: string | null; applications: number; status: string; signed_in: boolean; next_follow_up: string | null }[]; follow_ups: { id: number; lead_id: number; lead: string; kind: string; body: string; due_on: string; overdue: boolean }[] }>();
const invite = useForm({ owner_name: '', owner_email: '' });
</script>

<template>
    <Head title="My leads" />
    <AppLayout>
        <PageHeader title="My leads" description="Businesses you brought in. You can prepare and upload an application for them, and they can sign in to follow progress. You cannot assess, approve or promise funding.">
            <Button as-child><Link :href="route('sales.create')">Register a business</Link></Button>
        </PageHeader>
        <InviteLink />

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="lg:col-span-2">
                <h2 class="mb-3 font-semibold">Leads</h2>
                <div class="overflow-x-auto rounded-lg border bg-card">
                    <table class="w-full min-w-[40rem] text-sm"><caption class="sr-only">Your leads</caption>
                        <thead class="bg-muted/60 text-left text-xs uppercase tracking-wide text-muted-foreground"><tr><th scope="col" class="px-3 py-2">Owner / business</th><th scope="col" class="px-3 py-2">Progress</th><th scope="col" class="px-3 py-2">Owner access</th><th scope="col" class="px-3 py-2">Next follow-up</th><th /></tr></thead>
                        <tbody class="divide-y">
                            <tr v-for="l in leads" :key="l.id">
                                <td class="px-3 py-3"><p class="font-medium">{{ l.business ?? l.owner }}</p><p class="text-xs text-muted-foreground">{{ l.business ? l.owner + ' · ' : '' }}{{ l.email }}</p></td>
                                <td class="px-3 py-3"><Status :value="l.status.replace(' ', '_')" /></td>
                                <td class="px-3 py-3 text-xs">{{ l.signed_in ? 'Signed in' : 'Not signed in yet' }}</td>
                                <td class="px-3 py-3 text-xs">{{ l.next_follow_up ? date(l.next_follow_up) : '-' }}</td>
                                <td class="px-3 py-3 text-right"><Button size="sm" variant="outline" as-child><Link :href="route('sales.lead', l.id)">Open</Link></Button></td>
                            </tr>
                            <tr v-if="!leads.length"><td colspan="5" class="px-3 py-8 text-center text-muted-foreground">No leads yet. Register a business or send an invitation.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Invite an owner</h2>
                    <p class="text-xs text-muted-foreground">They complete their own application. You can follow it and help.</p>
                    <form class="mt-3 space-y-3" @submit.prevent="invite.post(route('sales.invite'), { onSuccess: () => invite.reset() })">
                        <FormField label="Owner's name" for="in" :error="invite.errors.owner_name"><Input id="in" v-model="invite.owner_name" required /></FormField>
                        <FormField label="Owner's email" for="ie" :error="invite.errors.owner_email"><Input id="ie" v-model="invite.owner_email" type="email" required /></FormField>
                        <Button type="submit" class="w-full" :disabled="invite.processing">Create invitation</Button>
                    </form>
                </section>
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Follow-ups due</h2>
                    <ul class="mt-3 space-y-3 text-sm">
                        <li v-for="f in follow_ups" :key="f.id" class="flex items-start justify-between gap-2"><div><Link :href="route('sales.lead', f.lead_id)" class="font-medium hover:underline">{{ f.lead }}</Link><p class="text-xs text-muted-foreground">{{ f.body }}</p><p class="text-xs" :class="f.overdue ? 'font-medium text-expense' : 'text-muted-foreground'">{{ f.overdue ? 'Overdue · ' : '' }}{{ date(f.due_on) }}</p></div><button class="text-xs text-primary hover:underline" @click="router.post(route('sales.activity.done', f.id), {}, { preserveScroll: true })">Done</button></li>
                        <li v-if="!follow_ups.length" class="text-muted-foreground">Nothing due.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
