<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Pager from '@/components/pw/Pager.vue';
import SelectField from '@/components/pw/SelectField.vue';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Store } from 'lucide-vue-next';
import { reactive } from 'vue';
import ListingCard from './ListingCard.vue';

const props = defineProps<{ listings: { data: any[]; links: any[] }; filters: Record<string, string>; sectors: string[]; districts: string[] }>();
const f = reactive({ search: '', sector: '', district: '', risk: '', max_term: '', min_rate: '', min_amount: '', max_amount: '', sort: '', ...props.filters });
const apply = () => router.get(route('marketplace.index'), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '')), { preserveState: true, preserveScroll: true });
</script>

<template>
    <Head title="Marketplace" />
    <AppLayout>
        <PageHeader title="Opportunities" description="Every business here has been assessed and approved by our credit team. That reduces risk; it does not remove it, and interest shown is never guaranteed income." />
        <form class="mb-6 grid gap-3 rounded-lg border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Filters" @submit.prevent="apply">
            <Input v-model="f.search" placeholder="Search" aria-label="Search" />
            <SelectField v-model="f.sector" aria-label="Sector"><option value="">All sectors</option><option v-for="s in sectors" :key="s">{{ s }}</option></SelectField>
            <SelectField v-model="f.district" aria-label="District"><option value="">All districts</option><option v-for="d in districts" :key="d">{{ d }}</option></SelectField>
            <SelectField v-model="f.risk" aria-label="Risk category"><option value="">Any risk category</option><option value="A">A: lower estimated risk</option><option value="B">B</option><option value="C">C</option><option value="D">D: high estimated risk</option></SelectField>
            <Input v-model="f.min_amount" type="number" placeholder="Min amount (MK)" aria-label="Minimum amount" />
            <Input v-model="f.max_amount" type="number" placeholder="Max amount (MK)" aria-label="Maximum amount" />
            <Input v-model="f.max_term" type="number" placeholder="Max term (months)" aria-label="Maximum term" />
            <Input v-model="f.min_rate" type="number" step="0.5" placeholder="Min rate (% a year)" aria-label="Minimum rate" />
            <SelectField v-model="f.sort" aria-label="Sort"><option value="">Newest</option><option value="rate">Highest rate</option><option value="deadline">Closing soonest</option><option value="amount">Smallest amount</option></SelectField>
            <div class="flex gap-2 sm:col-span-2"><button class="rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground" type="submit">Apply filters</button><button class="rounded-md border px-4 text-sm" type="button" @click="Object.keys(f).forEach((k) => ((f as any)[k] = '')); apply()">Reset</button></div>
        </form>
        <div v-if="listings.data.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"><ListingCard v-for="l in listings.data" :key="l.id" :l="l" /></div>
        <EmptyState v-else :icon="Store" title="No opportunities match" description="Try removing a filter. New businesses are listed after credit review." />
        <Pager :links="listings.links" />
    </AppLayout>
</template>
