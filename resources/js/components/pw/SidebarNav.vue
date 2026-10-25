<script setup lang="ts">
import Logo from '@/components/pw/Logo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, CalendarClock, ChartNoAxesCombined, FileSpreadsheet, Landmark, LayoutDashboard, PiggyBank, Settings, Target, Wallet } from 'lucide-vue-next';
import { computed, type Component } from 'vue';

interface Item {
    title: string;
    route: string;
    match: string; // path prefix that marks the item active
    icon: Component;
}

const items: Item[] = [
    { title: 'Dashboard', route: 'dashboard', match: '/dashboard', icon: LayoutDashboard },
    { title: 'Transactions', route: 'transactions.index', match: '/transactions', icon: ArrowLeftRight },
    { title: 'Budgets', route: 'budgets.index', match: '/budgets', icon: PiggyBank },
    { title: 'Bills', route: 'bills.index', match: '/bills', icon: CalendarClock },
    { title: 'Accounts', route: 'accounts.index', match: '/accounts', icon: Wallet },
    { title: 'Savings goals', route: 'goals.index', match: '/goals', icon: Target },
    { title: 'Reports', route: 'reports.index', match: '/reports', icon: ChartNoAxesCombined },
    { title: 'Import & export', route: 'data.index', match: '/data', icon: FileSpreadsheet },
    { title: 'Bank connections', route: 'bank-connections.index', match: '/bank-connections', icon: Landmark },
];

const emit = defineEmits<{ navigate: [] }>();
const page = usePage();
const path = computed(() => new URL(page.url, 'http://x').pathname);

const isActive = (item: Item) => path.value === item.match || path.value.startsWith(item.match + '/') || (item.match === '/data' && path.value.startsWith('/imports'));
</script>

<template>
    <div class="flex h-full flex-col border-r bg-sidebar text-sidebar-foreground">
        <Link :href="route('dashboard')" class="flex items-center gap-2.5 px-5 py-5" @click="emit('navigate')">
            <Logo :size="26" />
            <span class="text-base font-semibold tracking-tight text-foreground">Banyira MicroCredit</span>
        </Link>

        <nav aria-label="Main" class="flex-1 overflow-y-auto px-3 pb-4">
            <ul class="space-y-0.5">
                <li v-for="item in items" :key="item.route">
                    <Link
                        :href="route(item.route)"
                        :aria-current="isActive(item) ? 'page' : undefined"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        :class="isActive(item) ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground' : ''"
                        @click="emit('navigate')"
                    >
                        <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item) ? 'text-sidebar-primary' : 'opacity-70'" aria-hidden="true" />
                        {{ item.title }}
                    </Link>
                </li>
            </ul>
        </nav>

        <div class="border-t p-3">
            <Link
                :href="route('profile.edit')"
                class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                :class="path.startsWith('/settings') ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground' : ''"
                :aria-current="path.startsWith('/settings') ? 'page' : undefined"
                @click="emit('navigate')"
            >
                <Settings class="size-4 opacity-70" aria-hidden="true" />
                Settings
            </Link>
        </div>
    </div>
</template>
