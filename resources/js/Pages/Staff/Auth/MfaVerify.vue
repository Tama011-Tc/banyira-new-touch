<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import StaffAuthLayout from '@/Layouts/StaffAuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'

const form = useForm({ code: '' })
</script>

<template>
  <StaffAuthLayout title="Two-step verification" sub="Enter the 6-digit code from your authenticator app.">
    <form class="space-y-4" @submit.prevent="form.post('/internal-access/verify', { onFinish: () => form.reset() })">
      <TextInput v-model="form.code" label="Authenticator code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus :error="form.errors.code" />
      <Btn type="submit" variant="dark" full :disabled="form.processing">Verify and sign in</Btn>
    </form>
    <p class="mt-5 text-[13px] text-muted">Lost your device? Ask the IT Administrator to reset your two-step verification. <button type="button" class="link" @click="router.post('/internal-access/logout')">Cancel</button></p>
  </StaffAuthLayout>
</template>
