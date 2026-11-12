<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import StaffAuthLayout from '@/Layouts/StaffAuthLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'

defineProps<{ secret: string; uri: string }>()
const form = useForm({ code: '' })
</script>

<template>
  <StaffAuthLayout title="Set up two-step verification" sub="Employees must use an authenticator app (Google Authenticator, Microsoft Authenticator or similar).">
    <ol class="space-y-4 text-sm">
      <li><span class="num mr-1 text-muted">1</span> Open your authenticator app and choose <em>Add account</em>, then <em>Enter a setup key</em>.</li>
      <li>
        <span class="num mr-1 text-muted">2</span> Account name: Banyira. Key:
        <p class="mt-2 select-all rounded border border-line bg-surface px-3 py-2.5 font-mono text-[15px] tracking-wider">{{ secret }}</p>
        <p class="mt-1 text-xs text-muted">Time-based, 6 digits. <a :href="uri" class="link">Open in an authenticator app</a> on this device.</p>
      </li>
      <li><span class="num mr-1 text-muted">3</span> Enter the 6-digit code the app shows.</li>
    </ol>
    <form class="mt-5 space-y-4" @submit.prevent="form.post('/internal-access/setup')">
      <TextInput v-model="form.code" label="Authenticator code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" :error="form.errors.code" />
      <Btn type="submit" variant="dark" full :disabled="form.processing">Confirm and sign in</Btn>
    </form>
  </StaffAuthLayout>
</template>
