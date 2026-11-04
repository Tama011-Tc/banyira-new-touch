<script setup lang="ts">
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Stat from '@/Components/Stat.vue'
import Card from '@/Components/Card.vue'
import Status from '@/Components/Status.vue'
import Notice from '@/Components/Notice.vue'

defineProps<{ checks: { name: string; status: string; note: string }[]; stats: Record<string, number>; env: Record<string, any> }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="System health" :sub="`PHP ${env.php} · Laravel ${env.laravel} · ${env.env}`" />
    <Notice v-if="env.debug && env.env === 'production'" tone="error">Debug mode is on in production. Turn APP_DEBUG off.</Notice>
    <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
      <Stat label="User accounts" :value="String(stats.users)" />
      <Stat label="Active sessions (30 min)" :value="String(stats.sessions)" />
      <Stat label="Failed sign-ins (24 h)" :value="String(stats.failedLogins)" :warn="stats.failedLogins > 20" />
      <Stat label="Flagged sign-ins (7 days)" :value="String(stats.flagged)" :warn="stats.flagged > 0" />
    </div>
    <Card class="mt-8" title="Services" flush>
      <ul class="divide-y divide-line">
        <li v-for="c in checks" :key="c.name" class="flex items-center justify-between gap-4 px-5 py-3 text-sm"><span>{{ c.name }}</span><span class="flex items-center gap-4"><span class="text-[13px] text-muted">{{ c.note }}</span><Status :s="c.status" /></span></li>
      </ul>
    </Card>
  </StaffLayout>
</template>
