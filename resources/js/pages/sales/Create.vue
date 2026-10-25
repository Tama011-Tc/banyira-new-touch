<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ sectors: string[]; districts: string[] }>();
const form = useForm({ owner_name: '', owner_email: '', name: '', display_name: '', sector: props.sectors[0], district: props.districts[0], description: '', years_operating: 0, contact_phone: '', registration_no: '', consent: false });
</script>

<template>
    <Head title="Register a business" />
    <AppLayout>
        <PageHeader title="Register a business" description="Only with the owner's informed consent. Your name is recorded against this registration and every change you make." />
        <form class="max-w-2xl space-y-5 rounded-lg border bg-card p-5" @submit.prevent="form.post(route('sales.store'))">
            <div class="grid gap-5 sm:grid-cols-2">
                <FormField label="Owner's full name" for="on" :error="form.errors.owner_name"><Input id="on" v-model="form.owner_name" required /></FormField>
                <FormField label="Owner's email" for="oe" :error="form.errors.owner_email"><Input id="oe" v-model="form.owner_email" type="email" required /></FormField>
                <FormField label="Legal business name" for="n" :error="form.errors.name"><Input id="n" v-model="form.name" required /></FormField>
                <FormField label="Display name" for="dn" :error="form.errors.display_name"><Input id="dn" v-model="form.display_name" required /></FormField>
                <FormField label="Sector" for="s" :error="form.errors.sector"><SelectField id="s" v-model="form.sector"><option v-for="s in sectors" :key="s" :value="s">{{ s }}</option></SelectField></FormField>
                <FormField label="District" for="d" :error="form.errors.district"><SelectField id="d" v-model="form.district"><option v-for="d in districts" :key="d" :value="d">{{ d }}</option></SelectField></FormField>
                <FormField label="Years operating" for="y" :error="form.errors.years_operating"><Input id="y" v-model.number="form.years_operating" type="number" min="0" /></FormField>
                <FormField label="Registration number" for="r" :error="form.errors.registration_no"><Input id="r" v-model="form.registration_no" /></FormField>
            </div>
            <FormField label="What the business does" for="desc" :error="form.errors.description"><textarea id="desc" v-model="form.description" rows="3" required class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
            <label class="flex items-start gap-2 text-sm"><input v-model="form.consent" type="checkbox" class="mt-0.5 size-4 rounded border-input" /><span>The owner has been told what information is collected, who will see it, and that approval and returns are <strong>not guaranteed</strong>, and has agreed to this registration.</span></label>
            <p v-if="form.errors.consent" class="text-sm text-destructive">{{ form.errors.consent }}</p>
            <Button type="submit" :disabled="form.processing">Register and start application</Button>
        </form>
    </AppLayout>
</template>
