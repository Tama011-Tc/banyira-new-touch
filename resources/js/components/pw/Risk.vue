<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ category: string }>();
const bands = computed(() => (usePage().props.risk_bands ?? []) as { code: string; name: string; definition: string }[]);
const band = computed(() => bands.value.find((b) => b.code === props.category));
const tone = computed(() => {
    if (props.category === 'NR') return 'bg-muted-foreground';
    const i = bands.value.findIndex((b) => b.code === props.category);
    return i <= 0 ? 'bg-primary' : i === 1 ? 'bg-chart-3' : i === 2 ? 'bg-gold' : 'bg-expense';
});
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-xs font-medium" :title="`${band?.definition ?? 'Retired risk band.'} A credit assessment is an estimate, not a guarantee of repayment.`">
        <span class="flex h-5 min-w-5 items-center justify-center rounded px-1 text-[11px] font-semibold text-white" :class="tone">{{ category }}</span>
        <span class="text-muted-foreground">{{ category === 'NR' ? 'Not eligible for listing' : (band?.name ?? 'Earlier scale') }}</span>
    </span>
</template>
