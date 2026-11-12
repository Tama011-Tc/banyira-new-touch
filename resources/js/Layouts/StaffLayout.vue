<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppShell, { type NavGroup, type NavItem } from './AppShell.vue'
import { useCan } from '@/lib/permissions'

// Navigation is built from the signed-in employee's permissions, so each role
// only sees its own workspace.
const page = usePage<{ staffCounts?: Record<string, number> }>()
const user = computed(() => page.props.auth.user!)
const can = useCan()
const n = computed(() => page.props.staffCounts ?? {})

const nav = computed<NavGroup[]>(() => {
  const g: NavGroup[] = []
  const add = (label: string, items: (NavItem | false)[]) => {
    const list = items.filter(Boolean) as NavItem[]
    if (list.length) g.push({ label, items: list })
  }
  add('Executive', [
    can('executive.dashboard') && { href: '/internal/executive', label: 'Platform performance', exact: true },
    can('applications.approve') && { href: '/internal/approvals', label: 'Approvals', badge: n.value.approvals || undefined },
    can('executive.dashboard') && { href: '/internal/executive/risk', label: 'Repayment and risk' },
    can('executive.dashboard') && { href: '/internal/executive/people', label: 'Employee performance' },
  ])
  add('Credit', [
    can('applications.assess') && { href: '/internal/credit', label: 'Application queue', exact: true, badge: n.value.queue || undefined },
    can('applications.assess') && { href: '/internal/credit/history', label: 'Assessment history' },
    can('customers.verify') && { href: '/internal/verifications', label: 'Customer verification', badge: n.value.verifications || undefined },
  ])
  add('Finance', [
    can('finance.view') && { href: '/internal/finance', label: 'Finance overview', exact: true },
    can('finance.record') && { href: '/internal/finance/funding', label: 'Funding and disbursement' },
    can('payments.reconcile') && { href: '/internal/finance/reconciliation', label: 'Reconciliation', badge: n.value.reconcile || undefined },
    can('finance.approve') && { href: '/internal/finance/approvals', label: 'Maker-checker queue', badge: n.value.checker || undefined },
    can('finance.view') && { href: '/internal/finance/ledger', label: 'Ledger' },
    can('finance.record') && { href: '/internal/finance/expenses', label: 'Expenses' },
    can('payroll.view') && { href: '/internal/finance/payroll', label: 'Payroll' },
    can('finance.view') && { href: '/internal/finance/reports', label: 'Statements and exports' },
  ])
  add('Sales and marketing', [
    can('marketing.reports') && { href: '/internal/sales', label: 'Growth', exact: true },
    can('leads.manage') && { href: '/internal/sales/leads', label: 'Leads and follow-ups' },
    can('campaigns.manage') && { href: '/internal/sales/campaigns', label: 'Campaigns' },
  ])
  add('Administration', [
    can('system.manage') && { href: '/internal/it', label: 'System health', exact: true },
    can('users.manage') && { href: '/internal/it/users', label: 'Employees' },
    can('roles.manage') && { href: '/internal/it/roles', label: 'Roles and permissions' },
    can('security.view') && { href: '/internal/it/security', label: 'Security and sign-ins' },
    can('audit.view') && { href: '/internal/audit', label: 'Audit log' },
    can('system.manage') && { href: '/internal/it/jobs', label: 'Jobs and integrations' },
  ])
  add('Configuration', [
    (can('rules.propose') || can('rules.approve')) && { href: '/internal/rules', label: 'Financing rules' },
    can('products.manage') && { href: '/internal/products', label: 'Products' },
  ])
  return g
})
</script>

<template>
  <AppShell secure :nav="nav" :who="user.name" :sub="user.role ?? 'Employee'" logout="/internal-access/logout"><slot /></AppShell>
</template>
