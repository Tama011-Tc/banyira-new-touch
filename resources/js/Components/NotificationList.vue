<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import Btn from './Btn.vue'
import EmptyState from './EmptyState.vue'
import Pagination from './Pagination.vue'
import { dateTime } from '@/lib/format'
import type { Paginated } from '@/types'

export type NotificationItem = { id: string; title: string; body: string; url: string | null; at: string; read: boolean }

// Shared by the customer and staff notification pages. "action" is where
// "mark read" is sent.
const props = defineProps<{ items: Paginated<NotificationItem>; action: string }>()
const read = (id?: string) => router.post(props.action, { id }, { preserveScroll: true })
</script>

<template>
  <div>
    <div v-if="items.data.some((n) => !n.read)" class="mb-3 flex justify-end">
      <Btn variant="outline" size="sm" @click="read()">Mark all as read</Btn>
    </div>
    <EmptyState v-if="!items.data.length" title="You have no notifications" icon="bell" />
    <ul v-else class="divide-y divide-line rounded-lg border border-line bg-surface">
      <li v-for="n in items.data" :key="n.id" class="flex items-start gap-4 px-5 py-4">
        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full" :class="n.read ? 'bg-transparent' : 'bg-forest'" aria-hidden="true" />
        <div class="flex-1">
          <p class="text-sm" :class="!n.read && 'font-medium'"><span v-if="!n.read" class="sr-only">Unread: </span>{{ n.title }}</p>
          <p class="text-[13px] text-muted">{{ n.body }}</p>
          <p class="mt-1 text-xs text-muted">{{ dateTime(n.at) }}</p>
        </div>
        <div class="flex shrink-0 gap-3 text-[13px]">
          <Link v-if="n.url" :href="n.url" class="link" @click="!n.read && read(n.id)">Open</Link>
          <button v-if="!n.read" type="button" class="text-muted hover:text-ink" @click="read(n.id)">Mark read</button>
        </div>
      </li>
    </ul>
    <Pagination :page="items" />
  </div>
</template>
