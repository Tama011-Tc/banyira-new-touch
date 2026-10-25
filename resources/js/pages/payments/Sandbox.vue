<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import Sim from '@/components/pw/Sim.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { mk } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{ payment: { id: number; purpose: string; amount: number; status: string; provider_ref: string; failure_reason: string | null; agreement_id: number } }>();
const settle = (succeed: boolean) => router.post(route('payments.settle', props.payment.id), { succeed });
</script>

<template>
    <Head title="Sandbox payment" />
    <AppLayout>
        <PageHeader title="Sandbox payment provider" description="A stand-in for a real gateway (mobile money or bank transfer). No money moves."><Sim /></PageHeader>
        <div class="max-w-lg rounded-lg border-2 border-dashed border-gold/60 bg-card p-6">
            <dl class="space-y-2 text-sm"><div class="flex justify-between"><dt class="text-muted-foreground">Payment for</dt><dd class="capitalize">{{ payment.purpose }}</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Amount</dt><dd class="tabular text-lg font-semibold">{{ mk(payment.amount) }}</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Provider reference</dt><dd class="font-mono text-xs">{{ payment.provider_ref }}</dd></div><div class="flex justify-between"><dt class="text-muted-foreground">Platform status</dt><dd><Status :value="payment.status" /></dd></div></dl>
            <p v-if="payment.failure_reason" class="mt-3 text-sm text-destructive">{{ payment.failure_reason }}</p>
            <div v-if="payment.status === 'initiated'" class="mt-6">
                <p class="mb-3 text-xs text-muted-foreground">Pretend you approve this on your phone. The provider then notifies the platform with a signed message, and the platform verifies the payment with the provider before recording anything.</p>
                <div class="flex gap-2"><Button @click="settle(true)">Approve payment</Button><Button variant="outline" @click="settle(false)">Decline</Button></div>
            </div>
            <Button v-else class="mt-6" as-child><Link :href="route('agreements.show', payment.agreement_id)">Back to the agreement</Link></Button>
        </div>
        <p class="mt-4 max-w-lg text-xs text-muted-foreground">Coming back from a provider page never proves payment. The platform only trusts a signed callback that the provider itself confirms.</p>
    </AppLayout>
</template>
