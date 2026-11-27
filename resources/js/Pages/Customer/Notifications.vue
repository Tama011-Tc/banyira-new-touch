<script setup lang="ts">
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import NotificationList, { type NotificationItem } from '@/Components/NotificationList.vue'
import type { Paginated } from '@/types'

defineProps<{ area: 'business' | 'investor'; items: Paginated<NotificationItem> }>()
</script>

<template>
  <CustomerLayout :area="area">
    <PageHead title="Notifications" sub="Important updates are also sent by SMS and email." />
    <NotificationList :items="items" :action="`/${area}/notifications/read`" />
  </CustomerLayout>
</template>
