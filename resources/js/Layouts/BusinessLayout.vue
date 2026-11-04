<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppShell, { type NavGroup } from './AppShell.vue'

const page = usePage()
const c = computed(() => page.props.counts)
const nav = computed<NavGroup[]>(() => [
  { items: [{ href: '/business', label: 'Overview', exact: true }, { href: '/business/profile', label: 'Business Profile' }] },
  { label: 'Financing', items: [
    { href: '/business/applications', label: 'My Applications' },
    { href: '/business/offers', label: 'Marketplace Offers' },
    { href: '/business/financing', label: 'Financing' },
    { href: '/business/repayments', label: 'Repayments' },
  ] },
  { label: 'Account', items: [
    { href: '/business/messages', label: 'Messages', badge: c.value.messages || undefined },
    { href: '/business/documents', label: 'Documents' },
    { href: '/business/notifications', label: 'Notifications', badge: c.value.notifications || undefined },
    { href: '/business/settings', label: 'Settings' },
    { href: '/business/help', label: 'Help and Support' },
  ] },
])
</script>

<template>
  <AppShell :nav="nav" :who="page.props.auth.user?.name ?? ''" sub="Business account" logout="/logout"><slot /></AppShell>
</template>
