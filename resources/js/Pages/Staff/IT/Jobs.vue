<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Stat from '@/Components/Stat.vue'
import Status from '@/Components/Status.vue'
import Btn from '@/Components/Btn.vue'
import { dateTime } from '@/lib/format'

defineProps<{ queued: number; failed: any[]; sms: Record<string, number>; schedule: { task: string; when: string }[]; integrations: any[] }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Jobs and integrations" sub="Background work and connections to outside providers." />
    <div class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
      <Stat label="Jobs waiting" :value="String(queued)" />
      <Stat label="Failed jobs" :value="String(failed.length)" :warn="failed.length > 0" />
      <Stat label="SMS sent" :value="String(sms.sent ?? 0)" :note="`${sms.queued ?? 0} queued`" />
      <Stat label="SMS failed" :value="String(sms.failed ?? 0)" :warn="(sms.failed ?? 0) > 0" />
    </div>
    <div class="mt-8 grid gap-5 lg:grid-cols-2">
      <Card title="Integrations" flush>
        <ul class="divide-y divide-line">
          <li v-for="i in integrations" :key="i.name" class="px-5 py-3 text-sm">
            <div class="flex justify-between"><span>{{ i.name }} <span class="ml-2 font-mono text-[13px] text-muted">{{ i.driver }}</span></span><Status :s="i.configured ? 'active' : 'not configured'" /></div>
            <p class="mt-0.5 font-mono text-[11px] text-muted">{{ i.env }}</p>
          </li>
        </ul>
      </Card>
      <Card title="Scheduled tasks" flush>
        <ul class="divide-y divide-line"><li v-for="s in schedule" :key="s.task" class="flex justify-between px-5 py-3 text-sm"><span>{{ s.task }}</span><span class="text-muted">{{ s.when }}</span></li></ul>
        <p class="border-t border-line px-5 py-3 text-xs text-muted">Run <span class="font-mono">php artisan schedule:work</span> (development) or a cron calling <span class="font-mono">schedule:run</span> every minute.</p>
      </Card>
    </div>
    <Card class="mt-5" title="Failed jobs" flush>
      <template #action><Btn v-if="failed.length" size="sm" variant="outline" @click="router.post('/internal/it/jobs/retry', {}, { preserveScroll: true })">Retry all</Btn></template>
      <ul v-if="failed.length" class="divide-y divide-line"><li v-for="f in failed" :key="f.id" class="px-5 py-3 text-sm"><p class="font-mono text-[13px]">{{ f.error }}</p><p class="text-xs text-muted">{{ f.queue }} · {{ dateTime(f.at) }}</p></li></ul>
      <p v-else class="px-5 py-6 text-sm text-muted">No failed jobs.</p>
    </Card>
  </StaffLayout>
</template>
