<script setup lang="ts">
import { watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import WizardLayout from '@/Layouts/WizardLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'

const props = defineProps<{ phone: string; devCode: string | null }>()
const form = useForm({ code: '' })
const step = { key: 'code', title: 'Enter the code we sent', sub: `We sent a 6-digit code by SMS to ${props.phone}. It expires in 10 minutes.` }

const complete = () => /^\d{6}$/.test(form.code)
const verify = () => complete() && !form.processing && form.post('/verify-phone')
// The sixth digit is the answer; there is nothing left to confirm.
watch(() => form.code, verify)
</script>

<template>
  <WizardLayout title="Confirm your phone" :step="step" :progress="50" @next="verify">
    <Notice v-if="devCode" tone="warn" title="Development mode">SMS is being written to the log. Your code is <span class="font-mono font-semibold">{{ devCode }}</span>.</Notice>
    <TextInput v-model="form.code" label="Verification code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="num text-center font-mono text-[24px]! tracking-[0.4em]" :error="form.errors.code" />
    <div class="flex justify-between text-[13px]">
      <button type="button" class="link" @click="router.post('/verify-phone/resend', {}, { preserveScroll: true })">Send a new code</button>
      <button type="button" class="link text-muted" @click="router.post('/logout')">Sign out</button>
    </div>
    <template #footer><Btn type="submit" size="lg" :disabled="!complete() || form.processing">{{ form.processing ? 'Checking…' : 'Verify' }}</Btn></template>
  </WizardLayout>
</template>
