<script setup lang="ts">
import Logo from '@/components/pw/Logo.vue';
import Toasts from '@/components/pw/Toasts.vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Activity, BarChart3, Bell, Bookmark, Building2, Coins, Cog, FileText, Flag, Home, Inbox, List, LogOut, Menu, MessageSquare, PieChart, Plus, Scroll, ShieldCheck, Store, Users, Wallet, FlaskConical } from 'lucide-vue-next';
import { computed, ref, type Component } from 'vue';

defineProps<{ breadcrumbs?: unknown[] }>();

const icons: Record<string, Component> = { store: Store, bookmark: Bookmark, pie: PieChart, message: MessageSquare, shield: ShieldCheck, home: Home, building: Building2, file: FileText, wallet: Wallet, plus: Plus, inbox: Inbox, list: List, chart: BarChart3, coins: Coins, scroll: Scroll, users: Users, cog: Cog, pulse: Activity, flag: Flag };
const page = usePage();
const nav = computed(() => page.props.nav as { title: string; route: string; icon: string }[]);
const user = computed(() => page.props.auth.user as { name: string; role_label: string });
const brand = computed(() => page.props.brand as { name: string });
const simulation = computed(() => (page.props.mode as { simulation: boolean }).simulation);
const path = computed(() => new URL(page.url, 'http://x').pathname);
const open = ref(false);
const unread = computed(() => (page.props.unread as number) ?? 0);

function active(href: string): boolean {
    const p = new URL(href).pathname;
    return path.value === p || path.value.startsWith(p + '/');
}
const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="min-h-svh bg-background">
        <a href="#content" class="sr-only z-[200] rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 lg:block" aria-label="Sidebar">
            <div class="flex h-full flex-col bg-sidebar text-sidebar-foreground">
                <Link :href="route('dashboard')" class="flex items-center gap-2.5 px-5 py-5"><Logo :size="30" /><span class="text-[15px] font-semibold leading-tight tracking-tight text-white">{{ brand.name }}</span></Link>
                <nav aria-label="Main" class="flex-1 overflow-y-auto px-3 pb-4">
                    <ul class="space-y-0.5">
                        <li v-for="n in nav" :key="n.route">
                            <Link :href="route(n.route)" :aria-current="active(route(n.route)) ? 'page' : undefined" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors hover:bg-sidebar-accent hover:text-white" :class="active(route(n.route)) ? 'bg-sidebar-accent font-medium text-white' : ''">
                                <component :is="icons[n.icon] ?? Home" class="size-4 shrink-0" :class="active(route(n.route)) ? 'text-sidebar-primary' : 'opacity-70'" aria-hidden="true" />{{ n.title }}
                            </Link>
                        </li>
                    </ul>
                </nav>
                <div class="border-t border-sidebar-border p-3">
                    <Link :href="route('complaints.index')" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-sidebar-accent hover:text-white"><Flag class="size-4 opacity-70" aria-hidden="true" />Help &amp; complaints</Link>
                    <div class="mt-2 px-3 text-xs"><p class="truncate font-medium text-white">{{ user.name }}</p><p class="truncate text-sidebar-foreground/70">{{ user.role_label }}</p></div>
                    <button class="mt-2 flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-sidebar-accent hover:text-white" @click="logout"><LogOut class="size-4 opacity-70" aria-hidden="true" />Log out</button>
                </div>
            </div>
        </aside>

        <Sheet v-model:open="open">
            <SheetContent side="left" class="w-72 border-sidebar-border bg-sidebar p-0 [&>button]:text-white">
                <SheetTitle class="sr-only">Navigation</SheetTitle><SheetDescription class="sr-only">Main navigation</SheetDescription>
                <div class="flex h-full flex-col text-sidebar-foreground">
                    <div class="flex items-center gap-2.5 px-5 py-5"><Logo :size="28" /><span class="font-semibold text-white">{{ brand.name }}</span></div>
                    <ul class="flex-1 space-y-0.5 overflow-y-auto px-3">
                        <li v-for="n in nav" :key="n.route"><Link :href="route(n.route)" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-sidebar-accent hover:text-white" :class="active(route(n.route)) ? 'bg-sidebar-accent text-white' : ''" @click="open = false"><component :is="icons[n.icon] ?? Home" class="size-4" aria-hidden="true" />{{ n.title }}</Link></li>
                    </ul>
                    <button class="m-3 flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-sidebar-accent hover:text-white" @click="logout"><LogOut class="size-4" aria-hidden="true" />Log out</button>
                </div>
            </SheetContent>
        </Sheet>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b bg-background px-4 sm:px-6">
                <Button variant="ghost" size="icon" class="lg:hidden" aria-label="Open navigation" @click="open = true"><Menu aria-hidden="true" /></Button>
                <span class="text-sm font-medium lg:hidden">{{ brand.name }}</span>
                <div class="ml-auto flex items-center gap-3 text-xs text-muted-foreground">
                    <Link :href="route('notifications.index')" class="relative rounded-md p-1.5 hover:bg-muted" :aria-label="`Notifications${unread ? ', ' + unread + ' unread' : ''}`"><Bell class="size-4" aria-hidden="true" /><span v-if="unread" class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-primary-foreground">{{ unread > 9 ? '9+' : unread }}</span></Link>
                    <span v-if="simulation" class="inline-flex items-center gap-1 rounded-full bg-gold/15 px-2.5 py-1 font-medium text-foreground ring-1 ring-gold/40"><FlaskConical class="size-3.5" aria-hidden="true" /> Simulation mode: no real money moves</span>
                </div>
            </header>
            <main id="content" class="mx-auto w-full max-w-6xl px-4 py-7 sm:px-6 lg:px-8 lg:py-9" tabindex="-1"><slot /></main>
        </div>
        <Toasts />
    </div>
</template>
