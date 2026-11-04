<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextArea from '@/Components/TextArea.vue'
import Btn from '@/Components/Btn.vue'
import Status from '@/Components/Status.vue'
import { label } from '@/lib/format'

const props = defineProps<{ profile: Record<string, any>; contact: { name: string; email: string; phone: string }; verifications: { kind: string; status: string }[]; industries: string[]; regions: string[] }>()
const form = useForm({
  trading_name: props.profile.trading_name, region: props.profile.region ?? props.regions[0], district: props.profile.district, address: props.profile.address,
  employees: props.profile.employees, monthly_turnover: props.profile.monthly_turnover, description: props.profile.description,
})
</script>

<template>
  <BusinessLayout>
    <PageHead title="Business profile" sub="Investors see a summary of your business. Registration details and contacts stay private." />
    <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
      <Card title="Business details">
        <dl class="mb-6 grid gap-4 border-b border-line pb-6 text-sm sm:grid-cols-2">
          <div><dt class="text-muted">Registered name</dt><dd>{{ profile.legal_name }}</dd></div>
          <div><dt class="text-muted">Registration number</dt><dd class="font-mono">{{ profile.registration_number ?? '—' }}</dd></div>
          <div><dt class="text-muted">Business type</dt><dd>{{ profile.business_type ?? '—' }}</dd></div>
          <div><dt class="text-muted">Industry</dt><dd>{{ profile.industry }}</dd></div>
        </dl>
        <form class="space-y-4" @submit.prevent="form.put('/business/profile', { preserveScroll: true })">
          <TextInput v-model="form.trading_name" label="Trading name" optional :error="form.errors.trading_name" />
          <div class="grid gap-4 sm:grid-cols-2">
            <SelectInput v-model="form.region" label="Region" :options="regions" :error="form.errors.region" />
            <TextInput v-model="form.district" label="District" :error="form.errors.district" />
          </div>
          <TextInput v-model="form.address" label="Address" :error="form.errors.address" />
          <div class="grid gap-4 sm:grid-cols-2">
            <TextInput v-model="form.employees" label="Employees" inputmode="numeric" :error="form.errors.employees" />
            <TextInput v-model="form.monthly_turnover" label="Average monthly turnover (MK)" inputmode="numeric" :error="form.errors.monthly_turnover" />
          </div>
          <TextArea v-model="form.description" label="Description" :rows="3" :error="form.errors.description" />
          <Btn type="submit" :disabled="form.processing">Save changes</Btn>
        </form>
      </Card>
      <div class="space-y-5">
        <Card title="Verification">
          <p class="mb-3"><Status :s="profile.verification_status" /></p>
          <ul class="divide-y divide-line text-sm">
            <li v-for="v in verifications" :key="v.kind" class="flex justify-between py-2"><span>{{ label(v.kind) }}</span><Status :s="v.status" /></li>
          </ul>
        </Card>
        <Card title="Contact">
          <dl class="space-y-2 text-sm"><div><dt class="text-muted">Email</dt><dd>{{ contact.email }}</dd></div><div><dt class="text-muted">Mobile</dt><dd>{{ contact.phone }}</dd></div></dl>
        </Card>
      </div>
    </div>
  </BusinessLayout>
</template>
