<script setup lang="ts">
import ProgressBar from '@/components/pw/ProgressBar.vue';
import Risk from '@/components/pw/Risk.vue';
import { date, mk, pct } from '@/lib/format';
import { Link, router } from '@inertiajs/vue3';
import { Bookmark, BadgeCheck, MapPin } from 'lucide-vue-next';

defineProps<{ l: Record<string, any> }>();
</script>

<template>
    <article class="flex flex-col rounded-lg border bg-card p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0"><p class="flex items-center gap-1 text-xs text-muted-foreground"><MapPin class="size-3" aria-hidden="true" />{{ l.district }} · {{ l.sector }}</p><h3 class="mt-1 font-semibold leading-snug"><Link :href="route('marketplace.show', l.id)" class="hover:underline">{{ l.title }}</Link></h3><p class="mt-0.5 flex items-center gap-1 text-sm text-muted-foreground">{{ l.business }}<BadgeCheck v-if="l.verified" class="size-3.5 text-primary" aria-label="Business verified" /></p></div>
            <button class="rounded p-1.5 hover:bg-muted" :aria-label="l.saved ? 'Remove from watchlist' : 'Save to watchlist'" @click="router.post(route('watchlist.toggle', l.id), {}, { preserveScroll: true })"><Bookmark class="size-4" :class="l.saved ? 'fill-gold text-gold' : 'text-muted-foreground'" aria-hidden="true" /></button>
        </div>
        <dl class="mt-4 grid grid-cols-3 gap-2 text-sm">
            <div><dt class="text-xs text-muted-foreground">Seeking</dt><dd class="tabular font-semibold">{{ mk(l.target) }}</dd></div>
            <div><dt class="text-xs text-muted-foreground">Rate</dt><dd class="font-semibold">{{ pct(l.rate_bp) }} <span class="text-xs font-normal text-muted-foreground">a year</span></dd></div>
            <div><dt class="text-xs text-muted-foreground">Term</dt><dd class="font-semibold">{{ l.term_months }} mo</dd></div>
        </dl>
        <div class="mt-4"><div class="mb-1 flex justify-between text-xs text-muted-foreground"><span>{{ mk(l.committed) }} agreed</span><span>{{ l.percent }}%</span></div><ProgressBar :value="l.percent" :label="`${l.title} funding progress`" /></div>
        <div class="mt-4 flex items-center justify-between gap-2"><Risk :category="l.risk_category" /><span class="text-xs text-muted-foreground">Closes {{ date(l.deadline) }}</span></div>
    </article>
</template>
