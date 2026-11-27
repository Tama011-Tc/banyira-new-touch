<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import TableToolbar from '@/Components/TableToolbar.vue'
import Status from '@/Components/Status.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { dateTime } from '@/lib/format'
import { useTableQuery, type TableState } from '@/lib/table'
import type { Paginated } from '@/types'

const props = defineProps<{ users: Paginated<any>; query: TableState; roles: { id: number; name: string }[] }>()
const { apply, sortBy } = useTableQuery('/internal/it/users', () => props.query)
const filters = computed(() => [
  { key: 'role', label: 'Role', options: props.roles.map((r) => ({ value: String(r.id), label: r.name })) },
  { key: 'status', label: 'Status', options: [{ value: 'active', label: 'Active' }, { value: 'suspended', label: 'Suspended' }] },
])

const open = ref(false)
const form = useForm({ name: '', email: '', role_id: props.roles[0]?.id, job_title: '', department: '', password: '' })
const create = () => form.post('/internal/it/users', { preserveScroll: true, onSuccess: () => { open.value = false; form.reset() } })
</script>

<template>
  <StaffLayout>
    <PageHead title="Employees" sub="Employee accounts are separate from customer accounts. Every change is logged.">
      <Btn @click="open = true">Add employee</Btn>
    </PageHead>
    <TableToolbar :query="query" :filters="filters" search-label="Search name or email" @change="apply" />
    <DataTable caption="Employees" empty="No employees match" :rows="users.data" :order="query" @sort="sortBy"
      :columns="[{ key: 'name', label: 'Name', sort: 'name' }, { key: 'role', label: 'Role' }, { key: 'mfa', label: 'MFA' }, { key: 'last_login_at', label: 'Last sign-in', sort: 'signin' }, { key: 'status', label: 'Status' }]">
      <template #cell-name="{ row }">{{ row.name }}<span class="block text-xs text-muted">{{ row.email }} · {{ row.staffNo }}</span></template>
      <template #cell-mfa="{ row }"><Status :s="row.mfa ? 'active' : 'pending'" /></template>
      <template #cell-last_login_at="{ row }">{{ dateTime(row.last_login_at) }}</template>
      <template #cell-status="{ row }"><Status :s="row.locked ? 'locked' : row.status" /></template>
      <template #actions="{ row }"><Btn size="sm" variant="outline" :href="`/internal/it/users/${row.id}`" :label="`Manage ${row.name}`">Manage</Btn></template>
    </DataTable>
    <Pagination :page="users" />

    <Modal :open="open" title="Add an employee" @close="open = false">
      <form id="emp" class="space-y-3 text-ink" @submit.prevent="create">
        <TextInput v-model="form.name" label="Full name" :error="form.errors.name" />
        <TextInput v-model="form.email" label="Work email" type="email" hint="Must end with @banyira.internal" :error="form.errors.email" />
        <div class="grid grid-cols-2 gap-3"><TextInput v-model="form.job_title" label="Job title" :error="form.errors.job_title" /><TextInput v-model="form.department" label="Department" :error="form.errors.department" /></div>
        <SelectInput v-model="form.role_id" label="Role" :options="roles.map((r) => ({ value: r.id, label: r.name }))" :error="form.errors.role_id" />
        <TextInput v-model="form.password" label="Temporary password" type="password" autocomplete="new-password" hint="At least 12 characters with upper and lower case, a number and a symbol." :error="form.errors.password" />
      </form>
      <template #actions><Btn variant="outline" @click="open = false">Cancel</Btn><Btn type="submit" form="emp" :disabled="form.processing">Create account</Btn></template>
    </Modal>
  </StaffLayout>
</template>
