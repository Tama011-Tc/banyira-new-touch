<script lang="ts">
export type Column = { key: string; label: string; align?: 'right'; sort?: string }
</script>

<script setup lang="ts" generic="T extends Record<string, any>">
import EmptyState from './EmptyState.vue'
import Icon from './Icon.vue'

// Clean rows with a hover highlight on wide screens; on phones each row
// becomes a card in a grouped list. Override a cell with #cell-<key>.
// A column with "sort" gets a sortable heading; the server does the sorting.
defineProps<{ columns: Column[]; rows: T[]; caption: string; empty?: string; emptyIcon?: string; order?: { sort: string; dir: 'asc' | 'desc' } }>()
defineEmits<{ sort: [key: string] }>()
</script>

<template>
  <div>
    <EmptyState v-if="!rows.length" :title="empty ?? 'Nothing here yet'" :icon="emptyIcon">
      <slot name="emptyAction" />
    </EmptyState>
    <template v-else>
      <div class="surface-card hidden relative overflow-x-auto md:block">
        <table class="w-full text-left text-[14px]">
          <caption class="sr-only">{{ caption }}</caption>
          <thead>
            <tr class="text-[12px] text-label-2">
              <th v-for="c in columns" :key="c.key" scope="col" class="px-4 pb-2 pt-3.5 font-medium first:pl-5" :class="c.align === 'right' && 'text-right'"
                :aria-sort="c.sort && order?.sort === c.sort ? (order.dir === 'asc' ? 'ascending' : 'descending') : undefined">
                <button v-if="c.sort" type="button" class="inline-flex items-center gap-1 hover:text-label" :class="order?.sort === c.sort && 'text-label'" @click="$emit('sort', c.sort)">
                  {{ c.label }}<Icon v-if="order?.sort === c.sort" :n="order.dir === 'asc' ? 'up' : 'arrowdown'" :size="12" />
                </button>
                <template v-else>{{ c.label }}</template>
              </th>
              <th v-if="$slots.actions" scope="col" class="px-4 pb-2 pt-3.5 pr-5"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in rows" :key="r.id ?? i" class="group transition-colors duration-150 hover:bg-fill-2">
              <td v-for="c in columns" :key="c.key" class="border-t border-separator px-4 py-3 align-middle first:pl-5" :class="c.align === 'right' && 'num text-right'">
                <slot :name="'cell-' + c.key" :row="r">{{ r[c.key] ?? '—' }}</slot>
              </td>
              <td v-if="$slots.actions" class="border-t border-separator px-4 py-2 pr-5 text-right"><div class="flex justify-end gap-2"><slot name="actions" :row="r" /></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <ul class="space-y-2.5 md:hidden">
        <li v-for="(r, i) in rows" :key="r.id ?? i" class="surface-card px-4 py-3.5">
          <dl class="space-y-1.5">
            <div v-for="(c, ci) in columns" :key="c.key" class="flex items-baseline justify-between gap-4" :class="ci === 0 ? 'text-[15px] font-semibold' : 'text-[14px]'">
              <dt v-if="ci > 0" class="text-label-2">{{ c.label }}</dt>
              <dd :class="[ci === 0 ? 'w-full' : 'text-right', c.align === 'right' && 'num']"><slot :name="'cell-' + c.key" :row="r">{{ r[c.key] ?? '—' }}</slot></dd>
            </div>
          </dl>
          <div v-if="$slots.actions" class="mt-3 flex flex-wrap justify-end gap-2 border-t border-separator pt-3"><slot name="actions" :row="r" /></div>
        </li>
      </ul>
    </template>
  </div>
</template>
