<script setup lang="ts">
import StatusBadge from '@/components/pw/StatusBadge.vue';
import { label, tone } from '@/lib/format';

defineProps<{ value: string }>();
</script>

<template>
    <StatusBadge :tone="tone(value)">{{ label(value) }}</StatusBadge>
</template>
