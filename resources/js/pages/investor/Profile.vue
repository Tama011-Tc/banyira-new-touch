<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ profile: { kyc_status: string; district: string | null; phone: string | null; has_national_id: boolean; risk_acknowledged_at: string | null }; districts: string[] }>();
const kyc = useForm({ national_id: '', district: props.profile.district ?? props.districts[0], phone: props.profile.phone ?? '' });
const risk = useForm({ accept: false });
</script>

<template>
    <Head title="Profile and verification" />
    <AppLayout>
        <PageHeader title="Profile and verification" description="We verify every investor before they can contact businesses or lend." />
        <div class="grid max-w-4xl gap-6 md:grid-cols-2">
            <section class="rounded-lg border bg-card p-5">
                <div class="flex items-center justify-between"><h2 class="font-semibold">Identity verification</h2><Status :value="profile.kyc_status" /></div>
                <p v-if="profile.kyc_status === 'verified'" class="mt-3 text-sm text-muted-foreground">Your identity is verified.</p>
                <form v-else class="mt-4 space-y-4" @submit.prevent="kyc.post(route('investor.kyc'))">
                    <p class="text-sm text-muted-foreground">Your national ID number is encrypted at rest and only compliance staff can review it.</p>
                    <FormField label="National ID number" for="nid" :error="kyc.errors.national_id"><Input id="nid" v-model="kyc.national_id" autocomplete="off" required /></FormField>
                    <FormField label="District" for="dist" :error="kyc.errors.district"><SelectField id="dist" v-model="kyc.district"><option v-for="d in districts" :key="d">{{ d }}</option></SelectField></FormField>
                    <FormField label="Phone" for="ph" :error="kyc.errors.phone"><Input id="ph" v-model="kyc.phone" required /></FormField>
                    <Button type="submit" :disabled="kyc.processing || profile.kyc_status === 'submitted'">{{ profile.kyc_status === 'submitted' ? 'Under review' : 'Submit for verification' }}</Button>
                </form>
            </section>
            <section class="rounded-lg border bg-card p-5">
                <h2 class="font-semibold">Risk statement</h2>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-muted-foreground"><li>Businesses may repay late, in part, or not at all.</li><li>You may lose some or all of the money you lend.</li><li>Returns are never guaranteed and credit categories are estimates.</li><li>Loans cannot normally be withdrawn early.</li><li>Lending to few businesses concentrates risk.</li></ul>
                <p v-if="profile.risk_acknowledged_at" class="mt-4 text-sm">Accepted on {{ profile.risk_acknowledged_at }}.</p>
                <form v-else class="mt-4 space-y-3" @submit.prevent="risk.post(route('investor.risk'))"><label class="flex items-start gap-2 text-sm"><input v-model="risk.accept" type="checkbox" class="mt-0.5 size-4" />I have read and understand these risks.</label><p v-if="risk.errors.accept" class="text-sm text-destructive">Please tick to confirm.</p><Button type="submit" :disabled="risk.processing">Accept</Button></form>
            </section>
        </div>
    </AppLayout>
</template>
