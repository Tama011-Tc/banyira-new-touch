<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Btn from '@/Components/Btn.vue'
import DataTable from '@/Components/DataTable.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import Status from '@/Components/Status.vue'
import TableToolbar from '@/Components/TableToolbar.vue'
import TextInput from '@/Components/TextInput.vue'
import { dateTime } from '@/lib/format'
import { useCan } from '@/lib/permissions'
import { useTableQuery, type TableState } from '@/lib/table'
import type { Paginated } from '@/types'

type Row = { id: number; title: string; path: string; isSystem: boolean; state: 'draft' | 'published' | 'changes'; updatedAt: string; updatedBy: string | null }

const props = defineProps<{ pages: Paginated<Row>; query: TableState }>()
const can = useCan()
const { apply, sortBy } = useTableQuery('/internal/content', () => props.query)
const filters = [{ key: 'type', label: 'Type', options: [{ value: 'builtin', label: 'Built-in' }, { value: 'custom', label: 'Added' }] }]
const STATE = { draft: 'draft', published: 'published', changes: 'draft_changes' }

const open = ref(false)
const form = useForm({ title: '', slug: '' })
const slugify = (v: string) => v.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80)
// The address follows the title until someone types their own.
watch(() => form.title, (title, before) => { if (form.slug === slugify(before)) form.slug = slugify(title) })
</script>

<template>
  <StaffLayout>
    <PageHead title="Website pages" sub="Change what the public website says. Edits are saved as a draft and appear only when published. Product pages are edited under Products.">
      <Btn v-if="can('content.manage')" @click="open = true">New page</Btn>
    </PageHead>
    <TableToolbar :query="query" :filters="filters" search-label="Search title or address" @change="apply" />
    <DataTable caption="Website pages" empty="No pages match" :rows="pages.data" :order="query" @sort="sortBy"
      :columns="[{ key: 'title', label: 'Page' }, { key: 'path', label: 'Address', sort: 'address' }, { key: 'state', label: 'Status' }, { key: 'updatedAt', label: 'Last change', sort: 'updated' }]">
      <template #cell-title="{ row }">{{ row.title }}<span class="block text-xs font-normal text-muted">{{ row.isSystem ? 'Built-in' : 'Added' }}</span></template>
      <template #cell-path="{ row }"><span class="font-mono text-[13px]">{{ row.path }}</span></template>
      <template #cell-state="{ row }"><Status :s="STATE[row.state as Row['state']]" /></template>
      <template #cell-updatedAt="{ row }">{{ dateTime(row.updatedAt) }}<span v-if="row.updatedBy" class="block text-xs text-muted">{{ row.updatedBy }}</span></template>
      <template #actions="{ row }"><Btn size="sm" variant="outline" :href="`/internal/content/pages/${row.id}`" :label="`Edit ${row.title}`">{{ can('content.manage') ? 'Edit' : 'Open' }}</Btn></template>
    </DataTable>
    <Pagination :page="pages" />

    <Modal :open="open" title="New page" @close="open = false">
      <form id="page" class="space-y-3 text-ink" @submit.prevent="form.post('/internal/content/pages')">
        <TextInput v-model="form.title" label="Title" maxlength="120" :error="form.errors.title" />
        <TextInput v-model="form.slug" label="Address" maxlength="80" :hint="`The page will be at /pages/${form.slug || 'your-address'}. It cannot be changed later.`" :error="form.errors.slug" />
      </form>
      <template #actions><Btn variant="outline" @click="open = false">Cancel</Btn><Btn type="submit" form="page" :disabled="form.processing">Create draft</Btn></template>
    </Modal>
  </StaffLayout>
</template>
