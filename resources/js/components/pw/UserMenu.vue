<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut, Settings, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user as { name: string; email: string; is_demo: boolean });
const { getInitials } = useInitials();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger class="flex items-center gap-2 rounded-full focus-visible:ring-2" aria-label="Account menu">
            <Avatar class="size-9">
                <AvatarFallback class="bg-primary text-sm font-semibold text-primary-foreground">{{ getInitials(user.name) }}</AvatarFallback>
            </Avatar>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-60">
            <DropdownMenuLabel class="font-normal">
                <p class="truncate text-sm font-medium">{{ user.name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ user.is_demo ? 'Demo account' : user.email }}</p>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link :href="route('profile.edit')" class="flex w-full cursor-pointer items-center gap-2"><Settings class="size-4" aria-hidden="true" /> Settings</Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link :href="route('security.edit')" class="flex w-full cursor-pointer items-center gap-2"><ShieldCheck class="size-4" aria-hidden="true" /> Security & activity</Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link :href="route('logout')" method="post" as="button" class="flex w-full cursor-pointer items-center gap-2"><LogOut class="size-4" aria-hidden="true" /> Log out</Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
