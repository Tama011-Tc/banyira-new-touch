<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import BlockFields from '@/Components/BlockFields.vue'
import Btn from '@/Components/Btn.vue'
import Card from '@/Components/Card.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import FormGroup from '@/Components/FormGroup.vue'
import Icon from '@/Components/Icon.vue'
import IconButton from '@/Components/IconButton.vue'
import Modal from '@/Components/Modal.vue'
import Notice from '@/Components/Notice.vue'
import SelectInput from '@/Components/SelectInput.vue'
import Status from '@/Components/Status.vue'
import TextArea from '@/Components/TextArea.vue'
import TextInput from '@/Components/TextInput.vue'
import Toggle from '@/Components/Toggle.vue'
import { moved, newBlock, summary, type BlockType, type MediaOption, type PageContent } from '@/lib/content'
import { useUnsaved } from '@/lib/unsaved'
import { dateTime, label } from '@/lib/format'
import { useCan } from '@/lib/permissions'

const props = defineProps<{
  page: { id: number; slug: string; path: string; isSystem: boolean; state: string; revision: number; content: PageContent; publishedAt: string | null }
  versions: { id: number; title: string; by: string | null; at: string }[]
  catalogue: Record<string, BlockType>
  footerColumns: string[]
  media: MediaOption[]
}>()

const can = useCan()
const url = computed(() => `/internal/content/pages/${props.page.id}`)
const errors = computed(() => usePage().props.errors as Record<string, string>)

const copy = <T,>(v: T): T => JSON.parse(JSON.stringify(v))
const content = ref<PageContent>(copy(props.page.content))
const dirty = computed(() => JSON.stringify(content.value) !== JSON.stringify(props.page.content))
// A new revision from the server (saved, discarded or restored) replaces what is on screen.
watch(() => props.page.revision, () => (content.value = copy(props.page.content)))

const { recovered, forget } = useUnsaved(() => `banyira-page-draft-${props.page.id}`, () => props.page.revision, () => content.value, () => dirty.value)
const recover = () => { content.value = recovered.value as PageContent; recovered.value = null }

const openId = ref<string | null>(null)
const adding = ref(false)
const saving = ref(false)

const blocks = computed(() => content.value.blocks)
const hasErrors = (i: number) => Object.keys(errors.value).some((k) => k.startsWith(`blocks.${i}.`))
const add = (type: string) => {
  const block = newBlock(type, props.catalogue[type])
  content.value.blocks.push(block)
  openId.value = block.id
  adding.value = false
}
const remove = (id: string) => (content.value.blocks = blocks.value.filter((b) => b.id !== id))

const save = () => router.put(url.value, { content: content.value as any, revision: props.page.revision }, {
  preserveScroll: true, preserveState: true,
  onStart: () => (saving.value = true),
  onSuccess: forget,
  // Open the first section the server found a problem in.
  onError: (errs) => {
    const key = Object.keys(errs).find((k) => k.startsWith('blocks.'))
    if (key) openId.value = blocks.value[Number(key.split('.')[1])]?.id ?? openId.value
  },
  onFinish: () => (saving.value = false),
})
const preview = () => router.visit(`${url.value}/preview`)

const footerOptions = computed(() => [{ value: '', label: 'Not in the footer' }, ...props.footerColumns.map((c) => ({ value: c, label: `Under “${label(c)}”` }))])
</script>

