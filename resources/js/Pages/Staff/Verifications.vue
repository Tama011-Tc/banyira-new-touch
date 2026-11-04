<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Pagination from '@/Components/Pagination.vue'
import Status from '@/Components/Status.vue'
import Btn from '@/Components/Btn.vue'
import { date, label } from '@/lib/format'

defineProps<{ users: any }>()
const decide = (id: number, status: 'verified' | 'rejected') => router.post(`/internal/verifications/${id}`, { status }, { preserveScroll: true })
</script>

<template>
  <StaffLayout>
    <PageHead title="Customer verification" sub="Check each item against the documents and external registries, then record the outcome." />
    <div v-if="!users.data.length" class="rounded-lg border border-dashed border-line-2 px-6 py-12 text-center text-sm text-muted">No customers waiting for verification.</div>
    <div class="space-y-4">
      <Card v-for="u in users.data" :key="u.id">
        <div class="flex flex-wrap justify-between gap-4">
          <div>
            <p class="font-medium">{{ u.name }} <span class="ml-2 text-[13px] font-normal text-muted">{{ label(u.type) }}</span></p>
            <p class="text-[13px] text-muted">{{ u.email }} · {{ u.phone }} · joined {{ date(u.created) }}</p>
            <p v-if="u.detail" class="mt-1 text-[13px]"><span v-for="(v, k) in u.detail" :key="k" class="mr-4"><span class="text-muted">{{ label(String(k)) }}:</span> {{ v ?? '—' }}</span></p>
          </div>
        </div>
        <ul class="mt-4 divide-y divide-line border-t border-line">
          <li v-for="c in u.checks.filter((x: any) => !['email', 'phone'].includes(x.kind))" :key="c.id" class="flex items-center justify-between gap-3 py-2.5 text-sm">
            <span>{{ label(c.kind) }}</span>
            <span class="flex items-center gap-3">
              <Status :s="c.status" />
              <template v-if="c.status === 'pending'"><Btn size="sm" variant="outline" @click="decide(c.id, 'verified')">Verify</Btn><Btn size="sm" variant="ghost" @click="decide(c.id, 'rejected')">Reject</Btn></template>
            </span>
          </li>
        </ul>
      </Card>
    </div>
    <Pagination :page="users" />
  </StaffLayout>
</template>
