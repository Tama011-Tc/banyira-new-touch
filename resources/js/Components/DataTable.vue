<script lang="ts">
export type Column = { key: string; label: string; align?: 'right' }
</script>

<script setup lang="ts" generic="T extends Record<string, any>">
// Table on wide screens, stacked rows on phones. Cell content can be
// overridden with a #cell-<key> slot; row actions go in #actions.
defineProps<{ columns: Column[]; rows: T[]; caption: string; empty?: string }>()
</script>

<template>
  <div>
    <div v-if="!rows.length" class="rounded-lg border border-dashed border-line-2 px-6 py-10 text-center text-sm text-muted">
      {{ empty ?? 'Nothing to show yet.' }}
      <div v-if="$slots.emptyAction" class="mt-3"><slot name="emptyAction" /></div>
    </div>
    <template v-else>
      <div class="hidden overflow-x-auto rounded-lg border border-line bg-white md:block">
        <table class="w-full text-left text-sm">
          <caption class="sr-only">{{ caption }}</caption>
          <thead class="text-xs text-muted">
            <tr>
              <th v-for="c in columns" :key="c.key" scope="col" class="px-4 py-2.5 font-medium" :class="c.align === 'right' && 'text-right'">{{ c.label }}</th>
              <th v-if="$slots.actions" scope="col" class="px-4 py-2.5"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, i) in rows" :key="r.id ?? i" class="border-t border-line transition-colors duration-150 hover:bg-[#fafaf7]">
              <td v-for="c in columns" :key="c.key" class="px-4 py-2.5 align-middle" :class="c.align === 'right' && 'num text-right'">
                <slot :name="'cell-' + c.key" :row="r">{{ r[c.key] ?? '—' }}</slot>
              </td>
              <td v-if="$slots.actions" class="px-4 py-2 text-right"><div class="flex justify-end gap-2"><slot name="actions" :row="r" /></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <ul class="divide-y divide-line rounded-lg border border-line bg-white md:hidden">
        <li v-for="(r, i) in rows" :key="r.id ?? i" class="p-4">
          <dl class="space-y-1">
            <div v-for="c in columns" :key="c.key" class="flex justify-between gap-4 text-sm">
              <dt class="text-muted">{{ c.label }}</dt>
              <dd class="text-right"><slot :name="'cell-' + c.key" :row="r">{{ r[c.key] ?? '—' }}</slot></dd>
            </div>
          </dl>
          <div v-if="$slots.actions" class="mt-3 flex justify-end gap-2"><slot name="actions" :row="r" /></div>
        </li>
      </ul>
    </template>
  </div>
</template>
