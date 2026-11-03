<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Check, Landmark, Lock, ShieldCheck, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ profile: { payout_bank: string | null; payout_account_name: string | null; payout_account_masked: string | null; payout_details_changed_at: string | null; kyc_status: string; district: string | null; phone: string | null; has_national_id: boolean; risk_acknowledged_at: string | null }; districts: string[] }>();
const kyc = useForm({ national_id: '', district: props.profile.district ?? props.districts[0], phone: props.profile.phone ?? '' });
const risk = useForm({ accept: false });
const payout = useForm({ payout_bank: props.profile.payout_bank ?? '', payout_account_name: props.profile.payout_account_name ?? '', payout_account_number: '', current_password: '' });

const steps = computed(() => [
    { id: 'identity', label: 'Verify identity', done: props.profile.kyc_status === 'verified', pending: props.profile.kyc_status === 'submitted' },
    { id: 'risk', label: 'Accept risks', done: !!props.profile.risk_acknowledged_at, pending: false },
    { id: 'bank', label: 'Add bank account', done: !!props.profile.payout_account_masked, pending: false },
]);
const complete = computed(() => steps.value.every((s) => s.done));
</script>

<template>
    <Head title="Profile and verification" />
    <AppLayout>
        <PageHeader title="Profile and verification" description="We verify every investor before they can contact businesses or lend." />

        <div class="max-w-4xl">
            <nav class="mb-6 rounded-lg border bg-card p-4" aria-label="Setup progress">
                <ol class="grid grid-cols-3 gap-2">
                    <li v-for="(s, n) in steps" :key="s.id">
                        <a :href="`#${s.id}`" class="flex flex-col items-center gap-1.5 rounded-md p-1 text-center text-xs sm:flex-row sm:text-left sm:text-sm">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold" :class="s.done ? 'bg-primary text-primary-foreground' : s.pending ? 'bg-gold/20 text-foreground ring-1 ring-gold' : 'bg-muted text-muted-foreground'">
                                <Check v-if="s.done" class="size-4" aria-hidden="true" /><template v-else>{{ n + 1 }}</template>
                            </span>
                            <span :class="s.done ? 'text-muted-foreground' : 'font-medium'">{{ s.label }}<span class="sr-only">{{ s.done ? ' (done)' : s.pending ? ' (under review)' : ' (to do)' }}</span></span>
                        </a>
                    </li>
                </ol>
                <p v-if="complete" class="mt-3 flex items-center justify-between gap-3 border-t pt-3 text-sm">
                    <span class="flex items-center gap-1.5"><ShieldCheck class="size-4 text-primary" aria-hidden="true" />You're all set to lend.</span>
                    <Button size="sm" as-child><Link :href="route('marketplace.index')">Browse opportunities</Link></Button>
                </p>
            </nav>

            <div class="grid gap-6 md:grid-cols-2">
                <section id="identity" class="scroll-mt-4 rounded-lg border bg-card p-5">
                    <div class="flex items-center justify-between gap-2"><h2 class="font-semibold">1. Identity verification</h2><Status :value="profile.kyc_status" /></div>
                    <p v-if="profile.kyc_status === 'verified'" class="mt-3 flex items-center gap-1.5 text-sm text-muted-foreground"><Check class="size-4 text-primary" aria-hidden="true" />Your identity is verified.</p>
                    <div v-else-if="profile.kyc_status === 'submitted'" class="mt-3 rounded-md bg-muted/60 p-3 text-sm">Thanks, your details are with our compliance team. We'll notify you when the review is done.</div>
                    <form v-else class="mt-4 space-y-4" @submit.prevent="kyc.post(route('investor.kyc'))">
                        <p class="flex gap-1.5 text-sm text-muted-foreground"><Lock class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />Your national ID number is encrypted and only compliance staff can review it.</p>
                        <FormField label="National ID number" for="nid" :error="kyc.errors.national_id"><Input id="nid" v-model="kyc.national_id" autocomplete="off" autocapitalize="characters" required /></FormField>
                        <FormField label="District" for="dist" :error="kyc.errors.district"><SelectField id="dist" v-model="kyc.district"><option v-for="d in districts" :key="d">{{ d }}</option></SelectField></FormField>
                        <FormField label="Phone" for="ph" :error="kyc.errors.phone"><Input id="ph" v-model="kyc.phone" type="tel" inputmode="tel" autocomplete="tel" required /></FormField>
                        <Button type="submit" class="w-full sm:w-auto" :disabled="kyc.processing">Submit for verification</Button>
                    </form>
                </section>

                <section id="risk" class="scroll-mt-4 rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">2. Risk statement</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="r in ['Businesses may repay late, in part, or not at all.', 'You may lose some or all of the money you lend.', 'Returns are never guaranteed and credit categories are estimates.', 'Loans cannot normally be withdrawn early.', 'Lending to few businesses concentrates risk.']" :key="r" class="flex gap-2 text-muted-foreground"><TriangleAlert class="mt-0.5 size-3.5 shrink-0 text-gold" aria-hidden="true" />{{ r }}</li>
                    </ul>
                    <p v-if="profile.risk_acknowledged_at" class="mt-4 flex items-center gap-1.5 text-sm"><Check class="size-4 text-primary" aria-hidden="true" />Accepted on {{ profile.risk_acknowledged_at }}.</p>
                    <form v-else class="mt-4 space-y-3" @submit.prevent="risk.post(route('investor.risk'))">
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border p-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-primary/5"><input v-model="risk.accept" type="checkbox" class="mt-0.5 size-4 accent-[hsl(var(--primary))]" />I have read and understand these risks.</label>
                        <p v-if="risk.errors.accept" class="text-sm text-destructive">Please tick to confirm.</p>
                        <Button type="submit" class="w-full sm:w-auto" :disabled="risk.processing || !risk.accept">Accept</Button>
                    </form>
                </section>

                <section id="bank" class="scroll-mt-4 rounded-lg border bg-card p-5 md:col-span-2">
                    <h2 class="font-semibold">3. Where we pay your repayments</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Repayments from businesses are paid to this bank account. The number is encrypted and only the last 4 digits are ever shown.</p>
                    <div v-if="profile.payout_account_masked" class="mt-4 flex items-start gap-3 rounded-md border bg-muted/40 p-3 text-sm">
                        <Landmark class="mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true" />
                        <div><p class="font-medium">{{ profile.payout_bank }} · {{ profile.payout_account_masked }}</p><p class="text-muted-foreground">{{ profile.payout_account_name }}<span v-if="profile.payout_details_changed_at"> · changed {{ profile.payout_details_changed_at }}</span></p></div>
                    </div>
                    <p v-else class="mt-4 rounded-md border border-gold/50 bg-gold/10 p-3 text-sm">Add your bank details before you fund a loan.</p>
                    <h3 class="mt-5 text-sm font-semibold">{{ profile.payout_account_masked ? 'Change bank account' : 'Add bank account' }}</h3>
                    <form class="mt-3 grid gap-4 sm:grid-cols-2" @submit.prevent="payout.put(route('investor.payout'), { preserveScroll: true, onSuccess: () => payout.reset('payout_account_number', 'current_password') })">
                        <FormField label="Bank" for="pb" :error="payout.errors.payout_bank"><Input id="pb" v-model="payout.payout_bank" required /></FormField>
                        <FormField label="Account name" for="pn" :error="payout.errors.payout_account_name"><Input id="pn" v-model="payout.payout_account_name" autocomplete="name" required /></FormField>
                        <FormField label="Account number" for="pa" :error="payout.errors.payout_account_number"><Input id="pa" v-model="payout.payout_account_number" inputmode="numeric" autocomplete="off" required /></FormField>
                        <FormField label="Your password (to confirm)" for="pp" :error="payout.errors.current_password"><Input id="pp" v-model="payout.current_password" type="password" autocomplete="current-password" required /></FormField>
                        <Button type="submit" class="w-full sm:col-span-2 sm:w-fit" :disabled="payout.processing">Save bank details</Button>
                    </form>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
