<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import type { TableState } from '@/lib/table'

export type TableFilter = { key: string; label: string; options: { value: string; label: string }[] }

const props = defineProps<{ query: TableState; filters?: TableFilter[]; searchLabel?: string }>()
const emit = defineEmits<{ change: [patch: Partial<TableState>] }>()

// Typing is sent after a short pause, so a search is not made for every letter.
const term = ref(props.query.q ?? '')
let timer: ReturnType<typeof setTimeout> | undefined
const search = () => {
  clearTimeout(timer)
  timer = setTimeout(() => emit('change', { q: term.value.trim() }), 300)
}
onBeforeUnmount(() => clearTimeout(timer))

const value = (e: Event) => (e.target as HTMLSelectElement).value
</script>

<template>
  <div role="search" class="mb-3 flex flex-wrap items-center gap-2">
    <input v-if="searchLabel" v-model="term" type="search" class="input w-full sm:w-64" :aria-label="searchLabel" :placeholder="searchLabel" maxlength="100" @input="search" />
    <select v-for="f in filters" :key="f.key" class="input w-auto" :aria-label="f.label" :value="query[f.key] ?? ''" @change="emit('change', { [f.key]: value($event) })">
      <option value="">{{ f.label }}: all</option>
      <option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
    </select>
    <select class="input ml-auto w-auto" aria-label="Rows per page" :value="query.per" @change="emit('change', { per: Number(value($event)) })">
      <option v-for="n in [25, 50, 100]" :key="n" :value="n">{{ n }} rows</option>
    </select>
  </div>
</template>
