<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Btn from './Btn.vue'
import IconButton from './IconButton.vue'
import SelectInput from './SelectInput.vue'
import TextArea from './TextArea.vue'
import TextInput from './TextInput.vue'
import Toggle from './Toggle.vue'
import { emptyData, moved, type BlockField, type MediaOption } from '@/lib/content'

// Draws the form for a section from its field definitions. Lists contain this
// component again, one for each entry.
const props = defineProps<{ fields: BlockField[]; media: MediaOption[]; errors: Record<string, string>; prefix: string }>()
const model = defineModel<Record<string, any>>({ required: true })

const set = (key: string, value: unknown) => (model.value = { ...model.value, [key]: value })
const error = (key: string) => props.errors[props.prefix + key]
const list = (key: string): Record<string, any>[] => model.value[key] ?? []
const hint = (f: BlockField) => (f.format === 'link' ? 'A page on this site such as /about, or a full https:// address.' : f.format === 'anchor' ? 'Lower-case letters, numbers and hyphens.' : undefined)
const image = (id: number | null) => props.media.find((m) => m.id === id)
</script>

<template>
  <div class="space-y-4">
    <template v-for="f in fields" :key="f.key">
      <TextInput v-if="f.kind === 'text'" :model-value="model[f.key]" :label="f.label" :optional="!f.required" :maxlength="f.max" :hint="hint(f)" :error="error(f.key)" @update:model-value="set(f.key, $event)" />
      <TextArea v-else-if="f.kind === 'textarea'" :model-value="model[f.key]" :label="f.label" :optional="!f.required" :maxlength="f.max" :rows="(f.max ?? 0) > 1200 ? 8 : 3" :error="error(f.key)" @update:model-value="set(f.key, $event)" />
      <Toggle v-else-if="f.kind === 'boolean'" :model-value="!!model[f.key]" :label="f.label" @update:model-value="set(f.key, $event)" />
      <SelectInput v-else-if="f.kind === 'select'" :model-value="model[f.key]" :label="f.label" :options="f.options!" :error="error(f.key)" @update:model-value="set(f.key, $event)" />

      <div v-else-if="f.kind === 'media'" class="space-y-2">
        <SelectInput :model-value="model[f.key]" :label="f.label" placeholder="Choose an image" :options="media.map((m) => ({ value: m.id, label: `${m.alt} (#${m.id})` }))" :error="error(f.key)" @update:model-value="set(f.key, $event)" />
        <img v-if="image(model[f.key])" :src="image(model[f.key])!.url" :alt="image(model[f.key])!.alt" class="max-h-40 rounded-md border border-separator" />
        <p class="text-[13px] text-muted">Add pictures under <Link href="/internal/content/media" class="link">Images</Link>.</p>
      </div>

      <fieldset v-else class="space-y-3">
        <legend class="mb-1.5 text-[13px] font-medium">{{ f.label }}</legend>
        <p v-if="error(f.key)" class="text-[13px] text-red-text">{{ error(f.key) }}</p>
        <div v-for="(item, i) in list(f.key)" :key="i" class="rounded-lg border border-separator bg-surface-2 p-3">
          <div class="mb-2 flex items-center justify-between">
            <span class="text-[12px] font-medium text-label-2">{{ f.itemLabel }} {{ i + 1 }}</span>
            <span class="flex">
              <IconButton icon="up" :label="`Move ${f.itemLabel} ${i + 1} up`" :disabled="i === 0" @click="set(f.key, moved(list(f.key), i, -1))" />
              <IconButton icon="arrowdown" :label="`Move ${f.itemLabel} ${i + 1} down`" :disabled="i === list(f.key).length - 1" @click="set(f.key, moved(list(f.key), i, 1))" />
              <IconButton icon="trash" :label="`Remove ${f.itemLabel} ${i + 1}`" @click="set(f.key, list(f.key).filter((_, x) => x !== i))" />
            </span>
          </div>
          <BlockFields :model-value="item" :fields="f.fields!" :media="media" :errors="errors" :prefix="`${prefix}${f.key}.${i}.`"
            @update:model-value="set(f.key, list(f.key).map((old, x) => (x === i ? $event : old)))" />
        </div>
        <Btn v-if="list(f.key).length < f.max!" size="sm" variant="outline" @click="set(f.key, [...list(f.key), emptyData(f.fields!)])">Add {{ f.itemLabel!.toLowerCase() }}</Btn>
      </fieldset>
    </template>
  </div>
</template>
