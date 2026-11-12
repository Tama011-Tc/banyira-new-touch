<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Pagination from '@/Components/Pagination.vue'
import Btn from '@/Components/Btn.vue'
import { dateTime } from '@/lib/format'

const props = defineProps<{ area: 'business' | 'investor'; items: any }>()
const read = (id?: string) => router.post(`/${props.area}/notifications/read`, { id }, { preserveScroll: true })
</script>

<template>
  <CustomerLayout :area="area">
    <PageHead title="Notifications" sub="Important updates are also sent by SMS and email.">
      <Btn v-if="items.data.some((n: any) => !n.read)" variant="outline" size="sm" @click="read()">Mark all as read</Btn>
    </PageHead>
    <div v-if="!items.data.length" class="rounded-lg border border-dashed border-line-2 px-6 py-12 text-center text-sm text-muted">You have no notifications.</div>
    <ul v-else class="divide-y divide-line rounded-lg border border-line bg-surface">
      <li v-for="n in items.data" :key="n.id" class="flex items-start gap-4 px-5 py-4">
        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full" :class="n.read ? 'bg-transparent' : 'bg-forest'" aria-hidden="true" />
        <div class="flex-1">
          <p class="text-sm" :class="!n.read && 'font-medium'">{{ n.title }}</p>
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
  </CustomerLayout>
</template>
