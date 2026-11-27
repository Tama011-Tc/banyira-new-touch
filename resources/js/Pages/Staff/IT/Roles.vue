<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Btn from '@/Components/Btn.vue'
import Checkbox from '@/Components/Checkbox.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import Modal from '@/Components/Modal.vue'
import Notice from '@/Components/Notice.vue'
import TextInput from '@/Components/TextInput.vue'

type Role = { id: number; slug: string; name: string; description: string | null; users_count: number; permissions: number[]; builtIn: boolean; own: boolean }
type Permission = { id: number; slug: string; name: string; group: string }

const props = defineProps<{ roles: Role[]; permissions: Permission[]; separation: { admin: string[]; approval: string[] } }>()

// Unsaved ticks for each role, refreshed when the server sends new data.
const grid = reactive<Record<number, number[]>>({})
watch(() => props.roles, (roles) => roles.forEach((r) => (grid[r.id] = [...r.permissions])), { immediate: true })

const groups = computed(() => [...new Set(props.permissions.map((p) => p.group))])
const toggle = (r: number, p: number) => (grid[r] = grid[r].includes(p) ? grid[r].filter((x) => x !== p) : [...grid[r], p])
const dirty = (r: Role) => JSON.stringify([...grid[r.id]].sort()) !== JSON.stringify([...r.permissions].sort())
const slugs = (ids: number[]) => props.permissions.filter((p) => ids.includes(p.id)).map((p) => p.slug)

/** A role that administers the platform cannot hold approval powers. The server enforces this too. */
const blocked = (r: Role, p: Permission) => {
  const held = slugs(grid[r.id])
  if (held.includes(p.slug)) return false
  const administers = r.slug === 'it_admin' || held.some((s) => props.separation.admin.includes(s))
  if (props.separation.approval.includes(p.slug)) return administers
  return props.separation.admin.includes(p.slug) && held.some((s) => props.separation.approval.includes(s))
}

const err = computed(() => (usePage().props.errors as Record<string, string>).permissions)
const save = (r: Role) => router.put(`/internal/it/roles/${r.id}`, { permissions: grid[r.id] }, { preserveScroll: true, preserveState: true })

const open = ref(false)
const form = useForm({ name: '', description: '', permissions: [] as number[] })
const create = () => form.post('/internal/it/roles', { preserveScroll: true, onSuccess: () => { open.value = false; form.reset() } })
const pick = (id: number) => (form.permissions = form.permissions.includes(id) ? form.permissions.filter((x) => x !== id) : [...form.permissions, id])
</script>

<template>
  <StaffLayout>
    <PageHead title="Roles and permissions" sub="Changes take effect at the employee's next request and are logged with before and after values.">
      <Btn @click="open = true">New role</Btn>
    </PageHead>
    <Notice v-if="err" tone="error">{{ err }}</Notice>
    <Notice v-else>A role that administers the platform cannot hold credit, finance or rule-approval powers. You cannot edit a role you hold yourself.</Notice>

    <div class="mt-5 relative overflow-x-auto rounded-lg border border-line bg-surface">
      <table class="w-full min-w-[760px] text-sm">
        <caption class="sr-only">Permission matrix</caption>
        <thead>
          <tr class="text-left text-xs text-muted"><th class="px-4 py-3 font-medium">Permission</th><th v-for="r in roles" :key="r.id" class="px-2 py-3 text-center align-top font-medium">
            {{ r.name }}<span class="block font-normal">{{ r.users_count }} {{ r.users_count === 1 ? 'person' : 'people' }}</span>
            <ConfirmAction v-if="!r.builtIn && !r.own && !r.users_count" :href="`/internal/it/roles/${r.id}`" method="delete" :title="`Delete the ${r.name} role?`" confirm="Delete role" variant="ghost" danger>Delete<template #body>Nobody holds this role. This cannot be undone.</template></ConfirmAction>
          </th></tr>
        </thead>
        <tbody>
          <template v-for="g in groups" :key="g">
            <tr><th :colspan="roles.length + 1" class="bg-fill px-4 py-1.5 text-left text-[11px] font-medium uppercase tracking-wider text-muted">{{ g }}</th></tr>
            <tr v-for="p in permissions.filter((x) => x.group === g)" :key="p.id" class="border-t border-line">
              <td class="px-4 py-2">{{ p.name }}<span class="block font-mono text-[11px] text-muted">{{ p.slug }}</span></td>
              <td v-for="r in roles" :key="r.id" class="px-2 py-2 text-center">
                <input type="checkbox" class="h-4 w-4" :checked="grid[r.id]?.includes(p.id)" :disabled="r.own || blocked(r, p)" :aria-label="`${r.name}: ${p.name}`" @change="toggle(r.id, p.id)" />
              </td>
            </tr>
          </template>
          <tr class="border-t border-line">
            <td class="px-4 py-3" />
            <td v-for="r in roles" :key="r.id" class="px-2 py-3 text-center"><Btn v-if="grid[r.id] && dirty(r)" size="sm" @click="save(r)">Save</Btn></td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :open="open" title="New role" @close="open = false">
      <form id="role" class="space-y-3 text-ink" @submit.prevent="create">
        <TextInput v-model="form.name" label="Role name" maxlength="60" :error="form.errors.name" />
        <TextInput v-model="form.description" label="What the role is for" optional maxlength="200" :error="form.errors.description" />
        <Notice v-if="form.errors.permissions" tone="error">{{ form.errors.permissions }}</Notice>
        <fieldset v-for="g in groups" :key="g">
          <legend class="t-label">{{ g }}</legend>
          <Checkbox v-for="p in permissions.filter((x) => x.group === g)" :key="p.id" :model-value="form.permissions.includes(p.id)" @update:model-value="pick(p.id)">{{ p.name }}</Checkbox>
        </fieldset>
      </form>
      <template #actions><Btn variant="outline" @click="open = false">Cancel</Btn><Btn type="submit" form="role" :disabled="form.processing">Create role</Btn></template>
    </Modal>
  </StaffLayout>
</template>
