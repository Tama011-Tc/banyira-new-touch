<script setup lang="ts">
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Btn from '@/Components/Btn.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Field from '@/Components/Field.vue'
import FormGroup from '@/Components/FormGroup.vue'
import Pagination from '@/Components/Pagination.vue'
import TextInput from '@/Components/TextInput.vue'
import type { Paginated } from '@/types'

type Item = { id: number; url: string; alt: string; width: number; height: number; name: string; size: number; by: string | null; used: boolean }

defineProps<{ media: Paginated<Item> }>()

const form = useForm<{ file: File | null; alt: string }>({ file: null, alt: '' })
const input = ref<HTMLInputElement>()
const upload = () => form.post('/internal/content/media', { preserveScroll: true, onSuccess: () => { form.reset(); if (input.value) input.value.value = '' } })
</script>

<template>
  <StaffLayout>
    <PageHead title="Images" sub="Pictures for website pages. Anything uploaded here can be seen by anyone with its address, so never add customer documents." />

    <form class="mb-6 max-w-2xl" @submit.prevent="upload">
      <FormGroup title="Upload" padded>
        <Field v-slot="{ id }" label="Image file" :error="form.errors.file" hint="JPG, PNG or WebP, up to 4 MB.">
          <input :id="id" ref="input" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-[14px]" @change="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null" />
        </Field>
        <TextInput v-model="form.alt" label="Description" maxlength="160" hint="Says what the picture shows, for people who cannot see it." :error="form.errors.alt" />
        <Btn type="submit" :disabled="form.processing || !form.file">{{ form.processing ? 'Uploading…' : 'Upload image' }}</Btn>
      </FormGroup>
    </form>

    <p v-if="(usePage().props.errors as Record<string, string>).media" role="alert" class="mb-3 text-[14px] text-red-text">{{ (usePage().props.errors as Record<string, string>).media }}</p>
    <EmptyState v-if="!media.data.length" title="No images yet" icon="image" />
    <ul v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <li v-for="m in media.data" :key="m.id" class="surface-card overflow-hidden">
        <img :src="m.url" :alt="m.alt" loading="lazy" class="h-40 w-full bg-fill-2 object-contain" />
        <div class="border-t border-separator p-3 text-[13px]">
          <p class="font-medium">{{ m.alt }}</p>
          <p class="truncate text-muted">{{ m.name }} · {{ m.width }}×{{ m.height }} · {{ Math.max(1, Math.round(m.size / 1024)) }} KB</p>
          <div class="mt-2 flex items-center justify-between">
            <span class="text-muted">{{ m.used ? 'In use' : 'Not used' }}</span>
            <ConfirmAction v-if="!m.used" :href="`/internal/content/media/${m.id}`" method="delete" title="Delete this image?" confirm="Delete" variant="ghost" danger>Delete<template #body>“{{ m.alt }}” is removed for good.</template></ConfirmAction>
          </div>
        </div>
      </li>
    </ul>
    <Pagination :page="media" />
  </StaffLayout>
</template>
