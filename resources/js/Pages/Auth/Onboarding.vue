<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import WizardLayout from '@/Layouts/WizardLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import ChoiceCards from '@/Components/ChoiceCards.vue'
import Btn from '@/Components/Btn.vue'
import { filled, useWizard, type WizardStep } from '@/lib/wizard'

const props = defineProps<{ type: 'business' | 'investor'; profile: Record<string, any>; regions: string[] }>()
const biz = props.type === 'business'
const form = useForm<Record<string, any>>({
  ...props.profile,
  region: props.profile.region ?? null,
  national_id: '',
  payout_method: props.profile.payout_method ?? 'Mobile money',
  payout_account: '',
  source_of_funds: props.profile.source_of_funds ?? null,
})
const has = (...keys: string[]) => keys.every((k) => filled(form[k]))

const payoutMethods = [
  { value: 'Mobile money', label: 'Mobile money', description: 'Paid to your mobile wallet.' },
  { value: 'Bank account', label: 'Bank account', description: 'Paid to an account in your name.' },
]
const identity: WizardStep = { key: 'identity', title: biz ? 'Who owns the business?' : 'Confirm your identity', sub: 'Your ID number is stored encrypted and checked by our verification team.', fields: ['national_id'], done: () => has('national_id') }
const payout: WizardStep = { key: 'payout', title: biz ? 'Where should financing be paid?' : 'Where should repayments be paid?', fields: ['payout_method', 'payout_account'], done: () => has('payout_method', 'payout_account') }

const wizard = useWizard(biz ? [
  { key: 'registration', title: 'What is your registration number?', sub: 'It is on your business registration certificate.', fields: ['registration_number', 'trading_name'], done: () => has('registration_number') },
  { key: 'location', title: 'Where does the business operate?', fields: ['region', 'district', 'address'], done: () => has('region', 'district', 'address') },
  { key: 'size', title: 'How established is it?', fields: ['years_operating', 'employees'], done: () => has('years_operating', 'employees') },
  { key: 'turnover', title: 'What do you turn over in a typical month?', sub: 'An average is fine. We check it against your statements later.', fields: ['monthly_turnover'], done: () => has('monthly_turnover') },
  { key: 'description', title: 'What does the business do?', sub: 'A few sentences. Investors read this first.', fields: ['description'], done: () => has('description') },
  identity,
  payout,
] : [
  identity,
  { key: 'funds', title: 'Where does the money you will invest come from?', sub: 'We are required to ask every investor.', fields: ['source_of_funds'], done: () => has('source_of_funds') },
  payout,
], [56, 100])
const { step, dir, progress, index, count, first, last } = wizard

const next = () => {
  if (!step.value.done()) return
  if (!last.value) return wizard.next()
  form.post('/onboarding', { onError: wizard.showErrors })
}
</script>

<template>
  <WizardLayout title="Complete your profile" :step="step" :dir="dir" :progress="progress" :can-back="!first" :exit="{ href: `/${type}`, label: 'Finish later' }" @next="next" @back="wizard.back">
    <template v-if="step.key === 'registration'">
      <TextInput v-model="form.registration_number" label="Business registration number" :error="form.errors.registration_number" />
      <TextInput v-model="form.trading_name" label="Trading name" optional hint="Only if customers know you by a different name." :error="form.errors.trading_name" />
    </template>

    <template v-else-if="step.key === 'location'">
      <ChoiceCards v-model="form.region" label="Region" :options="regions" :error="form.errors.region" />
      <TextInput v-model="form.district" label="District" :error="form.errors.district" />
      <TextInput v-model="form.address" label="Business address" autocomplete="street-address" :error="form.errors.address" />
    </template>

    <div v-else-if="step.key === 'size'" class="grid grid-cols-2 gap-4">
      <TextInput v-model="form.years_operating" label="Years operating" inputmode="numeric" :error="form.errors.years_operating" />
      <TextInput v-model="form.employees" label="Employees" inputmode="numeric" :error="form.errors.employees" />
    </div>

    <TextInput v-else-if="step.key === 'turnover'" v-model="form.monthly_turnover" label="Average monthly turnover (MK)" inputmode="numeric" :error="form.errors.monthly_turnover" />
    <TextArea v-else-if="step.key === 'description'" v-model="form.description" label="Description" :rows="5" :error="form.errors.description" />
    <TextInput v-else-if="step.key === 'identity'" v-model="form.national_id" :label="biz ? 'Owner national ID number' : 'National ID number'" :error="form.errors.national_id" />
    <ChoiceCards v-else-if="step.key === 'funds'" v-model="form.source_of_funds" label="Source of funds" :options="['Salary', 'Business income', 'Savings', 'Pension', 'Other']" :error="form.errors.source_of_funds" />

    <template v-else>
      <ChoiceCards v-model="form.payout_method" label="Account type" :options="payoutMethods" :error="form.errors.payout_method" />
      <TextInput v-model="form.payout_account" :label="form.payout_method === 'Mobile money' ? 'Mobile money number' : 'Bank account number'" inputmode="numeric" :error="form.errors.payout_account" />
    </template>

    <template #aside>Question {{ index + 1 }} of {{ count }}</template>
    <template #footer>
      <Btn type="submit" size="lg" :disabled="!step.done() || form.processing">{{ form.processing ? 'Submitting…' : last ? 'Submit for verification' : 'Continue' }}</Btn>
    </template>
  </WizardLayout>
</template>
