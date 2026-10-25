<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string;
    hint?: string;
    tone?: 'neutral' | 'income' | 'expense';
    icon?: Component; // accepted for compatibility; the minimal design shows no icons
}>();
</script>

<template>
    <div class="rounded-lg border bg-card p-5">
        <p class="text-sm text-muted-foreground">{{ label }}</p>
        <p class="tabular mt-2 truncate text-2xl font-semibold tracking-tight" :class="{ 'text-income': tone === 'income', 'text-expense': tone === 'expense' }">
            {{ value }}
        </p>
        <p v-if="hint" class="mt-1 truncate text-xs text-muted-foreground">{{ hint }}</p>
    </div>
</template>
