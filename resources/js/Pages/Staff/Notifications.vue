<script setup lang="ts">
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import NotificationList, { type NotificationItem } from '@/Components/NotificationList.vue'
import type { Paginated } from '@/types'

defineProps<{ items: Paginated<NotificationItem> }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Notifications" sub="Work waiting for you and changes to your account." />
    <NotificationList :items="items" action="/internal/notifications/read" />
  </StaffLayout>
</template>
