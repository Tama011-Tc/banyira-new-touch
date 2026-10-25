<script setup lang="ts">
defineProps<{ tone?: 'neutral' | 'good' | 'warn' | 'bad' | 'demo' }>();
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
        :class="{
            'bg-muted text-muted-foreground': !tone || tone === 'neutral',
            'bg-accent text-accent-foreground': tone === 'good',
            'bg-warning/15 text-warning': tone === 'warn',
            'bg-expense/10 text-expense': tone === 'bad',
            'bg-chart-5/15 text-foreground ring-1 ring-chart-5/40': tone === 'demo',
        }"
    >
        <slot />
    </span>
</template>
