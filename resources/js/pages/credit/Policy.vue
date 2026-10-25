<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { mk } from '@/lib/format';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{ bands: { code: string; min: number; name: string; definition: string; max_amount?: number; max_term_months?: number; needs_conditions?: boolean }[]; weights: Record<string, number>; pending: { id: number; reason: string; bands: any[]; mine: boolean }[]; can_request: boolean; can_decide: boolean }>();
const form = useForm({ bands: props.bands.map((b) => ({ ...b, max_amount: b.max_amount ? (b.max_amount / 100).toFixed(2) : '', max_term_months: b.max_term_months ?? '', needs_conditions: !!b.needs_conditions })), reason: '' });
const add = () => form.bands.push({ code: '', min: 0, name: '', definition: '' });
</script>

<template>
    <Head title="Risk bands" />
    <AppLayout>
        <PageHeader title="Risk bands" description="How a score maps to a published risk band. This is credit policy: changes need a request and a second person's approval, and bands already given to assessments are not rewritten." />
        <section class="mb-8 rounded-lg border bg-card p-5">
            <h2 class="font-semibold">Current bands</h2>
            <table class="mt-3 w-full text-sm"><thead class="text-left text-xs uppercase text-muted-foreground"><tr><th class="py-1">Band</th><th>From score</th><th>Meaning</th></tr></thead><tbody class="divide-y"><tr v-for="b in bands" :key="b.code"><td class="py-2 font-semibold">{{ b.code }}</td><td>{{ b.min }}</td><td><strong>{{ b.name }}.</strong> <span class="text-muted-foreground">{{ b.definition }}</span><p v-if="b.max_amount || b.max_term_months || b.needs_conditions" class="mt-0.5 text-xs text-gold">Limits: {{ [b.max_amount ? 'up to ' + mk(b.max_amount) : '', b.max_term_months ? 'up to ' + b.max_term_months + ' months' : '', b.needs_conditions ? 'written conditions required' : ''].filter(Boolean).join(' · ') }}</p></td></tr></tbody></table>
            <p class="mt-3 text-xs text-muted-foreground">Score weights: {{ Object.entries(weights).map(([k, v]) => `${k} ${v}%`).join(' · ') }}. Scores below the lowest band are not eligible for listing. A band is an estimate of risk, never a guarantee of repayment.</p>
        </section>
        <section v-if="pending.length" class="mb-8"><h2 class="mb-3 font-semibold">Waiting for approval</h2>
            <ul class="divide-y rounded-lg border-2 border-gold/50 bg-card"><li v-for="r in pending" :key="r.id" class="p-4 text-sm"><p>{{ r.reason }}</p><p class="mt-1 text-xs text-muted-foreground">{{ r.bands.map((b: any) => `${b.code} ≥ ${b.min}`).join(' · ') }}</p>
                <div v-if="can_decide && !r.mine" class="mt-2 flex gap-2"><Button size="sm" @click="router.post(route('finance.approval', r.id), { approve: true })">Approve</Button><Button size="sm" variant="outline" @click="router.post(route('finance.approval', r.id), { approve: false })">Reject</Button></div><p v-else-if="r.mine" class="mt-1 text-xs text-muted-foreground">Waiting for someone else to decide.</p></li></ul></section>
        <section v-if="can_request" class="max-w-3xl rounded-lg border bg-card p-5">
            <h2 class="font-semibold">Request a change</h2>
            <form class="mt-3 space-y-4" @submit.prevent="form.post(route('credit.bands'))">
                <div v-for="(b, i) in form.bands" :key="i" class="grid gap-3 rounded-md border p-3 sm:grid-cols-[5rem_6rem_1fr]"><FormField label="Code" :for="`c${i}`" :error="form.errors[`bands.${i}.code`]"><Input :id="`c${i}`" v-model="b.code" maxlength="3" /></FormField><FormField label="From score" :for="`m${i}`" :error="form.errors[`bands.${i}.min`]"><Input :id="`m${i}`" v-model.number="b.min" type="number" min="0" max="100" /></FormField><FormField label="Name" :for="`n${i}`" :error="form.errors[`bands.${i}.name`]"><Input :id="`n${i}`" v-model="b.name" /></FormField><div class="grid gap-3 sm:col-span-3 sm:grid-cols-3"><FormField label="Max loan (MK, blank = none)" :for="`ma${i}`"><Input :id="`ma${i}`" v-model="b.max_amount" /></FormField><FormField label="Max term (months)" :for="`mt${i}`"><Input :id="`mt${i}`" v-model="b.max_term_months" type="number" min="1" /></FormField><label class="flex items-end gap-2 pb-2 text-sm"><input v-model="b.needs_conditions" type="checkbox" class="size-4" /> Conditions required</label></div>
                    <div class="sm:col-span-3"><FormField label="Definition" :for="`d${i}`" :error="form.errors[`bands.${i}.definition`]"><Input :id="`d${i}`" v-model="b.definition" /></FormField></div></div>
                <p v-if="form.errors.bands" class="text-sm text-destructive">{{ form.errors.bands }}</p>
                <div class="flex gap-2"><Button type="button" variant="outline" size="sm" @click="add">Add band</Button></div>
                <FormField label="Reason for the change" for="why" :error="form.errors.reason"><Input id="why" v-model="form.reason" /></FormField>
                <Button type="submit" :disabled="form.processing">Request change</Button>
            </form>
        </section>
    </AppLayout>
</template>
