<script setup lang="ts">
import PageHeader from '@/components/pw/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{ articles: { slug: string; title: string; category: string; summary: string; translated: boolean }[]; locale: string }>();
const set = (locale: string) => router.post(route('locale'), { locale }, { preserveScroll: true });
</script>

<template>
    <Head title="Learn" />
    <AppLayout>
        <PageHeader title="Learn: running a business well" description="Short lessons on cash flow, bookkeeping and borrowing. General information, not legal, tax or investment advice.">
            <div class="flex rounded-md border text-sm" role="group" aria-label="Language"><button class="px-3 py-1.5" :class="locale === 'en' ? 'bg-accent font-medium' : ''" @click="set('en')">English</button><button class="px-3 py-1.5" :class="locale === 'ny' ? 'bg-accent font-medium' : ''" @click="set('ny')">Chichewa</button></div>
        </PageHeader>
        <p v-if="locale === 'ny'" class="mb-4 rounded-lg bg-muted p-3 text-sm text-muted-foreground">Chichewa lessons are being prepared by translators. Until a lesson is translated, the English version is shown.</p>
        <ul class="grid gap-4 md:grid-cols-2">
            <li v-for="a in articles" :key="a.slug"><Link :href="route('learn.show', a.slug)" class="block h-full rounded-lg border bg-card p-5 hover:border-primary/50"><p class="text-xs font-medium text-primary">{{ a.category }}</p><h2 class="mt-1 font-semibold">{{ a.title }}</h2><p class="mt-1 text-sm text-muted-foreground">{{ a.summary }}</p><p v-if="!a.translated" class="mt-2 text-xs text-gold">English version</p></Link></li>
            <li v-if="!articles.length" class="text-muted-foreground">No lessons published yet.</li>
        </ul>
    </AppLayout>
</template>
