<script setup lang="ts">
import Logo from '@/components/pw/Logo.vue';
import { Button } from '@/components/ui/button';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Building2, FlaskConical, Landmark, LineChart, MessageSquareLock, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const signedIn = computed(() => !!page.props.auth.user);
const brand = computed(() => page.props.brand as { name: string; tagline: string });

const steps = [
    { icon: Building2, title: 'A business applies', text: 'Owners describe the business, what the money is for and how they will repay it, with supporting documents.' },
    { icon: ShieldCheck, title: 'Credit staff assess it', text: 'Analysts review the evidence with a transparent score. A different approver records the decision and the reason.' },
    { icon: LineChart, title: 'Approved listings go live', text: 'Investors see only reviewed, non-confidential facts: terms, risk category, fees and the risks that remain.' },
    { icon: MessageSquareLock, title: 'Negotiate on-platform', text: 'Investor and business agree terms through versioned offers inside protected messaging.' },
];
</script>

<template>
    <Head title="SME lending, made transparent" />
    <div class="min-h-svh bg-background">
        <header class="bg-sidebar text-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
                <Link :href="route('home')" class="flex items-center gap-2.5"><Logo :size="30" /><span class="font-semibold tracking-tight">{{ brand.name }}</span></Link>
                <nav class="flex items-center gap-2" aria-label="Account">
                    <Button v-if="signedIn" size="sm" as-child><Link :href="route('dashboard')">Open workspace</Link></Button>
                    <template v-else>
                        <Button variant="ghost" size="sm" class="text-white hover:bg-white/10 hover:text-white" as-child><Link :href="route('login')">Log in</Link></Button>
                        <Button size="sm" as-child><Link :href="route('register')">Get started</Link></Button>
                    </template>
                </nav>
            </div>
            <div class="mx-auto max-w-6xl px-4 pb-16 pt-12 sm:px-6 sm:pt-20">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-gold/15 px-3 py-1 text-xs font-medium text-[#f3d98b] ring-1 ring-gold/40"><FlaskConical class="size-3.5" aria-hidden="true" /> Prototype · simulated money only</p>
                <h1 class="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">Finance Malawian small businesses. Understand every risk before you lend.</h1>
                <p class="mt-5 max-w-2xl text-lg text-white/70">{{ brand.name }} connects eligible investors with vetted SMEs seeking financing, under clear terms, with independent credit assessment and a full audit trail.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Button size="lg" as-child><Link :href="signedIn ? route('dashboard') : route('register')">{{ signedIn ? 'Go to workspace' : 'Create an account' }}</Link></Button>
                    <Button size="lg" variant="outline" class="border-white/30 bg-transparent text-white hover:bg-white/10 hover:text-white" as-child><Link :href="route('risks')">How it works &amp; the risks</Link></Button>
                </div>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6" aria-label="How it works">
                <ol class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <li v-for="(s, i) in steps" :key="s.title" class="rounded-lg border bg-card p-5">
                        <span class="flex size-9 items-center justify-center rounded-md bg-accent text-accent-foreground"><component :is="s.icon" class="size-4.5" aria-hidden="true" /></span>
                        <p class="mt-4 text-xs font-medium text-muted-foreground">Step {{ i + 1 }}</p>
                        <h2 class="font-semibold">{{ s.title }}</h2>
                        <p class="mt-1.5 text-sm text-muted-foreground">{{ s.text }}</p>
                    </li>
                </ol>
            </section>

            <section class="border-y bg-card">
                <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3">
                    <div><Landmark class="size-5 text-gold" aria-hidden="true" /><h2 class="mt-3 font-semibold">Not a bank, not a guarantee</h2><p class="mt-1.5 text-sm text-muted-foreground">Lending to small businesses carries real risk. Returns are never guaranteed, repayments can be late or missed, and you can lose some or all of the money you lend.</p></div>
                    <div><ShieldCheck class="size-5 text-gold" aria-hidden="true" /><h2 class="mt-3 font-semibold">Controls you can inspect</h2><p class="mt-1.5 text-sm text-muted-foreground">Sales staff cannot approve credit. IT cannot alter loans or payments. Financial records are append-only, and every sensitive action is logged.</p></div>
                    <div><FlaskConical class="size-5 text-gold" aria-hidden="true" /><h2 class="mt-3 font-semibold">Simulation until approved</h2><p class="mt-1.5 text-sm text-muted-foreground">This build uses a clearly labelled sandbox. Real-money operation stays switched off until legal, regulatory and payment-provider approvals are verified.</p></div>
                </div>
            </section>
        </main>
        <footer class="mx-auto max-w-6xl px-4 py-8 text-xs text-muted-foreground sm:px-6">{{ brand.name }} is a portfolio prototype. All businesses, investors and figures shown are fictional demo data.</footer>
    </div>
</template>
