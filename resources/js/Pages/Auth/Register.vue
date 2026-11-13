<script setup lang="ts">
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import WizardLayout from '@/Layouts/WizardLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import ChoiceCards from '@/Components/ChoiceCards.vue'
import Checkbox from '@/Components/Checkbox.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'
import { filled, useWizard, type WizardStep } from '@/lib/wizard'

const props = defineProps<{ type: 'business' | 'investor'; industries: string[] }>()
const biz = props.type === 'business'
const form = useForm({
  name: '', email: '', phone: '', password: '', password_confirmation: '', terms: false,
  industry: null as string | null, business_type: null as string | null, investor_type: null as string | null, risk: false,
})

const businessTypes = [
  { value: 'Sole trader', label: 'Sole trader', description: 'One owner, trading in their own name.' },
  { value: 'Partnership', label: 'Partnership', description: 'Two or more owners sharing the business.' },
  { value: 'Private limited company', label: 'Private limited company', description: 'A registered company with shareholders.' },
  { value: 'Cooperative', label: 'Cooperative', description: 'Owned and run by its members.' },
]
const investorTypes = [
  { value: 'Individual', label: 'Individual', description: 'Investing your own money.' },
  { value: 'Joint account', label: 'Joint account', description: 'Investing together with another person.' },
  { value: 'Family trust', label: 'Family trust', description: 'Investing on behalf of a family trust.' },
]

const mismatch = computed(() => filled(form.password_confirmation) && form.password !== form.password_confirmation)
const contact: WizardStep = { key: 'contact', title: 'How can we reach you?', sub: 'We send a verification code to your mobile number.', fields: ['email', 'phone'], done: () => filled(form.email) && filled(form.phone) }
const password: WizardStep = { key: 'password', title: 'Create a password', sub: 'At least 8 characters, with letters and numbers.', fields: ['password'], done: () => form.password.length >= 8 && form.password === form.password_confirmation }

const wizard = useWizard(biz ? [
  { key: 'name', title: 'What is your business called?', sub: 'Use the name on your registration certificate.', fields: ['name'], done: () => filled(form.name) },
  { key: 'business_type', title: 'What kind of business is it?', fields: ['business_type'], done: () => filled(form.business_type) },
  { key: 'industry', title: 'Which industry are you in?', fields: ['industry'], done: () => filled(form.industry) },
  contact,
  password,
  { key: 'terms', title: 'One last thing', sub: 'Please read and accept our terms.', fields: ['terms'], done: () => form.terms },
] : [
  { key: 'name', title: 'What is your name?', sub: 'Use your full name, as it appears on your national ID.', fields: ['name'], done: () => filled(form.name) },
  { key: 'investor_type', title: 'How will you invest?', fields: ['investor_type'], done: () => filled(form.investor_type) },
  contact,
  password,
  { key: 'terms', title: 'Understand the risk', fields: ['risk', 'terms'], done: () => form.risk && form.terms },
], [8, 46])
const { step, dir, progress, index, count, first, last } = wizard

const next = () => {
  if (!step.value.done()) return
  if (!last.value) return wizard.next()
  form.post(`/register/${props.type}`, { onError: wizard.showErrors, onFinish: () => form.reset('password', 'password_confirmation') })
}
const back = () => (first.value ? router.visit('/register') : wizard.back())
</script>

<template>
  <WizardLayout :title="biz ? 'Register your business' : 'Open an investor account'" :step="step" :dir="dir" :progress="progress" can-back @next="next" @back="back">
    <TextInput v-if="step.key === 'name'" v-model="form.name" :label="biz ? 'Registered business name' : 'Full name'" :autocomplete="biz ? 'organization' : 'name'" :error="form.errors.name" />

    <ChoiceCards v-else-if="step.key === 'business_type'" v-model="form.business_type" label="Business type" :options="businessTypes" :error="form.errors.business_type" />
    <ChoiceCards v-else-if="step.key === 'industry'" v-model="form.industry" label="Industry" :options="industries" :columns="2" :error="form.errors.industry" />
    <ChoiceCards v-else-if="step.key === 'investor_type'" v-model="form.investor_type" label="Investor type" :options="investorTypes" :error="form.errors.investor_type" />

    <template v-else-if="step.key === 'contact'">
      <TextInput v-model="form.email" label="Email address" type="email" autocomplete="email" :error="form.errors.email" />
      <TextInput v-model="form.phone" label="Mobile number" type="tel" inputmode="tel" autocomplete="tel" placeholder="0999 123 456" :error="form.errors.phone" />
    </template>

    <template v-else-if="step.key === 'password'">
      <PasswordInput v-model="form.password" label="Password" autocomplete="new-password" :error="form.errors.password" />
      <PasswordInput v-model="form.password_confirmation" label="Type it again" autocomplete="new-password" :error="mismatch ? 'The two passwords do not match yet.' : undefined" />
    </template>

    <template v-else>
      <template v-if="!biz">
        <Notice tone="risk">Investing involves financial risk. You may experience delayed repayments or lose some or all of your capital. Ratings do not guarantee repayment.</Notice>
        <Checkbox v-model="form.risk" :error="form.errors.risk">I understand these risks and will make my own investment decisions.</Checkbox>
      </template>
      <Checkbox v-model="form.terms" :error="form.errors.terms">I accept the <Link href="/legal#terms" class="link">terms of use</Link> and <Link href="/legal#privacy" class="link">privacy notice</Link>.</Checkbox>
    </template>

    <template #aside>Question {{ index + 1 }} of {{ count }}</template>
    <template #footer>
      <Btn type="submit" size="lg" :disabled="!step.done() || form.processing">{{ form.processing ? 'Creating account…' : last ? 'Create account' : 'Continue' }}</Btn>
    </template>
  </WizardLayout>
</template>
