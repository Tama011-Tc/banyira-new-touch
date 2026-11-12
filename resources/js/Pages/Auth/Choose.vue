<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Icon from '@/Components/Icon.vue'

const props = defineProps<{ mode: 'login' | 'register' }>()
const login = props.mode === 'login'
const options = [
  ['business', 'Business', login ? 'Manage applications, offers and repayments.' : 'Apply for financing and receive offers from investors.'],
  ['investor', 'Investor', login ? 'Manage your offers, investments and repayments.' : 'Review assessed businesses and make offers.'],
] as const
</script>

<template>
  <AuthLayout :title="login ? 'Log in' : 'Create an account'" :sub="login ? 'Which account do you use?' : 'Choose the account that fits you. It cannot be changed later.'" back="/">
    <div class="divide-y divide-line rounded-lg border border-line bg-surface">
      <Link v-for="[t, h, d] in options" :key="t" :href="`/${mode}/${t}`" class="flex items-center gap-4 px-4 py-4 hover:bg-fill-2">
        <span class="flex-1"><span class="block font-medium">{{ h }}</span><span class="text-[13px] text-muted">{{ d }}</span></span>
        <Icon n="chev" class="text-muted" />
      </Link>
    </div>
    <p class="mt-6 text-sm text-muted">
      <template v-if="login">New to Banyira? <Link href="/register" class="link text-ink">Create an account</Link></template>
      <template v-else>Already registered? <Link href="/login" class="link text-ink">Log in</Link></template>
    </p>
  </AuthLayout>
</template>
