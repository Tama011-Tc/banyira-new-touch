<script setup lang="ts">
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextArea from '@/Components/TextArea.vue'
import Btn from '@/Components/Btn.vue'
import Progress from '@/Components/Progress.vue'

const props = defineProps<{ type: 'business' | 'investor'; profile: Record<string, any>; regions: string[] }>()
const biz = props.type === 'business'
const steps = biz ? ['Business details', 'Owner identity', 'Payout account'] : ['Identity', 'Source of funds', 'Payout account']
const step = ref(0)
const form = useForm<Record<string, any>>({
  ...props.profile,
  region: props.profile.region ?? props.regions[1],
  national_id: '',
  payout_method: props.profile.payout_method ?? 'Mobile money',
  payout_account: '',
  source_of_funds: props.profile.source_of_funds ?? 'Salary',
})
const fieldsByStep = biz
  ? [['registration_number', 'trading_name', 'region', 'district', 'address', 'years_operating', 'employees', 'monthly_turnover', 'description'], ['national_id'], ['payout_method', 'payout_account']]
  : [['national_id'], ['source_of_funds'], ['payout_method', 'payout_account']]

const next = () => {
  if (step.value < 2) return void (step.value += 1)
  form.post('/onboarding', {
    onError: (errs) => { const i = fieldsByStep.findIndex((f) => f.some((k) => k in errs)); if (i >= 0) step.value = i },
  })
}
</script>

<template>
  <AuthLayout title="Complete your profile" :step="`Step 3 of 3 · ${steps[step]} (${step + 1}/3)`" :sub="biz ? 'We use this to verify your business and assess applications.' : 'Investors must be verified before making offers.'">
    <Progress :value="((step + 1) / 3) * 100" label="Profile progress" />
    <form class="mt-6 space-y-4" @submit.prevent="next">
      <template v-if="biz && step === 0">
        <TextInput v-model="form.registration_number" label="Business registration number" :error="form.errors.registration_number" />
        <TextInput v-model="form.trading_name" label="Trading name" optional :error="form.errors.trading_name" />
        <div class="grid gap-4 sm:grid-cols-2">
          <SelectInput v-model="form.region" label="Region" :options="regions" :error="form.errors.region" />
          <TextInput v-model="form.district" label="District" :error="form.errors.district" />
        </div>
        <TextInput v-model="form.address" label="Business address" autocomplete="street-address" :error="form.errors.address" />
        <div class="grid grid-cols-2 gap-4">
          <TextInput v-model="form.years_operating" label="Years operating" inputmode="numeric" :error="form.errors.years_operating" />
          <TextInput v-model="form.employees" label="Employees" inputmode="numeric" :error="form.errors.employees" />
        </div>
        <TextInput v-model="form.monthly_turnover" label="Average monthly turnover (MK)" inputmode="numeric" :error="form.errors.monthly_turnover" />
        <TextArea v-model="form.description" label="What does the business do?" :rows="3" :error="form.errors.description" />
      </template>
      <template v-if="(biz && step === 1) || (!biz && step === 0)">
        <TextInput v-model="form.national_id" :label="biz ? 'Owner national ID number' : 'National ID number'" :error="form.errors.national_id" hint="Stored encrypted. Checked by our verification team." />
      </template>
      <template v-if="!biz && step === 1">
        <SelectInput v-model="form.source_of_funds" label="Main source of the money you will invest" :options="['Salary', 'Business income', 'Savings', 'Pension', 'Other']" :error="form.errors.source_of_funds" />
      </template>
      <template v-if="step === 2">
        <SelectInput v-model="form.payout_method" label="Account type" :options="['Mobile money', 'Bank account']" :error="form.errors.payout_method" />
        <TextInput v-model="form.payout_account" :label="form.payout_method === 'Mobile money' ? 'Mobile money number' : 'Bank account number'" inputmode="numeric" :error="form.errors.payout_account" :hint="biz ? 'Financing is paid into this account.' : 'Repayments are paid into this account.'" />
      </template>
      <div class="flex gap-2 pt-2">
        <Btn v-if="step > 0" variant="outline" @click="step -= 1">Back</Btn>
        <Btn type="submit" class="flex-1" :disabled="form.processing">{{ step < 2 ? 'Continue' : 'Submit for verification' }}</Btn>
      </div>
    </form>
    <p class="mt-5 text-center text-[13px]"><Link :href="`/${type}`" class="link text-muted">Finish later</Link></p>
  </AuthLayout>
</template>
