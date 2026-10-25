<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ values: Record<string, any>; rules: string[]; note: string }>();
const form = useForm({ moderation_rules: (props.values['moderation.rules'] ?? props.rules) as string[], default_days: props.values['loan.default_days'], max_rate: props.values['loan.max_rate_bp'] / 100, max_term: props.values['loan.max_term_months'] });
</script>

<template>
    <Head title="System settings" />
    <AppLayout>
        <PageHeader title="System settings" description="Operational configuration. Every save is recorded in the audit log." />
        <p class="mb-6 max-w-2xl rounded-lg bg-muted p-3 text-sm text-muted-foreground">{{ note }}</p>
        <form class="max-w-2xl space-y-6" @submit.prevent="form.put(route('admin.settings.update'))">
            <section class="rounded-lg border bg-card p-5"><h2 class="font-semibold">Messaging protection rules</h2><p class="text-xs text-muted-foreground">Tune detection if a rule produces too many false positives. Blocked messages can always be appealed.</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2"><label v-for="r in rules" :key="r" class="flex items-center gap-2 text-sm"><input v-model="form.moderation_rules" type="checkbox" :value="r" class="size-4" /> {{ r.replace('_', ' ') }}</label></div></section>
            <section class="rounded-lg border bg-card p-5"><h2 class="font-semibold">Lending limits</h2><div class="mt-3 grid gap-4 sm:grid-cols-3"><FormField label="Days past due before default can be recorded" for="dd" :error="form.errors.default_days"><Input id="dd" v-model.number="form.default_days" type="number" /></FormField><FormField label="Maximum rate (% a year)" for="mr" :error="form.errors.max_rate"><Input id="mr" v-model.number="form.max_rate" type="number" step="0.01" /></FormField><FormField label="Maximum term (months)" for="mt" :error="form.errors.max_term"><Input id="mt" v-model.number="form.max_term" type="number" /></FormField></div></section>
            <Button type="submit" :disabled="form.processing">Save settings</Button>
        </form>
    </AppLayout>
</template>
