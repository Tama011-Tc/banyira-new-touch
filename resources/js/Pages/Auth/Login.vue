<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import Btn from '@/Components/Btn.vue'

const props = defineProps<{ type: 'business' | 'investor' }>()
const form = useForm({ email: '', password: '', remember: false })
</script>

<template>
  <AuthLayout :title="type === 'business' ? 'Business login' : 'Investor login'" back="/login">
    <form class="space-y-4" @submit.prevent="form.post(`/login/${type}`, { onFinish: () => form.reset('password') })">
      <TextInput v-model="form.email" label="Email address" type="email" autocomplete="email" autofocus :error="form.errors.email" />
      <PasswordInput v-model="form.password" label="Password" autocomplete="current-password" :error="form.errors.password" />
      <div class="flex items-center justify-between text-[13px]">
        <label class="flex items-center gap-2"><input v-model="form.remember" type="checkbox" class="h-4 w-4" /> Keep me signed in</label>
        <Link href="/forgot-password" class="link text-muted">Forgot password?</Link>
      </div>
      <Btn type="submit" size="lg" full :disabled="form.processing">{{ form.processing ? 'Signing in…' : 'Log in' }}</Btn>
    </form>
    <p class="mt-6 text-sm text-muted">No account? <Link :href="`/register/${props.type}`" class="link">Register as {{ type === 'business' ? 'a business' : 'an investor' }}</Link></p>
  </AuthLayout>
</template>
