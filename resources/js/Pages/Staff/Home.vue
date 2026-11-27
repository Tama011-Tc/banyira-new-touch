<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import EmptyState from '@/Components/EmptyState.vue'
import GroupRow from '@/Components/GroupRow.vue'
import Stat from '@/Components/Stat.vue'
import { dateTime, label } from '@/lib/format'
import { useCan, type Permission } from '@/lib/permissions'

const props = defineProps<{
  unpublished: number
  unread: { id: string; title: string; body: string; url: string | null; at: string }[]
  activity: { id: number; area: string; action: string; created_at: string }[]
}>()

const page = usePage<{ staffCounts?: Record<string, number> }>()
const can = useCan()
const firstName = computed(() => page.props.auth.user!.name.split(' ')[0])

/** Queues this employee can act on, with what is waiting in each. */
const waiting = computed(() => {
  const n = page.props.staffCounts ?? {}
  return [
    { perm: 'applications.assess', label: 'Applications in the queue', count: n.queue, href: '/internal/credit' },
    { perm: 'applications.approve', label: 'Credit approvals', count: n.approvals, href: '/internal/approvals' },
    { perm: 'customers.verify', label: 'Customers to verify', count: n.verifications, href: '/internal/verifications' },
    { perm: 'payments.reconcile', label: 'Payments to reconcile', count: n.reconcile, href: '/internal/finance/reconciliation' },
    { perm: 'finance.approve', label: 'Maker-checker requests', count: n.checker, href: '/internal/finance/approvals' },
    { perm: 'content.publish', label: 'Pages with unpublished changes', count: props.unpublished, href: '/internal/content' },
  ].filter((w) => can(w.perm as Permission))
})

const shortcuts = computed(() => ([
  { perm: 'executive.dashboard', label: 'Platform performance', href: '/internal/executive', icon: 'chart' },
  { perm: 'applications.assess', label: 'Application queue', href: '/internal/credit', icon: 'review' },
  { perm: 'finance.view', label: 'Finance overview', href: '/internal/finance', icon: 'bank' },
  { perm: 'marketing.reports', label: 'Growth', href: '/internal/sales', icon: 'trend' },
  { perm: 'content.manage', label: 'Website pages', href: '/internal/content', icon: 'globe' },
  { perm: 'products.manage', label: 'Financing products', href: '/internal/products', icon: 'package' },
  { perm: 'users.manage', label: 'Employees', href: '/internal/it/users', icon: 'users' },
  { perm: 'roles.manage', label: 'Roles and permissions', href: '/internal/it/roles', icon: 'key' },
  { perm: 'system.manage', label: 'System health', href: '/internal/it', icon: 'activity' },
  { perm: 'backups.manage', label: 'Backups', href: '/internal/it/backups', icon: 'database' },
  { perm: 'audit.view', label: 'Audit log', href: '/internal/audit', icon: 'log' },
] as { perm: Permission; label: string; href: string; icon: string }[]).filter((s) => can(s.perm)))
</script>

<template>
  <StaffLayout>
    <PageHead :title="`Hello, ${firstName}`" sub="What is waiting for you, and the places you work." />

    <section v-if="waiting.length" aria-label="Waiting for you" class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-3">
      <Link v-for="w in waiting" :key="w.label" :href="w.href" class="press block">
        <Stat :label="w.label" :value="w.count ?? 0" :warn="!!w.count" />
      </Link>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-2">
      <Card title="Go to" flush>
        <div class="divide-y divide-separator border-t border-separator [&>*]:px-6">
          <GroupRow v-for="s in shortcuts" :key="s.href" :label="s.label" :href="s.href" :icon="s.icon" value="" />
        </div>
      </Card>

      <div class="space-y-6">
        <Card title="Unread notifications">
          <EmptyState v-if="!unread.length" compact title="You are up to date" icon="bell" />
          <ul v-else class="divide-y divide-separator">
            <li v-for="n in unread" :key="n.id" class="py-2.5 first:pt-0 last:pb-0">
              <Link :href="n.url ?? '/internal/notifications'" class="block text-[14px] font-medium hover:underline">{{ n.title }}</Link>
              <p class="text-[13px] text-muted">{{ n.body }}</p>
            </li>
          </ul>
        </Card>

        <Card title="Your recent activity">
          <EmptyState v-if="!activity.length" compact title="Nothing recorded yet" icon="history" />
          <ul v-else class="divide-y divide-separator">
            <li v-for="a in activity" :key="a.id" class="flex items-baseline justify-between gap-4 py-2 text-[13px] first:pt-0 last:pb-0">
              <span>{{ a.action }}<span class="block text-muted">{{ label(a.area) }}</span></span>
              <span class="shrink-0 whitespace-nowrap text-muted">{{ dateTime(a.created_at) }}</span>
            </li>
          </ul>
        </Card>
      </div>
    </div>
  </StaffLayout>
</template>
