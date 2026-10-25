<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CircleCheck, Circle, TriangleAlert } from 'lucide-vue-next';
import { reactive } from 'vue';

const props = defineProps<{ checks: any[]; mode: string; complete: boolean; live_possible: boolean; kyc_queue: any[]; business_queue: any[] }>();
const can = usePage().props.can as Record<string, boolean>;
const evidence = reactive<Record<number, string>>({});
const approve = (id: number) => router.post(route('compliance.check', id), { evidence: evidence[id] ?? '' });
</script>

<template>
    <Head title="Compliance" />
    <AppLayout>
        <PageHeader title="Compliance" description="Real-money operation stays off until every check below is approved with evidence, and a live provider exists.">
            <Status :value="mode === 'live' ? 'active' : 'simulated'" />
        </PageHeader>

        <section class="mb-8 rounded-lg border-2 p-5" :class="mode === 'live' ? 'border-primary' : 'border-gold/60'">
            <div class="flex items-start gap-3"><TriangleAlert class="mt-0.5 size-5 shrink-0 text-gold" aria-hidden="true" /><div class="text-sm"><p class="font-semibold">Operating mode: {{ mode }}</p><p class="mt-1 text-muted-foreground">Being able to use a payment gateway does not by itself authorise lending or investment activity. Qualified Malawian legal and financial-services advice, and regulator engagement (including the Reserve Bank of Malawi where applicable), are required before any real money is handled.</p>
                <p class="mt-2">{{ complete ? 'All checks are approved.' : 'Checks outstanding.' }} {{ live_possible ? '' : 'No live payment provider is implemented in this build, so live mode cannot be enabled even when the checklist is complete.' }}</p>
                <Button v-if="can['compliance.golive'] && mode !== 'live'" class="mt-3" variant="outline" size="sm" @click="router.post(route('compliance.live'))">Request live mode</Button></div></div>
        </section>

        <h2 class="mb-3 font-semibold">Go-live checklist</h2>
        <ul class="divide-y rounded-lg border bg-card">
            <li v-for="c in checks" :key="c.id" class="p-4 text-sm">
                <div class="flex items-start gap-3"><CircleCheck v-if="c.status === 'approved'" class="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true" /><Circle v-else class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                    <div class="min-w-0 flex-1"><p class="font-medium">{{ c.label }}</p><p class="text-muted-foreground">{{ c.description }}</p>
                        <p v-if="c.status === 'approved'" class="mt-1 text-xs">Approved by {{ c.approved_by }} on {{ c.approved_at }}. Evidence: {{ c.evidence }}</p>
                        <div v-else class="mt-2 flex gap-2"><Input v-model="evidence[c.id]" placeholder="Evidence reference (document, date, adviser)" class="h-9" /><Button size="sm" variant="outline" @click="approve(c.id)">Approve</Button></div></div></div>
            </li>
        </ul>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section><h2 class="mb-3 font-semibold">Investor identity reviews</h2>
                <ul class="divide-y rounded-lg border bg-card text-sm"><li v-for="k in kyc_queue" :key="k.id" class="flex flex-wrap items-center justify-between gap-2 p-4"><div><p class="font-medium">{{ k.name }}</p><p class="text-muted-foreground">{{ k.email }} · {{ k.district }} · ID {{ k.national_id_masked }}</p></div><div v-if="can['kyc.review']" class="flex gap-2"><Button size="sm" @click="router.post(route('compliance.kyc', k.id), { decision: 'verified' })">Verify</Button><Button size="sm" variant="outline" @click="router.post(route('compliance.kyc', k.id), { decision: 'rejected' })">Reject</Button></div></li><li v-if="!kyc_queue.length" class="p-4 text-muted-foreground">Nothing waiting.</li></ul></section>
            <section><h2 class="mb-3 font-semibold">Business verification</h2>
                <ul class="divide-y rounded-lg border bg-card text-sm"><li v-for="b in business_queue" :key="b.id" class="flex flex-wrap items-center justify-between gap-2 p-4"><div><p class="font-medium">{{ b.display_name }}</p><p class="text-muted-foreground">{{ b.sector }} · {{ b.district }}</p></div><div v-if="can['kyc.review']" class="flex gap-2"><Button size="sm" @click="router.post(route('compliance.business', b.id), { decision: 'verified' })">Verify</Button><Button size="sm" variant="outline" @click="router.post(route('compliance.business', b.id), { decision: 'rejected' })">Reject</Button></div></li><li v-if="!business_queue.length" class="p-4 text-muted-foreground">Nothing waiting.</li></ul></section>
        </div>
    </AppLayout>
</template>
