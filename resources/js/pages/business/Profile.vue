<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ business: Record<string, any> | null; sectors: string[]; districts: string[] }>();
const b = props.business;
const form = useForm({ name: b?.name ?? '', display_name: b?.display_name ?? '', sector: b?.sector ?? props.sectors[0], district: b?.district ?? props.districts[0], description: b?.description ?? '', years_operating: b?.years_operating ?? 0, contact_phone: b?.contact_phone ?? '', registration_no: '' });
</script>

<template>
    <Head title="My business" />
    <AppLayout>
        <PageHeader title="My business" description="Private details stay private. Investors only see your display name, sector, district and a short summary written by our team." />
        <form class="max-w-2xl space-y-5 rounded-lg border bg-card p-5 sm:p-6" @submit.prevent="form.post(route('business.save'))">
            <div class="grid gap-5 sm:grid-cols-2">
                <FormField label="Legal business name" for="name" :error="form.errors.name"><Input id="name" v-model="form.name" required /></FormField>
                <FormField label="Display name" for="display_name" :error="form.errors.display_name" hint="Shown to investors once approved. Use a name you are happy to publish."><Input id="display_name" v-model="form.display_name" required /></FormField>
                <FormField label="Sector" for="sector" :error="form.errors.sector"><SelectField id="sector" v-model="form.sector"><option v-for="s in sectors" :key="s" :value="s">{{ s }}</option></SelectField></FormField>
                <FormField label="District" for="district" :error="form.errors.district"><SelectField id="district" v-model="form.district"><option v-for="d in districts" :key="d" :value="d">{{ d }}</option></SelectField></FormField>
                <FormField label="Years operating" for="years" :error="form.errors.years_operating"><Input id="years" v-model.number="form.years_operating" type="number" min="0" max="80" required /></FormField>
                <FormField label="Registration number" for="reg" :error="form.errors.registration_no" :hint="business?.has_registration_no ? 'Stored encrypted. Leave blank to keep the current one.' : 'Stored encrypted; never shown publicly.'"><Input id="reg" v-model="form.registration_no" autocomplete="off" /></FormField>
            </div>
            <FormField label="What does the business do?" for="desc" :error="form.errors.description"><textarea id="desc" v-model="form.description" rows="4" required class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
            <FormField label="Contact phone (private)" for="phone" :error="form.errors.contact_phone" hint="Used by our team only. It is never shown to investors."><Input id="phone" v-model="form.contact_phone" /></FormField>
            <Button type="submit" :disabled="form.processing">Save business</Button>
        </form>
    </AppLayout>
</template>
