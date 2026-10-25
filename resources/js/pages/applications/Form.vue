<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ application: Record<string, any> | null; business_id: number; limits: { max_term: number; max_rate: number } }>();
const form = useForm({ business_id: props.business_id, amount: '', purpose: '', term_months: 12, frequency: 'monthly', interest_method: 'reducing', proposed_rate: 24, monthly_revenue: '', monthly_expenses: '', existing_debt: '0', records_months: 6, repayment_plan: '' });
</script>

<template>
    <Head title="New application" />
    <AppLayout>
        <PageHeader title="New financing application" description="Be accurate: credit staff compare these figures with your documents." />
        <form class="max-w-3xl space-y-6" @submit.prevent="form.post(route('applications.store'))">
            <section class="space-y-5 rounded-lg border bg-card p-5">
                <h2 class="font-semibold">The loan you want</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField label="Amount needed (MK)" for="amount" :error="form.errors.amount"><Input id="amount" v-model="form.amount" inputmode="decimal" required placeholder="e.g. 6,000,000" /></FormField>
                    <FormField label="Term (months)" for="term" :error="form.errors.term_months" :hint="`Up to ${limits.max_term} months`"><Input id="term" v-model.number="form.term_months" type="number" min="1" :max="limits.max_term" required /></FormField>
                    <FormField label="Repayments" for="freq" :error="form.errors.frequency"><SelectField id="freq" v-model="form.frequency"><option value="monthly">Monthly</option><option value="weekly">Weekly</option></SelectField></FormField>
                    <FormField label="Interest method" for="method" :error="form.errors.interest_method"><SelectField id="method" v-model="form.interest_method"><option value="reducing">Reducing balance</option><option value="flat">Flat rate on the original amount</option></SelectField></FormField>
                    <FormField label="Interest rate you propose (% per year)" for="rate" :error="form.errors.proposed_rate" :hint="`Platform maximum ${limits.max_rate}%`"><Input id="rate" v-model.number="form.proposed_rate" type="number" step="0.01" min="0" :max="limits.max_rate" required /></FormField>
                </div>
                <FormField label="What will the money be used for?" for="purpose" :error="form.errors.purpose" hint="At least 20 characters. Be specific."><textarea id="purpose" v-model="form.purpose" rows="3" required class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
            </section>
            <section class="space-y-5 rounded-lg border bg-card p-5">
                <h2 class="font-semibold">Your finances</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField label="Monthly revenue (MK)" for="rev" :error="form.errors.monthly_revenue"><Input id="rev" v-model="form.monthly_revenue" inputmode="decimal" required /></FormField>
                    <FormField label="Monthly expenses (MK)" for="exp" :error="form.errors.monthly_expenses"><Input id="exp" v-model="form.monthly_expenses" inputmode="decimal" required /></FormField>
                    <FormField label="Existing debt (MK)" for="debt" :error="form.errors.existing_debt"><Input id="debt" v-model="form.existing_debt" inputmode="decimal" required /></FormField>
                    <FormField label="Months of financial records you have" for="rec" :error="form.errors.records_months" hint="We usually look for at least 6 months."><Input id="rec" v-model.number="form.records_months" type="number" min="0" max="600" required /></FormField>
                </div>
                <FormField label="How will you repay?" for="plan" :error="form.errors.repayment_plan" hint="Optional: sources of repayment, seasonality, contracts."><textarea id="plan" v-model="form.repayment_plan" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
            </section>
            <Button type="submit" :disabled="form.processing">Save draft and add documents</Button>
        </form>
    </AppLayout>
</template>
