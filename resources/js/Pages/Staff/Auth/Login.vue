<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import StaffAuthLayout from '@/Layouts/StaffAuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'

const form = useForm({ email: '', password: '' })
</script>

<template>
  <StaffAuthLayout title="Employee sign-in">
    <form class="space-y-4" @submit.prevent="form.post('/internal-access', { onFinish: () => form.reset('password') })">
      <TextInput v-model="form.email" label="Work email" type="email" autocomplete="username" autofocus :error="form.errors.email" />
      <TextInput v-model="form.password" label="Password" type="password" autocomplete="current-password" :error="form.errors.password" />
      <Btn type="submit" variant="dark" full :disabled="form.processing">Continue</Btn>
    </form>
  </StaffAuthLayout>
</template>
