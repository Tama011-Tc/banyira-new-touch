<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'

const form = useForm({ email: '' })
</script>

<template>
  <AuthLayout title="Reset your password" sub="Enter your email and we will send you a reset link." back="/login">
    <form class="space-y-4" @submit.prevent="form.post('/forgot-password')">
      <TextInput v-model="form.email" label="Email address" type="email" autocomplete="email" :error="form.errors.email" />
      <Btn type="submit" full :disabled="form.processing">Send reset link</Btn>
    </form>
  </AuthLayout>
</template>
