<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import InviteLink from '@/components/pw/InviteLink.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { date, label, mk } from '@/lib/format';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{ lead: { id: number; name: string; email: string; status: string; signed_in: boolean }; business: { id: number; display_name: string; sector: string; district: string; registered_by_me: boolean } | null; applications: { id: number; status: string; amount: number; term_months: number; submitted_at: string | null; assisted: boolean }[]; activities: { id: number; kind: string; body: string; due_on: string | null; done: boolean; at: string }[] }>();
const act = useForm({ kind: 'note', body: '', due_on: '' });
</script>

<template>
    <Head :title="lead.name" />
    <AppLayout>
        <PageHeader :title="business?.display_name ?? lead.name" :description="`${lead.name} · ${lead.email}`"><Status :value="lead.status.replace(' ', '_')" /></PageHeader>
        <InviteLink />

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Owner access</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ lead.signed_in ? 'The owner has signed in and can follow their application.' : 'The owner has not signed in yet. Send them the secure link to choose their own password.' }}</p>
                    <Button v-if="!lead.signed_in" class="mt-3" variant="outline" size="sm" @click="router.post(route('sales.reinvite', lead.id), {}, { preserveScroll: true })">Create a new invitation link</Button>
                </section>

                <section class="rounded-lg border bg-card p-5">
                    <div class="flex items-center justify-between"><h2 class="font-semibold">Business and applications</h2><Button v-if="business" size="sm" as-child><Link :href="route('applications.create', { business: business.id })">New application</Link></Button></div>
                    <div v-if="!business" class="mt-3 text-sm"><p class="text-muted-foreground">No business profile yet. With the owner's consent you can add it for them.</p><Button class="mt-3" as-child><Link :href="route('sales.create', { lead: lead.id })">Add business details</Link></Button></div>
                    <template v-else>
                        <p class="mt-1 text-sm text-muted-foreground">{{ business.sector }} · {{ business.district }}</p>
                        <ul class="mt-4 divide-y rounded-md border">
                            <li v-for="a in applications" :key="a.id" class="flex flex-wrap items-center justify-between gap-3 p-3 text-sm"><div><p class="font-medium">{{ mk(a.amount) }} over {{ a.term_months }} months</p><p class="text-xs text-muted-foreground">{{ a.submitted_at ? 'Submitted ' + date(a.submitted_at) : 'Draft' }}{{ a.assisted ? ' · prepared by you' : '' }}</p></div><div class="flex items-center gap-2"><Status :value="a.status" /><Button size="sm" variant="outline" as-child><Link :href="route('applications.show', a.id)">Open</Link></Button><Button v-if="a.status === 'submitted'" size="sm" variant="ghost" @click="router.post(route('sales.refer', a.id))">Refer to credit</Button></div></li>
                            <li v-if="!applications.length" class="p-4 text-center text-muted-foreground">No applications yet.</li>
                        </ul>
                    </template>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Log an interaction</h2>
                    <form class="mt-3 space-y-3" @submit.prevent="act.post(route('sales.activity', lead.id), { preserveScroll: true, onSuccess: () => act.reset('body', 'due_on') })">
                        <FormField label="Type" for="ak" :error="act.errors.kind"><SelectField id="ak" v-model="act.kind"><option value="note">Note</option><option value="call">Call</option><option value="meeting">Meeting</option><option value="onboarding_session">Onboarding session</option><option value="follow_up">Follow-up task</option></SelectField></FormField>
                        <FormField label="Details" for="ab" :error="act.errors.body"><textarea id="ab" v-model="act.body" rows="3" required class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
                        <FormField label="Due / scheduled for" for="ad" :error="act.errors.due_on" hint="Optional"><Input id="ad" v-model="act.due_on" type="date" /></FormField>
                        <Button type="submit" class="w-full" :disabled="act.processing">Save</Button>
                    </form>
                </section>
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">History</h2>
                    <ul class="mt-3 space-y-3 text-sm">
                        <li v-for="a in activities" :key="a.id" :class="a.done ? 'opacity-60' : ''"><p class="font-medium">{{ label(a.kind) }} <span class="text-xs font-normal text-muted-foreground">· {{ a.at }}</span></p><p>{{ a.body }}</p><p v-if="a.due_on" class="text-xs text-muted-foreground">{{ a.done ? 'Done' : 'Due ' + date(a.due_on) }}</p></li>
                        <li v-if="!activities.length" class="text-muted-foreground">Nothing recorded yet.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
