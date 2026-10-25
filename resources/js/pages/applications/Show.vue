<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { date, label, method, mk, pct } from '@/lib/format';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck } from 'lucide-vue-next';

const props = defineProps<{ application: Record<string, any>; problems: string[]; doc_types: { required: string[]; optional: string[] }; limits: { max_term: number; max_rate: number } }>();
const a = props.application;
const upload = useForm<{ type: string; file: File | null }>({ type: props.doc_types.required[0], file: null });
const edit = useForm({ amount: (a.amount / 100).toFixed(2), purpose: a.purpose, term_months: a.term_months, frequency: a.frequency, interest_method: a.interest_method, proposed_rate: a.rate_bp / 100, monthly_revenue: (a.monthly_revenue / 100).toFixed(2), monthly_expenses: (a.monthly_expenses / 100).toFixed(2), existing_debt: (a.existing_debt / 100).toFixed(2), records_months: a.records_months, repayment_plan: a.repayment_plan ?? '' });
const have = (t: string) => a.documents.some((d: { type: string }) => d.type === t);
function send() {
    upload.post(route('applications.documents.store', a.id), { forceFormData: true, onSuccess: () => upload.reset('file') });
}
</script>

<template>
    <Head :title="`Application #${a.id}`" />
    <AppLayout>
        <PageHeader :title="`Application #${a.id}`" :description="`${a.business} · ${mk(a.amount)} over ${a.term_months} months`"><Status :value="a.status" /></PageHeader>

        <p v-if="a.assisted_by_name" class="mb-4 rounded-lg border bg-card p-3 text-sm">This application was prepared with help from <strong>{{ a.assisted_by_name }}</strong> at your request. Please check every detail is correct; you can edit a draft until it is submitted.</p>
        <div v-if="a.info_request" class="mb-6 rounded-lg border border-gold/50 bg-gold/10 p-4 text-sm"><p class="font-medium">Credit staff asked for more information</p><p class="mt-1">{{ a.info_request }}</p></div>
        <div v-if="a.decision" class="mb-6 rounded-lg border bg-card p-4 text-sm"><p class="font-medium">Decision: {{ label(a.decision.decision) }}</p><p class="mt-1 text-muted-foreground">{{ a.decision.reason }}</p><p v-if="a.decision.conditions" class="mt-1"><strong>Conditions:</strong> {{ a.decision.conditions }}</p></div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Terms and finances</h2>
                    <dl v-if="!a.editable" class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-muted-foreground">Rate proposed</dt><dd>{{ pct(a.rate_bp) }} a year · {{ method(a.interest_method) }}</dd></div>
                        <div><dt class="text-muted-foreground">Repayments</dt><dd class="capitalize">{{ a.frequency }}</dd></div>
                        <div><dt class="text-muted-foreground">Monthly revenue</dt><dd>{{ mk(a.monthly_revenue) }}</dd></div>
                        <div><dt class="text-muted-foreground">Monthly expenses</dt><dd>{{ mk(a.monthly_expenses) }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-muted-foreground">Use of funds</dt><dd>{{ a.purpose }}</dd></div>
                    </dl>
                    <form v-else class="mt-4 space-y-4" @submit.prevent="edit.put(route('applications.update', a.id))">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Amount (MK)" for="amount" :error="edit.errors.amount"><Input id="amount" v-model="edit.amount" inputmode="decimal" /></FormField>
                            <FormField label="Term (months)" for="term" :error="edit.errors.term_months"><Input id="term" v-model.number="edit.term_months" type="number" min="1" :max="limits.max_term" /></FormField>
                            <FormField label="Rate (% a year)" for="rate" :error="edit.errors.proposed_rate"><Input id="rate" v-model.number="edit.proposed_rate" type="number" step="0.01" :max="limits.max_rate" /></FormField>
                            <FormField label="Repayments" for="f" :error="edit.errors.frequency"><SelectField id="f" v-model="edit.frequency"><option value="monthly">Monthly</option><option value="weekly">Weekly</option></SelectField></FormField>
                            <FormField label="Monthly revenue (MK)" for="rev" :error="edit.errors.monthly_revenue"><Input id="rev" v-model="edit.monthly_revenue" /></FormField>
                            <FormField label="Monthly expenses (MK)" for="exp" :error="edit.errors.monthly_expenses"><Input id="exp" v-model="edit.monthly_expenses" /></FormField>
                            <FormField label="Existing debt (MK)" for="debt" :error="edit.errors.existing_debt"><Input id="debt" v-model="edit.existing_debt" /></FormField>
                            <FormField label="Months of records" for="recm" :error="edit.errors.records_months"><Input id="recm" v-model.number="edit.records_months" type="number" min="0" /></FormField>
                            <FormField label="Interest method" for="m" :error="edit.errors.interest_method"><SelectField id="m" v-model="edit.interest_method"><option value="reducing">Reducing balance</option><option value="flat">Flat rate</option></SelectField></FormField>
                        </div>
                        <FormField label="Use of funds" for="purpose" :error="edit.errors.purpose"><textarea id="purpose" v-model="edit.purpose" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
                        <Button type="submit" variant="outline" :disabled="edit.processing">Save changes</Button>
                    </form>
                </section>

                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Documents</h2>
                    <p class="text-sm text-muted-foreground">Stored privately. Only the credit team and you can open them. Investors never can.</p>
                    <ul class="mt-4 space-y-2">
                        <li v-for="t in doc_types.required" :key="t" class="flex items-center gap-2 text-sm"><CircleCheck v-if="have(t)" class="size-4 text-primary" aria-hidden="true" /><CircleAlert v-else class="size-4 text-gold" aria-hidden="true" /> {{ label(t) }} <span class="text-muted-foreground">(required)</span></li>
                    </ul>
                    <ul v-if="a.documents.length" class="mt-4 divide-y rounded-md border">
                        <li v-for="d in a.documents" :key="d.id" class="flex items-center justify-between gap-3 p-3 text-sm"><span class="min-w-0 truncate">{{ label(d.type) }} · {{ d.name }}</span><span class="flex gap-3"><a :href="route('documents.download', d.id)" class="text-primary hover:underline">Open</a><button v-if="a.editable" class="text-destructive hover:underline" @click="router.delete(route('documents.destroy', d.id))">Remove</button></span></li>
                    </ul>
                    <form v-if="a.editable" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1.4fr_auto] sm:items-end" @submit.prevent="send">
                        <FormField label="Type" for="dt" :error="upload.errors.type"><SelectField id="dt" v-model="upload.type"><option v-for="t in [...doc_types.required, ...doc_types.optional]" :key="t" :value="t">{{ label(t) }}</option></SelectField></FormField>
                        <FormField label="File (PDF, JPG or PNG, up to 5 MB)" for="file" :error="upload.errors.file"><input id="file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm" @input="upload.file = ($event.target as HTMLInputElement).files?.[0] ?? null" /></FormField>
                        <Button type="submit" variant="outline" :disabled="upload.processing || !upload.file">Upload</Button>
                    </form>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Status timeline</h2>
                    <ol class="mt-3 space-y-3 border-l pl-4">
                        <li v-for="(h, i) in a.history" :key="i" class="relative text-sm"><span class="absolute -left-[21px] top-1.5 size-2.5 rounded-full bg-primary" /><p class="font-medium">{{ label(h.to) }}</p><p class="text-xs text-muted-foreground">{{ date(h.at) }}<template v-if="h.reason"> · {{ h.reason }}</template></p></li>
                        <li v-if="!a.history.length" class="text-sm text-muted-foreground">Draft: not submitted yet.</li>
                    </ol>
                </section>
                <section v-if="a.editable" class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Ready to submit?</h2>
                    <ul v-if="problems.length" class="mt-2 space-y-1 text-sm text-gold"><li v-for="p in problems" :key="p" class="flex gap-2"><CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ p }}</li></ul>
                    <p v-else class="mt-2 text-sm text-muted-foreground">Everything required is in place.</p>
                    <Button class="mt-4 w-full" :disabled="problems.length > 0" @click="router.post(route('applications.submit', a.id))">Submit for credit review</Button>
                    <Button class="mt-2 w-full" variant="ghost" @click="router.post(route('applications.withdraw', a.id))">Withdraw application</Button>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
