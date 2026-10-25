<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Risk from '@/components/pw/Risk.vue';
import SelectField from '@/components/pw/SelectField.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { label, method, mk, pct } from '@/lib/format';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{ application: Record<string, any>; suggested: { score: number; category: string; criteria: Record<string, any> } | null; is_analyst: boolean; is_approver: boolean; read_only: boolean; can_open_documents: boolean; executive_threshold: number; max_rate_bp: number; transitions: string[] }>();
const a = props.application;
const assess = useForm({ score: props.suggested?.score ?? 50, notes: '', recommendation: 'approve' });
const decide = useForm({ decision: 'approved', reason: '', conditions: '', approved_amount: (a.amount / 100).toFixed(2), max_rate: Math.min(props.max_rate_bp / 100, a.rate_bp / 100 + 3) });
const info = useForm({ request: '' });
const surplus = a.monthly_revenue - a.monthly_expenses;
</script>

<template>
    <Head :title="`Review #${a.id}`" />
    <AppLayout>
        <p v-if="read_only" class="mb-4 rounded-lg border border-gold/50 bg-gold/10 p-3 text-sm">Read-only oversight. Your view of this application is logged. Documents and registration numbers are restricted to credit and compliance staff.</p>
        <PageHeader :title="a.business.display_name" :description="`${mk(a.amount)} over ${a.term_months} months at ${pct(a.rate_bp)} (${method(a.interest_method).toLowerCase()})`"><Status :value="a.status" /></PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Business and request</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-muted-foreground">Legal name</dt><dd>{{ a.business.name }}</dd></div><div><dt class="text-muted-foreground">Registration no.</dt><dd>{{ a.business.registration_no ?? (read_only ? 'restricted' : 'not provided') }}</dd></div>
                        <div><dt class="text-muted-foreground">Sector · district</dt><dd>{{ a.business.sector }} · {{ a.business.district }}</dd></div><div><dt class="text-muted-foreground">Years operating</dt><dd>{{ a.business.years }}</dd></div>
                        <div><dt class="text-muted-foreground">Monthly revenue</dt><dd>{{ mk(a.monthly_revenue) }}</dd></div><div><dt class="text-muted-foreground">Monthly expenses</dt><dd>{{ mk(a.monthly_expenses) }} (surplus {{ mk(surplus) }})</dd></div>
                        <div><dt class="text-muted-foreground">Existing debt</dt><dd>{{ mk(a.existing_debt) }}</dd></div><div><dt class="text-muted-foreground">Registered by sales</dt><dd>{{ a.business.assisted ? 'Yes (assisted)' : 'No' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-muted-foreground">About</dt><dd>{{ a.business.description }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-muted-foreground">Use of funds</dt><dd>{{ a.purpose }}</dd></div>
                        <div v-if="a.repayment_plan" class="sm:col-span-2"><dt class="text-muted-foreground">Repayment plan</dt><dd>{{ a.repayment_plan }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Documents</h2>
                    <ul class="mt-3 divide-y rounded-md border text-sm"><li v-for="d in a.documents" :key="d.id" class="flex justify-between gap-3 p-3"><span>{{ label(d.type) }} · {{ d.name }} <span class="text-xs text-muted-foreground">sha256 {{ d.sha256 }}…</span></span><a v-if="can_open_documents" :href="route('documents.download', d.id)" class="text-primary hover:underline">Open (logged)</a><span v-else class="text-xs text-muted-foreground">restricted</span></li><li v-if="!a.documents.length" class="p-3 text-muted-foreground">No documents.</li></ul>
                </section>

                <section v-if="suggested" class="rounded-lg border bg-card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-semibold">Calculated score: {{ suggested.score }} / 100</h2><Risk :category="suggested.category" /></div>
                    <p class="text-sm text-muted-foreground">Transparent and configurable. It suggests a category; it never decides.</p>
                    <table class="mt-3 w-full text-sm"><thead class="text-left text-xs uppercase text-muted-foreground"><tr><th class="py-1">Criterion</th><th>Weight</th><th>Score</th><th>Why</th></tr></thead><tbody class="divide-y"><tr v-for="(c, k) in suggested.criteria" :key="k"><td class="py-2 font-medium">{{ c.label }}</td><td>{{ c.weight }}%</td><td>{{ c.score }}</td><td class="text-muted-foreground">{{ c.detail }}</td></tr></tbody></table>
                </section>

                <section v-for="x in a.assessments" :key="x.id" class="rounded-lg border bg-card p-5 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-semibold">Assessment by {{ x.analyst }}</h2><Risk :category="x.category" /></div>
                    <p class="mt-1 text-muted-foreground">Score {{ x.score }} (calculated {{ x.criteria.calculated_score }}) · recommends <strong>{{ x.recommendation }}</strong> · {{ x.at }}</p><p class="mt-2">{{ x.notes }}</p>
                </section>
                <section v-for="(d, i) in a.decisions" :key="i" class="rounded-lg border bg-card p-5 text-sm">
                    <h2 class="font-semibold">Decision: {{ label(d.decision) }} by {{ d.by }}</h2><p class="mt-1 text-muted-foreground">{{ d.at }}<template v-if="d.approved_amount"> · {{ mk(d.approved_amount) }} · rate ceiling {{ pct(d.max_rate_bp) }}</template></p><p class="mt-2">{{ d.reason }}</p><p v-if="d.conditions" class="mt-1"><strong>Conditions:</strong> {{ d.conditions }}</p>
                </section>
            </div>

            <aside class="space-y-6">
                <section v-if="is_analyst && transitions.length" class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Move forward</h2>
                    <Button v-for="t in transitions" :key="t" class="mt-3 w-full" variant="outline" @click="router.post(route('credit.advance', a.id), { to: t })">Start {{ t }}</Button>
                </section>
                <section v-if="is_analyst && ['screening', 'assessment'].includes(a.status)" class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Request information</h2>
                    <form class="mt-3 space-y-3" @submit.prevent="info.post(route('credit.info', a.id))"><textarea v-model="info.request" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Exactly what is missing or inconsistent?" /><p v-if="info.errors.request" class="text-sm text-destructive">{{ info.errors.request }}</p><Button type="submit" variant="outline" class="w-full" :disabled="info.processing">Ask applicant</Button></form>
                </section>
                <section v-if="is_analyst && a.status === 'assessment'" class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Record assessment</h2>
                    <form class="mt-3 space-y-3" @submit.prevent="assess.post(route('credit.assess', a.id))">
                        <FormField label="Final score" for="score" :error="assess.errors.score" hint="At most 15 points from the calculated score. Explain any change."><Input id="score" v-model.number="assess.score" type="number" min="0" max="100" /></FormField>
                        <FormField label="Recommendation" for="rec" :error="assess.errors.recommendation"><SelectField id="rec" v-model="assess.recommendation"><option value="approve">Approve</option><option value="reject">Reject</option><option value="refer">Refer for senior review</option></SelectField></FormField>
                        <FormField label="Evidence and notes" for="notes" :error="assess.errors.notes"><textarea id="notes" v-model="assess.notes" rows="4" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
                        <Button type="submit" class="w-full" :disabled="assess.processing">Save assessment</Button>
                    </form>
                </section>
                <section v-if="is_approver && a.status === 'assessment' && a.assessments.length" class="rounded-lg border-2 border-gold/60 bg-card p-5">
                    <h2 class="font-semibold">Final decision</h2>
                    <p class="text-xs text-muted-foreground">Must be a different person from the analyst. Amounts above {{ mk(executive_threshold) }} need an executive.</p>
                    <form class="mt-3 space-y-3" @submit.prevent="decide.post(route('credit.decide', a.id))">
                        <FormField label="Decision" for="dec" :error="decide.errors.decision"><SelectField id="dec" v-model="decide.decision"><option value="approved">Approve</option><option value="rejected">Reject</option></SelectField></FormField>
                        <template v-if="decide.decision === 'approved'">
                            <FormField label="Approved amount (MK)" for="amt" :error="decide.errors.approved_amount"><Input id="amt" v-model="decide.approved_amount" /></FormField>
                            <FormField label="Interest-rate ceiling for negotiation (% a year)" for="mr" :error="decide.errors.max_rate"><Input id="mr" v-model.number="decide.max_rate" type="number" step="0.01" /></FormField>
                            <FormField label="Conditions" for="cond" :error="decide.errors.conditions"><textarea id="cond" v-model="decide.conditions" rows="2" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
                        </template>
                        <FormField label="Reason (required)" for="reason" :error="decide.errors.reason"><textarea id="reason" v-model="decide.reason" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
                        <p v-if="Object.keys(decide.errors).length && !decide.errors.reason && !decide.errors.approved_amount && !decide.errors.max_rate && !decide.errors.decision" class="text-sm text-destructive">Please check the form.</p>
                        <Button type="submit" class="w-full" :disabled="decide.processing">Record decision</Button>
                    </form>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
