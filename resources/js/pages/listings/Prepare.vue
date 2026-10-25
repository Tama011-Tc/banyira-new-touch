<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { mk } from '@/lib/format';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ application: { id: number; business: string; amount: number; sector: string; district: string; purpose: string } }>();
const a = props.application;
const form = useForm({ title: '', summary: '', risk_disclosure: 'Repayment depends on the business continuing to trade profitably. Late or missed payments, partial repayment and loss of capital are possible. The credit assessment is an estimate, not a guarantee.', min_investment: '1000', max_investment: (a.amount / 100).toFixed(2), deadline: '' });
</script>

<template>
    <Head title="Prepare listing" />
    <AppLayout>
        <PageHeader title="Prepare listing" :description="`${a.business} · ${a.sector}, ${a.district} · ${mk(a.amount)}`" />
        <form class="max-w-3xl space-y-5 rounded-lg border bg-card p-5" @submit.prevent="form.post(route('listings.store', a.id))">
            <p class="rounded-md bg-muted p-3 text-sm text-muted-foreground">Write only what investors may see. Do not include contact details, links, addresses, ID numbers, bank details or confidential financials. The system rejects text that looks like contact details.</p>
            <FormField label="Headline" for="t" :error="form.errors.title"><Input id="t" v-model="form.title" maxlength="120" required /></FormField>
            <FormField label="Business summary" for="s" :error="form.errors.summary"><textarea id="s" v-model="form.summary" rows="4" required class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
            <FormField label="Key risks and limitations" for="r" :error="form.errors.risk_disclosure"><textarea id="r" v-model="form.risk_disclosure" rows="4" required class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
            <div class="grid gap-5 sm:grid-cols-3">
                <FormField label="Minimum investment (MK)" for="mi" :error="form.errors.min_investment"><Input id="mi" v-model="form.min_investment" /></FormField>
                <FormField label="Maximum investment (MK)" for="ma" :error="form.errors.max_investment"><Input id="ma" v-model="form.max_investment" /></FormField>
                <FormField label="Funding deadline" for="d" :error="form.errors.deadline"><Input id="d" v-model="form.deadline" type="date" required /></FormField>
            </div>
            <Button type="submit" :disabled="form.processing">Save draft listing</Button>
        </form>
    </AppLayout>
</template>
