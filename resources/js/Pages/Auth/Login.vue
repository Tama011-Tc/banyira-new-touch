<script setup lang="ts">
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import Field from '@/Components/Field.vue'
import Btn from '@/Components/Btn.vue'

const props = defineProps<{ type: 'business' | 'investor' }>()
const form = useForm({ email: '', password: '', remember: false })
const show = ref(false)
</script>

<template>
  <AuthLayout :title="type === 'business' ? 'Business login' : 'Investor login'" back="/login">
    <form class="space-y-4" @submit.prevent="form.post(`/login/${type}`, { onFinish: () => form.reset('password') })">
      <TextInput v-model="form.email" label="Email address" type="email" autocomplete="email" autofocus :error="form.errors.email" />
      <Field label="Password" :error="form.errors.password" v-slot="{ id, invalid }">
        <div class="relative">
          <input :id="id" v-model="form.password" :type="show ? 'text' : 'password'" autocomplete="current-password" class="input pr-16" :aria-invalid="invalid" />
          <button type="button" class="absolute right-1 top-1 h-8 px-3 text-[13px] text-muted hover:text-ink" @click="show = !show">{{ show ? 'Hide' : 'Show' }}</button>
        </div>
      </Field>
      <div class="flex items-center justify-between text-[13px]">
        <label class="flex items-center gap-2"><input v-model="form.remember" type="checkbox" class="h-4 w-4" /> Keep me signed in</label>
        <Link href="/forgot-password" class="link text-muted">Forgot password?</Link>
      </div>
      <Btn type="submit" full :disabled="form.processing">{{ form.processing ? 'Signing in…' : 'Log in' }}</Btn>
    </form>
    <p class="mt-6 text-sm text-muted">No account? <Link :href="`/register/${props.type}`" class="link text-ink">Register as {{ type === 'business' ? 'a business' : 'an investor' }}</Link></p>
  </AuthLayout>
</template>
