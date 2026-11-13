<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import WizardLayout from '@/Layouts/WizardLayout.vue'
import ChoiceCards from '@/Components/ChoiceCards.vue'
import Btn from '@/Components/Btn.vue'
import Icon from '@/Components/Icon.vue'

defineProps<{ mode: 'login' | 'register' }>()

const type = ref<string | null>(null)
const accounts = [
  { value: 'business', label: 'I run a business', description: 'Apply for financing and receive offers from investors.' },
  { value: 'investor', label: 'I want to invest', description: 'Review assessed businesses and make offers.' },
]
const logins: [string, string, string][] = [
  ['business', 'Business', 'Manage applications, offers and repayments.'],
  ['investor', 'Investor', 'Manage your offers, investments and repayments.'],
]
const step = { key: 'type', title: 'What brings you to Banyira?', sub: 'Choose the account that fits you. It cannot be changed later.' }
</script>

<template>
  <WizardLayout v-if="mode === 'register'" title="Create an account" :step="step" :progress="4" @next="type && router.visit(`/register/${type}`)">
    <ChoiceCards v-model="type" label="Account type" :options="accounts" />
    <template #aside>Already registered? <Link href="/login" class="link">Log in</Link></template>
    <template #footer><Btn type="submit" size="lg" :disabled="!type">Continue</Btn></template>
  </WizardLayout>

  <AuthLayout v-else title="Log in" sub="Which account do you use?" back="/">
    <div class="divide-y divide-line rounded-lg border border-line bg-surface">
      <Link v-for="[t, h, d] in logins" :key="t" :href="`/login/${t}`" class="flex items-center gap-4 px-4 py-3.5 transition-colors duration-150 hover:bg-fill-2">
        <span class="flex-1"><span class="block font-medium">{{ h }}</span><span class="text-[13px] text-muted">{{ d }}</span></span>
        <Icon n="chev" :size="16" class="text-label-3" />
      </Link>
    </div>
    <p class="mt-6 text-sm text-muted">New to Banyira? <Link href="/register" class="link">Create an account</Link></p>
  </AuthLayout>
</template>
