<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Btn from '@/Components/Btn.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import DataTable from '@/Components/DataTable.vue'
import FormGroup from '@/Components/FormGroup.vue'
import Modal from '@/Components/Modal.vue'
import Notice from '@/Components/Notice.vue'
import Pagination from '@/Components/Pagination.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import Status from '@/Components/Status.vue'
import TextInput from '@/Components/TextInput.vue'
import { dateTime, label } from '@/lib/format'
import type { Paginated } from '@/types'

type Backup = { id: number; filename: string; size: number | null; checksum: string | null; status: string; origin: string; error: string | null; created_at: string; by: string | null }

const props = defineProps<{ backups: Paginated<Backup>; settings: { frequency: string; keep: number }; frequencies: string[]; downloadLink: string | null }>()

const size = (bytes: number | null) => (bytes === null ? '—' : bytes < 1_048_576 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / 1_048_576).toFixed(1)} MB`)

const running = ref(false)
const take = () => router.post('/internal/it/backups', {}, { preserveScroll: true, onStart: () => (running.value = true), onFinish: () => (running.value = false) })

const schedule = useForm({ frequency: props.settings.frequency, keep: props.settings.keep })

const chosen = ref<Backup | null>(null)
const download = useForm({ password: '' })
const choose = (b: Backup) => { download.reset(); download.clearErrors(); chosen.value = b }
// The server answers with a one-minute link, which the browser follows to save the file.
const fetchFile = () => download.post(`/internal/it/backups/${chosen.value!.id}/download`, {
  preserveScroll: true, preserveState: true,
  onSuccess: () => {
    chosen.value = null
    if (props.downloadLink) window.location.assign(props.downloadLink)
  },
})
</script>

<template>
  <StaffLayout>
    <PageHead title="Backups" sub="Compressed copies of the whole database. Uploaded documents and images are stored separately and are not included.">
      <Btn :disabled="running" @click="take">{{ running ? 'Backing up…' : 'Back up now' }}</Btn>
    </PageHead>

    <Notice tone="warn" class="mb-5" title="Treat a backup like the database itself">It holds every customer and financial record. Downloads need your password, are logged and are announced to other administrators.</Notice>

    <form class="mb-6 max-w-2xl" @submit.prevent="schedule.put('/internal/it/backups/settings', { preserveScroll: true })">
      <FormGroup title="Schedule" footer="Scheduled backups run at 02:00 and need the scheduler to be running. Older completed backups are removed automatically." padded>
        <div class="grid gap-4 sm:grid-cols-2">
          <SelectInput v-model="schedule.frequency" label="Automatic backups" :options="frequencies.map((f) => ({ value: f, label: label(f) }))" :error="schedule.errors.frequency" />
          <TextInput v-model="schedule.keep" label="Backups to keep" type="number" min="1" max="90" :error="schedule.errors.keep" />
        </div>
        <Btn type="submit" :disabled="schedule.processing">Save schedule</Btn>
      </FormGroup>
    </form>

    <DataTable caption="Backups" empty="No backups yet" empty-icon="database" :rows="backups.data"
      :columns="[{ key: 'filename', label: 'File' }, { key: 'created_at', label: 'Taken' }, { key: 'origin', label: 'How' }, { key: 'size', label: 'Size', align: 'right' }, { key: 'status', label: 'Status' }]">
      <template #cell-filename="{ row }">
        <span class="font-mono text-[13px]">{{ row.filename }}</span>
        <span v-if="row.error" class="block text-xs text-danger">{{ row.error }}</span>
        <span v-else-if="row.checksum" class="block font-mono text-[11px] text-muted" :title="row.checksum">SHA-256 {{ row.checksum.slice(0, 16) }}…</span>
      </template>
      <template #cell-created_at="{ row }">{{ dateTime(row.created_at) }}</template>
      <template #cell-origin="{ row }">{{ row.by ?? label(row.origin) }}</template>
      <template #cell-size="{ row }">{{ size(row.size) }}</template>
      <template #cell-status="{ row }"><Status :s="row.status" /></template>
      <template #actions="{ row }">
        <Btn v-if="row.status === 'completed'" size="sm" variant="outline" :label="`Download ${row.filename}`" @click="choose(row)">Download</Btn>
        <ConfirmAction v-if="row.status !== 'running'" :href="`/internal/it/backups/${row.id}`" method="delete" title="Delete this backup?" confirm="Delete" variant="ghost" danger>Delete<template #body>The file {{ row.filename }} is removed for good.</template></ConfirmAction>
      </template>
    </DataTable>
    <Pagination :page="backups" />

    <Modal :open="!!chosen" title="Download this backup?" @close="chosen = null">
      <p class="text-label-2">Enter your password to download <span class="font-mono text-[13px]">{{ chosen?.filename }}</span>. Store it somewhere private.</p>
      <form id="download" class="mt-4 text-ink" @submit.prevent="fetchFile">
        <PasswordInput v-model="download.password" label="Your password" autocomplete="current-password" :error="download.errors.password" />
      </form>
      <template #actions><Btn variant="outline" @click="chosen = null">Cancel</Btn><Btn type="submit" form="download" :disabled="download.processing || !download.password">Download</Btn></template>
    </Modal>
  </StaffLayout>
</template>
