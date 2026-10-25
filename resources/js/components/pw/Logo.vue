<script setup lang="ts">
defineProps<{ size?: number }>();
</script>

<template>
    <svg :width="size ?? 32" :height="size ?? 32" viewBox="0 0 32 32" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
        <rect width="32" height="32" rx="8" fill="#0b1a33" />
        <path d="M10 8v16M10 8h6.2a4.3 4.3 0 0 1 0 8.6H10m6.8 0a4.3 4.3 0 0 1 0 8.4H10" stroke="#34d399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
        <circle cx="24.5" cy="8.5" r="2" fill="#d4a62a" />
    </svg>
</template>
