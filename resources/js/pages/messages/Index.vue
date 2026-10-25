<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { MessageSquare } from 'lucide-vue-next';

defineProps<{ conversations: { id: number; title: string; with: string; last: string | null }[] }>();
</script>

<template>
    <Head title="Messages" />
    <AppLayout>
        <PageHeader title="Messages" description="Protected conversations about published opportunities." />
        <ul v-if="conversations.length" class="divide-y rounded-lg border bg-card"><li v-for="c in conversations" :key="c.id"><Link :href="route('conversations.show', c.id)" class="block p-4 hover:bg-muted/50"><p class="font-medium">{{ c.title }}</p><p class="text-sm text-muted-foreground">With {{ c.with }}</p><p v-if="c.last" class="mt-1 line-clamp-1 text-sm">{{ c.last }}</p></Link></li></ul>
        <EmptyState v-else :icon="MessageSquare" title="No conversations" description="Investors start a conversation from an opportunity page." />
    </AppLayout>
</template>
