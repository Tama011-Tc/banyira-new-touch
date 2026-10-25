<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    value: number; // percent, may exceed 100
    tone?: 'ok' | 'warning' | 'exceeded' | 'goal';
    label: string;
}>();

const width = computed(() => `${Math.min(Math.max(props.value, 0), 100)}%`);
</script>

<template>
    <div
        role="progressbar"
        :aria-label="label"
        :aria-valuenow="Math.min(value, 100)"
        aria-valuemin="0"
        aria-valuemax="100"
        class="h-2.5 w-full overflow-hidden rounded-full bg-muted"
    >
        <div
            class="h-full rounded-full transition-[width] duration-500"
            :class="{
                'bg-primary': !tone || tone === 'ok' || tone === 'goal',
                'bg-warning': tone === 'warning',
                'bg-expense': tone === 'exceeded',
            }"
            :style="{ width }"
        />
    </div>
</template>
