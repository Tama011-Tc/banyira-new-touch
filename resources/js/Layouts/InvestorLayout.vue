<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppShell, { type NavGroup } from './AppShell.vue'

const page = usePage()
const c = computed(() => page.props.counts)
const nav = computed<NavGroup[]>(() => [
  { items: [{ href: '/investor', label: 'Overview', exact: true }, { href: '/investor/marketplace', label: 'Marketplace' }] },
  { label: 'Portfolio', items: [
    { href: '/investor/investments', label: 'My Investments' },
    { href: '/investor/offers', label: 'My Offers' },
    { href: '/investor/agreements', label: 'Agreements' },
    { href: '/investor/repayments', label: 'Repayment History' },
    { href: '/investor/transactions', label: 'Transactions' },
  ] },
  { label: 'Account', items: [
    { href: '/investor/messages', label: 'Messages', badge: c.value.messages || undefined },
    { href: '/investor/documents', label: 'Documents' },
    { href: '/investor/notifications', label: 'Notifications', badge: c.value.notifications || undefined },
    { href: '/investor/profile', label: 'Profile and Verification' },
    { href: '/investor/settings', label: 'Settings' },
    { href: '/investor/help', label: 'Help and Support' },
  ] },
])
</script>

<template>
  <AppShell :nav="nav" :who="page.props.auth.user?.name ?? ''" sub="Investor account" logout="/logout"><slot /></AppShell>
</template>
