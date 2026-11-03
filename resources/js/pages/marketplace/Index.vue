<script setup lang="ts">
import EmptyState from '@/components/pw/EmptyState.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import Pager from '@/components/pw/Pager.vue';
import SelectField from '@/components/pw/SelectField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Search, SlidersHorizontal, Store, X } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import ListingCard from './ListingCard.vue';

const props = defineProps<{ listings: { data: any[]; links: any[]; total?: number }; filters: Record<string, string>; sectors: string[]; districts: string[] }>();
const bands = computed(() => usePage().props.risk_bands as { code: string; name: string }[]);
const f = reactive({ search: '', sector: '', district: '', risk: '', max_term: '', min_rate: '', min_amount: '', max_amount: '', sort: '', ...props.filters });
const panelKeys = ['sector', 'district', 'risk', 'max_term', 'min_rate', 'min_amount', 'max_amount'] as const;
const active = computed(() => panelKeys.filter((k) => props.filters[k]).length);
const open = ref(active.value > 0);
const apply = () => router.get(route('marketplace.index'), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '')), { preserveState: true, preserveScroll: true });
const reset = () => {
    Object.keys(f).forEach((k) => ((f as any)[k] = ''));
    apply();
};
</script>

<template>
    <Head title="Marketplace" />
    <AppLayout>
        <PageHeader title="Opportunities" description="Every business here has been assessed and approved by our credit team. That reduces risk; it does not remove it, and interest shown is never guaranteed income." />

        <form class="mb-6 rounded-lg border bg-card p-3 sm:p-4" aria-label="Filters" @submit.prevent="apply">
            <div class="flex flex-col gap-2 sm:flex-row">
                <div class="relative grow">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input v-model="f.search" type="search" placeholder="Search businesses or purposes" aria-label="Search" class="pl-9" />
                </div>
                <div class="flex gap-2">
                    <SelectField v-model="f.sort" aria-label="Sort" class="grow sm:w-44" @change="apply"><option value="">Newest</option><option value="rate">Highest rate</option><option value="deadline">Closing soonest</option><option value="amount">Smallest amount</option></SelectField>
                    <Button type="button" variant="outline" :aria-expanded="open" aria-controls="more-filters" @click="open = !open">
                        <SlidersHorizontal class="size-4" aria-hidden="true" />Filters<span v-if="active" class="rounded-full bg-primary px-1.5 text-[11px] font-semibold text-primary-foreground">{{ active }}</span>
                    </Button>
                    <Button type="submit" class="hidden sm:inline-flex">Search</Button>
                </div>
            </div>
            <div v-show="open" id="more-filters" class="mt-3 grid gap-3 border-t pt-3 sm:grid-cols-2 lg:grid-cols-4">
                <SelectField v-model="f.sector" aria-label="Sector"><option value="">All sectors</option><option v-for="s in sectors" :key="s">{{ s }}</option></SelectField>
                <SelectField v-model="f.district" aria-label="District"><option value="">All districts</option><option v-for="d in districts" :key="d">{{ d }}</option></SelectField>
                <SelectField v-model="f.risk" aria-label="Risk category"><option value="">Any risk band</option><option v-for="b in bands" :key="b.code" :value="b.code">{{ b.code }}: {{ b.name }}</option></SelectField>
                <Input v-model="f.min_rate" type="number" step="0.5" inputmode="decimal" placeholder="Min rate (% a year)" aria-label="Minimum rate" />
                <Input v-model="f.min_amount" type="number" inputmode="numeric" placeholder="Min amount (MK)" aria-label="Minimum amount" />
                <Input v-model="f.max_amount" type="number" inputmode="numeric" placeholder="Max amount (MK)" aria-label="Maximum amount" />
                <Input v-model="f.max_term" type="number" inputmode="numeric" placeholder="Max term (months)" aria-label="Maximum term" />
                <div class="flex gap-2"><Button type="submit" class="grow">Apply</Button><Button type="button" variant="ghost" @click="reset"><X class="size-4" aria-hidden="true" />Reset</Button></div>
            </div>
            <Button type="submit" class="mt-2 w-full sm:hidden" :class="open ? 'hidden' : ''">Search</Button>
        </form>

        <p v-if="listings.data.length && listings.total !== undefined" class="mb-3 text-sm text-muted-foreground">{{ listings.total }} {{ listings.total === 1 ? 'opportunity' : 'opportunities' }}</p>
        <div v-if="listings.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"><ListingCard v-for="l in listings.data" :key="l.id" :l="l" /></div>
        <EmptyState v-else :icon="Store" title="No opportunities match" description="Try removing a filter. New businesses are listed after credit review."><Button v-if="active || f.search" variant="outline" @click="reset">Clear filters</Button></EmptyState>
        <Pager :links="listings.links" />
    </AppLayout>
</template>