<template>
  <StaffLayout>
    <PageHead :title="page.content.title" :sub="`${page.path} · ${page.isSystem ? 'Built-in page' : 'Added page'}`" :back="{ href: '/internal/content', label: 'Pages' }">
      <Btn variant="outline" :disabled="dirty" @click="preview">Preview</Btn>
      <Btn v-if="can('content.manage')" :variant="dirty ? 'primary' : 'outline'" :disabled="!dirty || saving" @click="save">{{ saving ? 'Saving…' : 'Save draft' }}</Btn>
      <ConfirmAction v-if="can('content.publish') && page.state !== 'published' && !dirty" :href="`${url}/publish`" :data="{ revision: page.revision }" title="Publish this page?" confirm="Publish" variant="primary" size="md">
        Publish<template #body>Everyone who visits {{ page.path }} will see this version.</template>
      </ConfirmAction>
    </PageHead>

    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-muted">
      <Status :s="page.state === 'changes' ? 'draft_changes' : page.state" />
      <span v-if="dirty">You have unsaved changes. Save before previewing or publishing.</span>
      <span v-else-if="page.state === 'changes'">The website still shows the version published {{ dateTime(page.publishedAt) }}.</span>
      <span v-else-if="page.state === 'draft'">Not on the website yet.</span>
      <a v-if="page.state !== 'draft'" :href="page.path" target="_blank" rel="noopener" class="link">View the live page</a>
    </div>

    <Notice v-if="recovered" tone="warn" title="Unsaved changes from earlier" class="mb-4">
      This browser kept edits that were never saved.
      <span class="mt-2 flex gap-2"><Btn size="sm" @click="recover">Bring them back</Btn><Btn size="sm" variant="outline" @click="forget">Throw them away</Btn></span>
    </Notice>
    <Notice v-if="errors.page" tone="error" class="mb-4">{{ errors.page }}</Notice>
    <Notice v-else-if="Object.keys(errors).length" tone="error" class="mb-4">{{ errors.blocks ?? 'Some fields need attention. They are marked below.' }}</Notice>

    <div class="grid grid-cols-[minmax(0,1fr)] items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
      <section aria-label="Sections">
        <h2 class="mb-2 text-[16px]">Sections</h2>
        <p v-if="!blocks.length" class="surface-card px-4 py-8 text-center text-muted">No sections yet.</p>
        <ol class="space-y-2">
          <li v-for="(b, i) in blocks" :key="b.id" class="surface-card" :class="hasErrors(i) && 'border-red'">
            <div class="flex items-center gap-1 py-1.5 pl-2 pr-1.5">
              <button type="button" class="flex min-h-11 min-w-0 flex-1 items-center gap-2 rounded-md px-2 text-left hover:bg-fill-2" :aria-expanded="openId === b.id" @click="openId = openId === b.id ? null : b.id">
                <Icon :n="openId === b.id ? 'chevdown' : 'chev'" :size="16" class="text-label-3" />
                <span class="min-w-0" :class="!b.visible && 'opacity-50'">
                  <span class="block text-[14px] font-medium">{{ catalogue[b.type]?.label ?? b.type }}<span v-if="!b.visible" class="ml-2 text-[12px] font-normal">Hidden</span></span>
                  <span class="block truncate text-[12px] text-muted">{{ summary(b) }}</span>
                </span>
              </button>
              <template v-if="can('content.manage')">
                <IconButton icon="up" label="Move section up" :disabled="i === 0" @click="content.blocks = moved(blocks, i, -1)" />
                <IconButton icon="arrowdown" label="Move section down" :disabled="i === blocks.length - 1" @click="content.blocks = moved(blocks, i, 1)" />
                <IconButton :icon="b.visible ? 'eye' : 'eyeoff'" :label="b.visible ? 'Hide section' : 'Show section'" @click="b.visible = !b.visible" />
                <IconButton icon="trash" label="Remove section" @click="remove(b.id)" />
              </template>
            </div>
            <fieldset v-if="openId === b.id && catalogue[b.type]" :disabled="!can('content.manage')" class="border-t border-separator p-4">
              <legend class="sr-only">{{ catalogue[b.type].label }} fields</legend>
              <BlockFields v-model="b.data" :fields="catalogue[b.type].fields" :media="media" :errors="errors" :prefix="`blocks.${i}.data.`" />
            </fieldset>
          </li>
        </ol>
        <Btn v-if="can('content.manage')" class="mt-3" variant="outline" @click="adding = true"><Icon n="plus" :size="16" />Add section</Btn>
      </section>

      <div class="space-y-6">
        <fieldset :disabled="!can('content.manage')" class="space-y-6">
          <legend class="sr-only">Page settings</legend>
          <FormGroup title="Page" padded>
            <TextInput v-model="content.title" label="Browser title" maxlength="120" :error="errors.title" />
            <TextArea v-model="content.description" label="Description for search engines" optional :rows="3" maxlength="300" :error="errors.description" />
          </FormGroup>

          <FormGroup title="Menus" footer="Lower numbers come first." padded>
            <Toggle v-model="content.nav.header" label="Show in the top menu" />
            <TextInput v-if="content.nav.header" v-model="content.nav.header_label" label="Top menu text" optional maxlength="30" :error="errors['nav.header_label']" />
            <SelectInput v-model="content.nav.footer" label="Footer" :options="footerOptions" :error="errors['nav.footer']" />
            <TextInput v-if="content.nav.footer" v-model="content.nav.footer_label" label="Footer text" optional maxlength="60" :error="errors['nav.footer_label']" />
            <TextInput v-model.number="content.nav.order" label="Order" type="number" min="0" max="999" :error="errors['nav.order']" />
          </FormGroup>
        </fieldset>

        <Card title="Published versions" sub="Restoring copies a version into the draft.">
          <p v-if="!versions.length" class="text-[13px] text-muted">Nothing has been published yet.</p>
          <ul v-else class="divide-y divide-separator">
            <li v-for="(v, i) in versions" :key="v.id" class="flex items-center justify-between gap-3 py-2 text-[13px] first:pt-0 last:pb-0">
              <span class="min-w-0"><span class="block truncate">{{ dateTime(v.at) }}</span><span class="block truncate text-muted">{{ v.by ?? 'Original content' }}{{ i === 0 && page.state !== 'draft' ? ' · live' : '' }}</span></span>
              <ConfirmAction v-if="can('content.manage')" :href="`${url}/versions/${v.id}/restore`" title="Restore this version?" confirm="Restore to draft" variant="ghost">Restore<template #body>It replaces the current draft. The website does not change until you publish.</template></ConfirmAction>
            </li>
          </ul>
        </Card>

        <div class="flex flex-wrap gap-2">
          <ConfirmAction v-if="can('content.manage') && page.state === 'changes'" :href="`${url}/discard`" title="Discard the draft?" confirm="Discard draft" danger>Discard draft<template #body>The draft goes back to what is on the website now.</template></ConfirmAction>
          <template v-if="can('content.publish') && !page.isSystem">
            <ConfirmAction v-if="page.state !== 'draft'" :href="`${url}/unpublish`" title="Take this page down?" confirm="Take down" variant="danger" danger>Take down<template #body>{{ page.path }} stops working until you publish it again. The draft is kept.</template></ConfirmAction>
            <ConfirmAction v-else :href="url" method="delete" title="Delete this page?" confirm="Delete page" variant="danger" danger>Delete page<template #body>The page and its history are removed. This cannot be undone.</template></ConfirmAction>
          </template>
        </div>
      </div>
    </div>

    <Modal :open="adding" title="Add a section" @close="adding = false">
      <ul class="divide-y divide-separator">
        <li v-for="(type, key) in catalogue" :key="key">
          <button type="button" class="block w-full rounded-md px-2 py-2.5 text-left hover:bg-fill-2" @click="add(String(key))">
            <span class="block font-medium text-label">{{ type.label }}</span>
            <span class="block text-[13px] text-label-2">{{ type.description }}</span>
          </button>
        </li>
      </ul>
    </Modal>
  </StaffLayout>
</template>
