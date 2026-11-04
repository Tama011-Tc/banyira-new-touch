<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageTop from '@/Components/PageTop.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextArea from '@/Components/TextArea.vue'
import Btn from '@/Components/Btn.vue'

const form = useForm({ name: '', email: '', phone: '', type: 'business', message: '' })
const send = () => form.post('/contact', { preserveScroll: true, onSuccess: () => form.reset() })
</script>

<template>
  <PublicLayout>
    <PageTop title="Talk to us." sub="Questions about financing, investing or an existing account. We reply within two working days." />
    <div class="mx-auto grid max-w-6xl gap-12 px-4 pb-20 sm:px-6 md:grid-cols-[1fr_280px]">
      <form class="max-w-lg space-y-4" @submit.prevent="send">
        <TextInput v-model="form.name" label="Full name" autocomplete="name" :error="form.errors.name" />
        <div class="grid gap-4 sm:grid-cols-2">
          <TextInput v-model="form.email" label="Email" type="email" autocomplete="email" :error="form.errors.email" />
          <TextInput v-model="form.phone" label="Phone" type="tel" autocomplete="tel" optional :error="form.errors.phone" />
        </div>
        <SelectInput v-model="form.type" label="I am" :options="[{ value: 'business', label: 'A business owner' }, { value: 'investor', label: 'An investor' }, { value: 'other', label: 'Something else' }]" />
        <TextArea v-model="form.message" label="Message" :rows="5" :error="form.errors.message" />
        <Btn type="submit" :disabled="form.processing">{{ form.processing ? 'Sending…' : 'Send message' }}</Btn>
      </form>
      <aside class="space-y-5 text-sm">
        <div><h2 class="font-sans text-[13px] font-medium tracking-normal">Office</h2><p class="mt-1">Lilongwe, Malawi</p></div>
        <div><h2 class="font-sans text-[13px] font-medium tracking-normal">Hours</h2><p class="mt-1">Monday to Friday, 08:00–17:00</p></div>
        <div><h2 class="font-sans text-[13px] font-medium tracking-normal">Existing customers</h2><p class="mt-1 text-muted">Use Help and Support in your dashboard so we can see your account.</p></div>
      </aside>
    </div>
  </PublicLayout>
</template>
