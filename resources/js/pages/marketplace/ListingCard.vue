<script setup lang="ts">
import ProgressBar from '@/components/pw/ProgressBar.vue';
import Risk from '@/components/pw/Risk.vue';
import { closes, daysUntil, mk, pct } from '@/lib/format';
import { Link, router } from '@inertiajs/vue3';
import { BadgeCheck, Bookmark, Clock, MapPin } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ l: Record<string, any> }>();
const urgent = computed(() => daysUntil(props.l.deadline) <= 7);
</script>

<template>
    <article class="group relative flex flex-col rounded-lg border bg-card p-5 transition hover:border-primary/40 hover:shadow-md focus-within:ring-2 focus-within:ring-ring">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="flex items-center gap-1 truncate text-xs text-muted-foreground"><MapPin class="size-3 shrink-0" aria-hidden="true" />{{ l.district }} · {{ l.sector }}</p>
                <!-- The title link stretches over the whole card so the card is one tap target on a phone. -->
                <h3 class="mt-1 font-semibold leading-snug"><Link :href="route('marketplace.show', l.id)" class="after:absolute after:inset-0 after:rounded-lg focus:outline-none group-hover:text-primary">{{ l.title }}</Link></h3>
                <p class="mt-0.5 flex items-center gap-1 truncate text-sm text-muted-foreground">{{ l.business }}<BadgeCheck v-if="l.verified" class="size-3.5 shrink-0 text-primary" aria-label="Business verified" /></p>
            </div>
            <button class="relative z-10 -mr-1.5 -mt-1 rounded-md p-2 hover:bg-muted" :aria-label="l.saved ? 'Remove from watchlist' : 'Save to watchlist'" :aria-pressed="l.saved" @click="router.post(route('watchlist.toggle', l.id), {}, { preserveScroll: true })"><Bookmark class="size-4" :class="l.saved ? 'fill-gold text-gold' : 'text-muted-foreground'" aria-hidden="true" /></button>
        </div>

        <div class="mt-4 flex items-end justify-between gap-3 rounded-md bg-muted/60 px-3 py-2.5">
            <div><p class="text-xs text-muted-foreground">Rate</p><p class="tabular text-2xl font-semibold leading-none text-primary">{{ pct(l.rate_bp) }}<span class="ml-1 text-xs font-normal text-muted-foreground">a year</span></p></div>
            <div class="text-right"><p class="text-xs text-muted-foreground">Term</p><p class="font-semibold">{{ l.term_months }} months</p></div>
        </div>

        <div class="mt-4">
            <div class="mb-1.5 flex items-baseline justify-between gap-2 text-sm"><span><span class="tabular font-semibold">{{ mk(l.committed) }}</span> <span class="text-muted-foreground">of {{ mk(l.target) }}</span></span><span class="tabular text-xs font-medium">{{ l.percent }}%</span></div>
            <ProgressBar :value="l.percent" :label="`${l.title} funding progress`" />
            <p class="mt-1.5 text-xs text-muted-foreground">{{ mk(l.remaining) }} still available · from {{ mk(l.min_investment) }}</p>
        </div>

        <div class="grow" />
        <div class="mt-4 flex items-center justify-between gap-2 border-t pt-3">
            <Risk :category="l.risk_category" />
            <span class="inline-flex items-center gap-1 text-xs" :class="urgent ? 'font-medium text-warning' : 'text-muted-foreground'"><Clock class="size-3" aria-hidden="true" />{{ closes(l.deadline) }}</span>
        </div>
    </article>
</template>
