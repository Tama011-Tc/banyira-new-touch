<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';

defineProps<{ items: { id: string; title: string; body: string; url: string; read: boolean; at: string }[] }>();
</script>

<template>
    <Head title="Notifications" />
    <AppLayout>
        <PageHeader title="Notifications" description="New work and progress on your applications."><Button variant="outline" size="sm" @click="router.post(route('notifications.read'))">Mark all as read</Button></PageHeader>
        <ul v-if="items.length" class="divide-y rounded-lg border bg-card"><li v-for="n in items" :key="n.id"><Link :href="n.url" class="flex gap-3 p-4 hover:bg-muted/50"><span class="mt-1.5 size-2 shrink-0 rounded-full" :class="n.read ? 'bg-transparent' : 'bg-primary'" aria-hidden="true" /><div class="min-w-0"><p class="text-sm" :class="n.read ? '' : 'font-semibold'">{{ n.title }}</p><p class="text-sm text-muted-foreground">{{ n.body }}</p><p class="mt-0.5 text-xs text-muted-foreground">{{ n.at }}</p></div></Link></li></ul>
        <EmptyState v-else :icon="Bell" title="Nothing yet" description="You will be notified when something needs your attention." />
    </AppLayout>
</template>
