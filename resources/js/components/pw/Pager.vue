<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{ links: { url: string | null; label: string; active: boolean }[] }>();
</script>

<template>
    <nav v-if="links.length > 3" class="mt-4 flex flex-wrap justify-center gap-1" aria-label="Pagination">
        <template v-for="(l, i) in links" :key="i">
            <span v-if="!l.url" class="rounded-md px-3 py-1.5 text-sm text-muted-foreground/50" v-html="l.label" />
            <Link v-else :href="l.url" preserve-scroll class="rounded-md px-3 py-1.5 text-sm hover:bg-muted" :class="l.active ? 'bg-primary text-primary-foreground hover:bg-primary' : ''" v-html="l.label" />
        </template>
    </nav>
</template>
