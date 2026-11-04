<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'
import Status from '@/Components/Status.vue'
import { dateTime } from '@/lib/format'

const props = defineProps<{ area: 'business' | 'investor'; account: any; sessions: number }>()
const form = useForm({ current_password: '', password: '', password_confirmation: '' })
</script>

<template>
  <CustomerLayout :area="area">
    <PageHead title="Settings" />
    <div class="grid gap-5 lg:grid-cols-2">
      <Card title="Account">
        <dl class="space-y-3 text-sm">
          <div class="flex justify-between gap-4"><dt class="text-muted">Name</dt><dd>{{ account.name }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-muted">Email</dt><dd class="text-right">{{ account.email }}<br /><Status :s="account.email_verified_at ? 'verified' : 'not verified'" /></dd></div>
          <div class="flex justify-between gap-4"><dt class="text-muted">Mobile</dt><dd class="text-right">{{ account.phone }}<br /><Status :s="account.phone_verified_at ? 'verified' : 'not verified'" /></dd></div>
          <div class="flex justify-between gap-4"><dt class="text-muted">Last sign-in</dt><dd>{{ dateTime(account.last_login_at) }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-muted">Active sessions</dt><dd>{{ sessions }}</dd></div>
        </dl>
        <Btn v-if="!account.email_verified_at" class="mt-4" size="sm" variant="outline" @click="router.post('/email/verification-notification')">Resend email confirmation</Btn>
        <p class="mt-4 text-xs text-muted">To change your name, email or phone number, contact support so we can re-verify you.</p>
      </Card>
      <Card title="Change password">
        <form class="space-y-4" @submit.prevent="form.put(`/${props.area}/settings/password`, { preserveScroll: true, onSuccess: () => form.reset() })">
          <TextInput v-model="form.current_password" label="Current password" type="password" autocomplete="current-password" :error="form.errors.current_password" />
          <TextInput v-model="form.password" label="New password" type="password" autocomplete="new-password" hint="At least 8 characters, with letters and numbers." :error="form.errors.password" />
          <TextInput v-model="form.password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" />
          <Btn type="submit" :disabled="form.processing">Change password</Btn>
          <p class="text-xs text-muted">Changing your password signs you out on other devices.</p>
        </form>
      </Card>
    </div>
  </CustomerLayout>
</template>
