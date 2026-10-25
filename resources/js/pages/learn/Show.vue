<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ article: { slug: string; title: string; category: string; body: string; translated: boolean } }>();
// Plain text only: paragraphs split on blank lines, lines starting "- " become a list. Everything is escaped by Vue.
const blocks = computed(() => props.article.body.split(/\n{2,}/).map((p) => {
    const lines = p.split('\n');
    const bullets = lines.filter((l) => l.startsWith('- '));
    return bullets.length ? { lead: lines.filter((l) => !l.startsWith('- ')).join(' '), items: bullets.map((l) => l.slice(2)) } : { lead: p, items: [] as string[] };
}));
</script>

<template>
    <Head :title="article.title" />
    <AppLayout>
        <Link :href="route('learn.index')" class="text-sm text-primary hover:underline">← All lessons</Link>
        <article class="mt-4 max-w-2xl">
            <p class="text-xs font-medium text-primary">{{ article.category }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ article.title }}</h1>
            <p v-if="!article.translated" class="mt-3 text-xs text-gold">Chichewa version not available yet. Showing English.</p>
            <div class="mt-6 space-y-4 leading-relaxed">
                <template v-for="(b, i) in blocks" :key="i"><p v-if="b.lead">{{ b.lead }}</p><ul v-if="b.items.length" class="list-disc space-y-1 pl-6"><li v-for="(it, j) in b.items" :key="j">{{ it }}</li></ul></template>
            </div>
        </article>
    </AppLayout>
</template>
