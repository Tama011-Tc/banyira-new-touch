<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Btn from '@/Components/Btn.vue'
import FormGroup from '@/Components/FormGroup.vue'
import TextArea from '@/Components/TextArea.vue'
import TextInput from '@/Components/TextInput.vue'
import Toggle from '@/Components/Toggle.vue'
import type { Site } from '@/types'

const props = defineProps<{ settings: Site['settings'] }>()
const form = useForm({
  announcementEnabled: props.settings.announcement.enabled,
  announcementText: props.settings.announcement.text,
  announcementLink: props.settings.announcement.link,
  footer_about: props.settings.footer_about,
  footer_location: props.settings.footer_location,
  footer_notice: props.settings.footer_notice,
  risk_statement: props.settings.risk_statement,
})
// Kept here because vue-tsc misreads "//" inside a template attribute.
const LINK_HINT = 'A page on this site such as /about, or a full https:// address.'
// The server names the announcement fields "announcement.text" and so on.
const announcementError = (field: string) => (form.errors as Record<string, string>)[`announcement.${field}`]
const save = () => form
  .transform(({ announcementEnabled, announcementText, announcementLink, ...rest }) => ({
    ...rest,
    announcement: { enabled: announcementEnabled, text: announcementText, link: announcementLink },
  }))
  .put('/internal/content/settings', { preserveScroll: true, onSuccess: () => form.defaults() })
</script>

<template>
  <StaffLayout>
    <PageHead title="Site settings" sub="Words shown on every public page. Saving changes the website immediately." />
    <form class="grid max-w-2xl gap-6" @submit.prevent="save">
      <FormGroup title="Announcement bar" padded>
        <Toggle v-model="form.announcementEnabled" label="Show an announcement at the top" />
        <template v-if="form.announcementEnabled">
          <TextInput v-model="form.announcementText" label="Announcement" maxlength="200" :error="announcementError('text')" />
          <TextInput v-model="form.announcementLink" label="Link" optional :hint="LINK_HINT" :error="announcementError('link')" />
        </template>
      </FormGroup>

      <FormGroup title="Footer" padded>
        <TextArea v-model="form.footer_about" label="About Banyira" :rows="3" maxlength="400" :error="form.errors.footer_about" />
        <TextInput v-model="form.footer_location" label="Location" maxlength="120" :error="form.errors.footer_location" />
        <TextInput v-model="form.footer_notice" label="Application notice" maxlength="300" :error="form.errors.footer_notice" />
      </FormGroup>

      <FormGroup title="Risk statement" footer="Follows “Investing involves risk.” on every public page. It cannot be removed. Check changes with legal counsel." padded>
        <TextArea v-model="form.risk_statement" label="Statement" :rows="4" maxlength="600" :error="form.errors.risk_statement" />
      </FormGroup>

      <div><Btn type="submit" :disabled="form.processing || !form.isDirty">Save and publish</Btn></div>
    </form>
  </StaffLayout>
</template>
