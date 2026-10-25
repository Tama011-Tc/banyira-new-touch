<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Bookmark } from 'lucide-vue-next';
import ListingCard from './ListingCard.vue';

defineProps<{ listings: any[] }>();
</script>

<template>
    <Head title="Watchlist" />
    <AppLayout>
        <PageHeader title="Watchlist" description="Opportunities you saved." />
        <div v-if="listings.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"><ListingCard v-for="l in listings" :key="l.id" :l="l" /></div>
        <EmptyState v-else :icon="Bookmark" title="Nothing saved" description="Use the bookmark on any opportunity to keep it here." />
    </AppLayout>
</template>
