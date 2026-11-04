<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'

const props = defineProps<{ token: string; email: string | null }>()
const form = useForm({ token: props.token, email: props.email ?? '', password: '', password_confirmation: '' })
</script>

<template>
  <AuthLayout title="Choose a new password">
    <form class="space-y-4" @submit.prevent="form.post('/reset-password')">
      <TextInput v-model="form.email" label="Email address" type="email" autocomplete="email" :error="form.errors.email" />
      <TextInput v-model="form.password" label="New password" type="password" autocomplete="new-password" hint="At least 8 characters, with letters and numbers." :error="form.errors.password" />
      <TextInput v-model="form.password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" />
      <Btn type="submit" full :disabled="form.processing">Set new password</Btn>
    </form>
  </AuthLayout>
</template>
