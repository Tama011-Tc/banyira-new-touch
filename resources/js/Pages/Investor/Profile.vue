<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Status from '@/Components/Status.vue'
import { date, label } from '@/lib/format'

defineProps<{ profile: any; contact: { name: string; email: string; phone: string }; verifications: { kind: string; status: string; reviewed_at: string | null }[] }>()
</script>

<template>
  <InvestorLayout>
    <PageHead title="Profile and verification" />
    <div class="grid gap-5 lg:grid-cols-2">
      <Card title="Verification">
        <p class="mb-3"><Status :s="profile.verification_status" /></p>
        <ul class="divide-y divide-line text-sm">
          <li v-for="v in verifications" :key="v.kind" class="flex justify-between py-2"><span>{{ label(v.kind) }}</span><span class="flex items-center gap-3"><span class="text-xs text-muted">{{ date(v.reviewed_at) }}</span><Status :s="v.status" /></span></li>
        </ul>
        <p v-if="profile.verification_status === 'unverified'" class="mt-4 text-sm"><Link href="/onboarding" class="link">Complete verification</Link></p>
      </Card>
      <Card title="Details">
        <dl class="space-y-2.5 text-sm">
          <div class="flex justify-between"><dt class="text-muted">Name</dt><dd>{{ contact.name }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Email</dt><dd>{{ contact.email }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Mobile</dt><dd>{{ contact.phone }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Investor type</dt><dd>{{ profile.investor_type }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Source of funds</dt><dd>{{ profile.source_of_funds ?? '—' }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Payout method</dt><dd>{{ profile.payout_method ?? '—' }}</dd></div>
          <div class="flex justify-between"><dt class="text-muted">Risk acknowledged</dt><dd>{{ date(profile.risk_acknowledged_at) }}</dd></div>
        </dl>
      </Card>
    </div>
  </InvestorLayout>
</template>
