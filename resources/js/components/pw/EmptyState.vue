<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{ title: string; description?: string; icon?: Component }>();
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-lg border border-dashed bg-card/50 px-6 py-12 text-center">
        <span v-if="icon" class="mb-4 flex size-12 items-center justify-center rounded-full bg-accent text-accent-foreground">
            <component :is="icon" class="size-6" aria-hidden="true" />
        </span>
        <h3 class="text-base font-semibold">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5 flex flex-wrap justify-center gap-2"><slot /></div>
    </div>
</template>
