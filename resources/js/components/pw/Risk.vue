<script setup lang="ts">
defineProps<{ category: string }>();
const meaning: Record<string, string> = { A: 'Lower estimated risk', B: 'Moderate estimated risk', C: 'Elevated estimated risk', D: 'High estimated risk' };
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-xs font-medium" :title="`${meaning[category]}. A credit assessment is an estimate, not a guarantee of repayment.`">
        <span class="flex size-5 items-center justify-center rounded text-[11px] font-semibold text-white" :class="{ 'bg-primary': category === 'A', 'bg-chart-3': category === 'B', 'bg-gold': category === 'C', 'bg-expense': category === 'D' }">{{ category }}</span>
        <span class="text-muted-foreground">{{ meaning[category] }}</span>
    </span>
</template>
