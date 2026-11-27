<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import FormGroup from '@/Components/FormGroup.vue'
import GroupRow from '@/Components/GroupRow.vue'
import TextInput from '@/Components/TextInput.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import Btn from '@/Components/Btn.vue'
import { dateTime } from '@/lib/format'

const props = defineProps<{
  account: { name: string; email: string; phone: string | null; last_login_at: string | null; password_changed_at: string | null; role: string | null; title: string | null; department: string | null; staffNo: string | null }
}>()

const profile = useForm({ name: props.account.name, phone: props.account.phone ?? '' })
const password = useForm({ current_password: '', password: '', password_confirmation: '' })
const changePassword = () => password.put('/internal/profile/password', { preserveScroll: true, onSuccess: () => password.reset() })
</script>

<template>
  <StaffLayout>
    <PageHead title="My profile" sub="Your details and password. An administrator manages your work email, role and job details." />
    <div class="grid max-w-2xl gap-6">
      <form @submit.prevent="profile.put('/internal/profile', { preserveScroll: true })">
        <FormGroup title="Your details" padded>
          <TextInput v-model="profile.name" label="Full name" autocomplete="name" :error="profile.errors.name" />
          <TextInput v-model="profile.phone" label="Phone" type="tel" autocomplete="tel" optional :error="profile.errors.phone" />
          <Btn type="submit" :disabled="profile.processing || !profile.isDirty">Save details</Btn>
        </FormGroup>
      </form>

      <FormGroup title="Employment">
        <GroupRow label="Work email" :value="account.email" />
        <GroupRow label="Role" :value="account.role" />
        <GroupRow label="Job title" :value="account.title" />
        <GroupRow label="Department" :value="account.department" />
        <GroupRow label="Staff number" :value="account.staffNo" mono />
        <GroupRow label="Last sign-in" :value="dateTime(account.last_login_at)" />
        <GroupRow label="Password last changed" :value="dateTime(account.password_changed_at)" />
      </FormGroup>

      <form @submit.prevent="changePassword">
        <FormGroup title="Password" footer="At least 12 characters with upper and lower case, a number and a symbol. Changing it signs out your other sessions." padded>
          <PasswordInput v-model="password.current_password" label="Current password" autocomplete="current-password" :error="password.errors.current_password" />
          <PasswordInput v-model="password.password" label="New password" autocomplete="new-password" :error="password.errors.password" />
          <PasswordInput v-model="password.password_confirmation" label="Repeat the new password" autocomplete="new-password" />
          <Btn type="submit" :disabled="password.processing || !password.password">Change password</Btn>
        </FormGroup>
      </form>
    </div>
  </StaffLayout>
</template>
