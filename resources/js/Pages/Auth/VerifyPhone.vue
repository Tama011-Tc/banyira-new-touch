<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'

defineProps<{ phone: string; devCode: string | null }>()
const form = useForm({ code: '' })
</script>

<template>
  <AuthLayout title="Confirm your phone" :sub="`We sent a 6-digit code by SMS to ${phone}. It expires in 10 minutes.`" step="Step 2 of 3 · Verification">
    <Notice v-if="devCode" tone="warn" title="Development mode">SMS is being written to the log. Your code is <span class="font-mono font-semibold">{{ devCode }}</span>.</Notice>
    <form class="mt-4 space-y-4" @submit.prevent="form.post('/verify-phone')">
      <TextInput v-model="form.code" label="Verification code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus :error="form.errors.code" />
      <Btn type="submit" full :disabled="form.processing">Verify</Btn>
    </form>
    <div class="mt-5 flex justify-between text-[13px]">
      <button type="button" class="link text-muted" @click="router.post('/verify-phone/resend', {}, { preserveScroll: true })">Send a new code</button>
      <button type="button" class="link text-muted" @click="router.post('/logout')">Sign out</button>
    </div>
  </AuthLayout>
</template>
