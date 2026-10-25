<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { NotificationItem } from '@/types/banyira';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const data = computed(() => page.props.notifications as { unread: number; latest: NotificationItem[] } | null);

const open = (n: NotificationItem) => router.post(route('notifications.read', n.id));
const readAll = () => router.post(route('notifications.read-all'), {}, { preserveScroll: true });
</script>

<template>
    <DropdownMenu v-if="data">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative" :aria-label="`Notifications${data.unread ? `, ${data.unread} unread` : ''}`">
                <Bell aria-hidden="true" />
                <span v-if="data.unread" class="absolute right-1.5 top-1.5 flex size-4 items-center justify-center rounded-full bg-expense text-[10px] font-bold text-white" aria-hidden="true">
                    {{ data.unread > 9 ? '9+' : data.unread }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <p class="text-sm font-semibold">Notifications</p>
                <button v-if="data.unread" type="button" class="text-xs font-medium text-primary hover:underline" @click="readAll">Mark all read</button>
            </div>
            <ul v-if="data.latest.length" class="max-h-96 divide-y overflow-y-auto">
                <li v-for="n in data.latest" :key="n.id">
                    <button type="button" class="flex w-full gap-3 px-4 py-3 text-left hover:bg-accent" @click="open(n)">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="n.read ? 'bg-transparent' : 'bg-primary'" aria-hidden="true" />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium" :class="{ 'text-muted-foreground': n.read }">{{ n.title }}</span>
                            <span class="block text-xs text-muted-foreground">{{ n.body }}</span>
                            <span class="mt-1 block text-[11px] text-muted-foreground">{{ n.at }}</span>
                        </span>
                    </button>
                </li>
            </ul>
            <p v-else class="px-4 py-8 text-center text-sm text-muted-foreground">You're all caught up.</p>
            <div class="border-t px-4 py-2.5 text-center">
                <Link :href="route('notifications.index')" class="text-xs font-medium text-primary hover:underline">View all</Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
