<script setup lang="ts">
import { computed, reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'

const props = defineProps<{ roles: any[]; permissions: { id: number; slug: string; name: string; group: string }[]; separated: string[] }>()
const grid = reactive<Record<number, number[]>>(Object.fromEntries(props.roles.map((r) => [r.id, [...r.permissions]])))
const groups = computed(() => [...new Set(props.permissions.map((p) => p.group))])
const toggle = (r: number, p: number) => (grid[r] = grid[r].includes(p) ? grid[r].filter((x) => x !== p) : [...grid[r], p])
const dirty = (r: any) => JSON.stringify([...grid[r.id]].sort()) !== JSON.stringify([...r.permissions].sort())
const blocked = (r: any, p: any) => r.slug === 'it_admin' && props.separated.includes(p.slug)
const err = computed(() => (usePage().props.errors as Record<string, string>).permissions)
</script>

<template>
  <StaffLayout>
    <PageHead title="Roles and permissions" sub="Changes take effect at the employee's next request and are logged with before and after values." />
    <Notice v-if="err" tone="error">{{ err }}</Notice>
    <Notice v-else>IT administrators cannot hold credit, finance or rule-approval powers. You cannot edit a role you hold yourself.</Notice>
    <div class="mt-5 overflow-x-auto rounded-lg border border-line bg-white">
      <table class="w-full min-w-[760px] text-sm">
        <caption class="sr-only">Permission matrix</caption>
        <thead>
          <tr class="text-left text-xs text-muted"><th class="px-4 py-3 font-medium">Permission</th><th v-for="r in roles" :key="r.id" class="px-2 py-3 text-center font-medium">{{ r.name }}<span class="block font-normal">{{ r.users_count }} people</span></th></tr>
        </thead>
        <tbody>
          <template v-for="g in groups" :key="g">
            <tr><th :colspan="roles.length + 1" class="bg-[#fafaf8] px-4 py-1.5 text-left text-[11px] font-medium uppercase tracking-wider text-muted">{{ g }}</th></tr>
            <tr v-for="p in permissions.filter((x) => x.group === g)" :key="p.id" class="border-t border-line">
              <td class="px-4 py-2">{{ p.name }}<span class="block font-mono text-[11px] text-muted">{{ p.slug }}</span></td>
              <td v-for="r in roles" :key="r.id" class="px-2 py-2 text-center">
                <input type="checkbox" class="h-4 w-4" :checked="grid[r.id].includes(p.id)" :disabled="blocked(r, p)" :aria-label="`${r.name}: ${p.name}`" @change="toggle(r.id, p.id)" />
              </td>
            </tr>
          </template>
          <tr class="border-t border-line">
            <td class="px-4 py-3" />
            <td v-for="r in roles" :key="r.id" class="px-2 py-3 text-center"><Btn v-if="dirty(r)" size="sm" @click="router.put(`/internal/it/roles/${r.id}`, { permissions: grid[r.id] }, { preserveScroll: true })">Save</Btn></td>
          </tr>
        </tbody>
      </table>
    </div>
  </StaffLayout>
</template>
