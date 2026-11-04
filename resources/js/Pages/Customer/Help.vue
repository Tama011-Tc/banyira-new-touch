<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextArea from '@/Components/TextArea.vue'
import Btn from '@/Components/Btn.vue'

const props = defineProps<{ area: 'business' | 'investor' }>()
const faqs: [string, string][] = props.area === 'business' ? [
  ['How do I accept an offer?', 'Open Marketplace Offers, compare the offers, then choose Accept. You confirm before anything is final.'],
  ['Banyira asked for more information. What do I do?', 'Open the application, upload any documents in step 4, then reply to the request. Your assessment continues once you respond.'],
  ['When are repayments due?', 'Your combined schedule is under Repayments. We send an SMS reminder three days before each date.'],
  ['Can I repay early?', 'Yes. Make a payment for more than the instalment; the extra is applied to your oldest instalments first.'],
] : [
  ['How is my money protected?', 'Agreements are recorded by Banyira and repayments are tracked, but investing carries risk: you can lose some or all of your capital.'],
  ['What does a risk rating mean?', 'It is Banyira\'s assessment of a business when it applied. It is not a guarantee of repayment.'],
  ['Can I withdraw an offer?', 'Yes, while it is still pending, from My Offers.'],
  ['When do I send my money?', 'Only after your offer is accepted and every party has signed the agreement. Instructions appear under Agreements.'],
]
const form = useForm({ topic: 'Account', message: '' })
</script>

<template>
  <CustomerLayout :area="area">
    <PageHead title="Help and support" />
    <div class="grid gap-8 lg:grid-cols-[1.4fr_1fr]">
      <div class="divide-y divide-line border-y border-line">
        <details v-for="[q, a] in faqs" :key="q" class="group py-1">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-3 text-[15px]">{{ q }}<span class="font-mono text-muted group-open:hidden">+</span><span class="hidden font-mono text-muted group-open:inline">−</span></summary>
          <p class="pb-4 pr-10 text-muted">{{ a }}</p>
        </details>
      </div>
      <Card title="Contact support" sub="Monday to Friday, 08:00–17:00. We reply within one working day.">
        <form class="space-y-4" @submit.prevent="form.post(`/${area}/help`, { preserveScroll: true, onSuccess: () => form.reset('message') })">
          <SelectInput v-model="form.topic" label="Topic" :options="['Account', 'Application', 'Offers', 'Payments', 'Complaint', 'Other']" />
          <TextArea v-model="form.message" label="How can we help?" :rows="4" :error="form.errors.message" />
          <Btn type="submit" :disabled="form.processing">Send</Btn>
        </form>
      </Card>
    </div>
  </CustomerLayout>
</template>
