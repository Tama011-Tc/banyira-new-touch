<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Pager from '@/components/pw/Pager.vue';
import SelectField from '@/components/pw/SelectField.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ users: { data: any[]; links: any[] }; roles: { value: string; label: string; staff: boolean }[]; filters: { search?: string; role?: string } }>();
const staffRoles = props.roles.filter((r) => r.staff);
const form = useForm({ name: '', email: '', password: '', role: 'finance' });
const temp = computed(() => usePage().props.temp_credential as { email: string; password: string } | null);
const reset = (id: number, name: string) => confirm(`Issue a new temporary password for ${name}? Their current password and sessions stop working.`) && router.post(route('admin.reset', id));
const search = ref(props.filters.search ?? '');
const find = () => router.get(route('admin.users'), { search: search.value }, { preserveState: true });
const suspend = (id: number, on: boolean) => router.post(route('admin.suspend', id), { suspend: on, reason: on ? (prompt('Reason for suspension?') ?? '') : '' });
</script>

<template>
    <Head title="Users" />
    <AppLayout>
        <PageHeader title="Users and access" description="Manage accounts and roles. Role changes are logged. You cannot alter credit decisions, payments, loans or the audit trail." />
        <section v-if="temp" class="mb-6 max-w-2xl rounded-lg border-2 border-gold/60 bg-gold/10 p-4 text-sm" role="alert">
            <p class="font-semibold">Temporary password for {{ temp.email }}</p>
            <p class="mt-1 select-all rounded bg-card px-3 py-2 font-mono text-base">{{ temp.password }}</p>
            <p class="mt-2 text-muted-foreground">Shown once. Share it through a secure channel; it is not stored or emailed. They must choose their own password at first sign-in.</p>
        </section>
        <form class="mb-4 flex max-w-md gap-2" @submit.prevent="find"><Input v-model="search" placeholder="Search name or email" aria-label="Search users" /><Button type="submit" variant="outline">Search</Button></form>
        <div class="overflow-x-auto rounded-lg border bg-card"><table class="w-full min-w-[40rem] text-sm"><thead class="bg-muted/60 text-left text-xs uppercase text-muted-foreground"><tr><th class="px-3 py-2">Name</th><th class="px-3 py-2">Role</th><th class="px-3 py-2">Status</th><th /></tr></thead>
            <tbody class="divide-y"><tr v-for="u in users.data" :key="u.id"><td class="px-3 py-2"><p class="font-medium">{{ u.name }}</p><p class="text-xs text-muted-foreground">{{ u.email }}</p></td>
                <td class="px-3 py-2"><SelectField v-if="staffRoles.some((r) => r.value === u.role)" :model-value="u.role" class="h-8 w-48" :aria-label="`Role for ${u.name}`" @update:model-value="(v) => router.put(route('admin.role', u.id), { role: v })"><option v-for="r in staffRoles" :key="r.value" :value="r.value">{{ r.label }}</option></SelectField><span v-else>{{ u.role_label }}</span></td>
                <td class="px-3 py-2"><Status :value="u.status" /></td><td class="px-3 py-2 text-right"><Button size="sm" variant="ghost" @click="reset(u.id, u.name)">Reset password</Button><Button size="sm" variant="ghost" @click="suspend(u.id, u.status === 'active')">{{ u.status === 'active' ? 'Suspend' : 'Reinstate' }}</Button></td></tr></tbody></table></div>
        <Pager :links="users.links" />
        <section class="mt-8 max-w-xl rounded-lg border bg-card p-5"><h2 class="font-semibold">Create a staff account</h2><p class="text-xs text-muted-foreground">Staff cannot sign themselves up. Public sign-up only creates investors and business owners. New staff must change their password at first sign-in.</p>
            <form class="mt-3 grid gap-3 sm:grid-cols-2" @submit.prevent="form.post(route('admin.staff'), { onSuccess: () => form.reset() })"><FormField label="Name" for="sn" :error="form.errors.name"><Input id="sn" v-model="form.name" required /></FormField><FormField label="Email" for="se" :error="form.errors.email"><Input id="se" v-model="form.email" type="email" required /></FormField><FormField label="Role" for="sr" :error="form.errors.role"><SelectField id="sr" v-model="form.role"><option v-for="r in staffRoles" :key="r.value" :value="r.value">{{ r.label }}</option></SelectField></FormField><FormField label="Temporary password" for="sp" :error="form.errors.password" hint="Leave blank to generate a strong one (shown once)."><Input id="sp" v-model="form.password" type="password" autocomplete="new-password" /></FormField><Button type="submit" class="sm:col-span-2" :disabled="form.processing">Create account</Button></form></section>
    </AppLayout>
</template>
