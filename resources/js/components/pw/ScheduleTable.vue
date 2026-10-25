<script setup lang="ts">
import Status from '@/components/pw/Status.vue';
import { date, mk } from '@/lib/format';

defineProps<{ rows: { number: number; due_on: string; principal_due: number; interest_due: number; principal_paid?: number; interest_paid?: number; status?: string }[]; showPaid?: boolean }>();
</script>

<template>
    <div class="overflow-x-auto rounded-lg border">
        <table class="w-full min-w-[34rem] text-sm">
            <caption class="sr-only">Repayment schedule</caption>
            <thead class="bg-muted/60 text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th scope="col" class="px-3 py-2">#</th><th scope="col" class="px-3 py-2">Due</th>
                    <th scope="col" class="px-3 py-2 text-right">Principal</th><th scope="col" class="px-3 py-2 text-right">Interest</th><th scope="col" class="px-3 py-2 text-right">Instalment</th>
                    <template v-if="showPaid"><th scope="col" class="px-3 py-2 text-right">Paid</th><th scope="col" class="px-3 py-2">Status</th></template>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="r in rows" :key="r.number">
                    <td class="px-3 py-2">{{ r.number }}</td><td class="px-3 py-2">{{ date(r.due_on) }}</td>
                    <td class="tabular px-3 py-2 text-right">{{ mk(r.principal_due) }}</td><td class="tabular px-3 py-2 text-right">{{ mk(r.interest_due) }}</td>
                    <td class="tabular px-3 py-2 text-right font-medium">{{ mk(r.principal_due + r.interest_due) }}</td>
                    <template v-if="showPaid"><td class="tabular px-3 py-2 text-right">{{ mk((r.principal_paid ?? 0) + (r.interest_paid ?? 0)) }}</td><td class="px-3 py-2"><Status :value="r.status ?? 'scheduled'" /></td></template>
                </tr>
            </tbody>
        </table>
    </div>
</template>
