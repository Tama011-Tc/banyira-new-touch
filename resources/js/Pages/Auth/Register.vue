<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'

const props = defineProps<{ type: 'business' | 'investor'; industries: string[] }>()
const biz = props.type === 'business'
const form = useForm({
  name: '', email: '', phone: '', password: '', password_confirmation: '', terms: false,
  industry: biz ? props.industries[0] : null, business_type: biz ? 'Private limited company' : null,
  investor_type: biz ? null : 'Individual', risk: false,
})
</script>

<template>
  <AuthLayout :title="biz ? 'Register your business' : 'Open an investor account'" step="Step 1 of 3 · Account" back="/register">
    <form class="space-y-4" novalidate @submit.prevent="form.post(`/register/${type}`, { onFinish: () => form.reset('password', 'password_confirmation') })">
      <TextInput v-model="form.name" :label="biz ? 'Registered business name' : 'Full name'" :autocomplete="biz ? 'organization' : 'name'" :error="form.errors.name" />
      <template v-if="biz">
        <SelectInput v-model="form.business_type" label="Business type" :options="['Sole trader', 'Partnership', 'Private limited company', 'Cooperative']" :error="form.errors.business_type" />
        <SelectInput v-model="form.industry" label="Industry" :options="industries" :error="form.errors.industry" />
      </template>
      <SelectInput v-else v-model="form.investor_type" label="Investor type" :options="['Individual', 'Joint account', 'Family trust']" :error="form.errors.investor_type" />
      <TextInput v-model="form.email" label="Email address" type="email" autocomplete="email" :error="form.errors.email" />
      <TextInput v-model="form.phone" label="Mobile number" type="tel" inputmode="tel" autocomplete="tel" hint="We send a verification code to this number." :error="form.errors.phone" />
      <TextInput v-model="form.password" label="Password" type="password" autocomplete="new-password" hint="At least 8 characters, with letters and numbers." :error="form.errors.password" />
      <TextInput v-model="form.password_confirmation" label="Confirm password" type="password" autocomplete="new-password" />

      <template v-if="!biz">
        <Notice tone="risk">Investing involves financial risk. You may experience delayed repayments or lose some or all of your capital. Ratings do not guarantee repayment.</Notice>
        <label class="flex items-start gap-3 text-sm"><input v-model="form.risk" type="checkbox" class="mt-0.5 h-4 w-4" />I understand these risks and will make my own investment decisions.</label>
        <p v-if="form.errors.risk" class="text-[13px] text-danger">{{ form.errors.risk }}</p>
      </template>
      <label class="flex items-start gap-3 text-sm"><input v-model="form.terms" type="checkbox" class="mt-0.5 h-4 w-4" />I accept the <Link href="/legal#terms" class="link">terms of use</Link> and <Link href="/legal#privacy" class="link">privacy notice</Link>.</label>
      <p v-if="form.errors.terms" class="text-[13px] text-danger">{{ form.errors.terms }}</p>

      <Btn type="submit" full :disabled="form.processing">{{ form.processing ? 'Creating account…' : 'Continue' }}</Btn>
    </form>
  </AuthLayout>
</template>
