<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Status from '@/Components/Status.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { dateTime } from '@/lib/format'

const props = defineProps<{ users: any[]; roles: { id: number; name: string }[] }>()
const open = ref(false)
const form = useForm({ name: '', email: '', role_id: props.roles[0]?.id, job_title: '', department: '', password: '' })
const act = (u: any, action: string, extra: Record<string, unknown> = {}) => router.put(`/internal/it/users/${u.id}`, { action, ...extra }, { preserveScroll: true })
</script>

<template>
  <StaffLayout>
    <PageHead title="Employees" sub="Employee accounts are separate from customer accounts. Every change is logged.">
      <Btn @click="open = true">Add employee</Btn>
    </PageHead>
    <DataTable caption="Employees" :rows="users"
      :columns="[{ key: 'name', label: 'Name' }, { key: 'role', label: 'Role' }, { key: 'mfa', label: 'MFA' }, { key: 'last_login_at', label: 'Last sign-in' }, { key: 'status', label: 'Status' }]">
      <template #cell-name="{ row }">{{ row.name }}<span class="block text-xs text-muted">{{ row.email }} · {{ row.staffNo }}</span></template>
      <template #cell-role="{ row }">
        <select class="input h-8 w-auto text-[13px]" :value="row.roleId" :aria-label="`Role for ${row.name}`" @change="act(row, 'role', { role_id: +($event.target as HTMLSelectElement).value })">
          <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
      </template>
      <template #cell-mfa="{ row }"><Status :s="row.mfa ? 'active' : 'pending'" /></template>
      <template #cell-last_login_at="{ row }">{{ dateTime(row.last_login_at) }}</template>
      <template #cell-status="{ row }"><Status :s="row.locked ? 'locked' : row.status" /></template>
      <template #actions="{ row }">
        <Btn v-if="row.locked" size="sm" variant="outline" @click="act(row, 'unlock')">Unlock</Btn>
        <Btn v-if="row.mfa" size="sm" variant="ghost" @click="act(row, 'reset_mfa')">Reset MFA</Btn>
        <Btn v-if="row.status === 'active'" size="sm" variant="ghost" @click="act(row, 'suspend')">Suspend</Btn>
        <Btn v-else size="sm" variant="outline" @click="act(row, 'activate')">Activate</Btn>
      </template>
    </DataTable>

    <Modal :open="open" title="Add an employee" @close="open = false">
      <form id="emp" class="space-y-3 text-ink" @submit.prevent="form.post('/internal/it/users', { preserveScroll: true, onSuccess: () => { open = false; form.reset() } })">
        <TextInput v-model="form.name" label="Full name" :error="form.errors.name" />
        <TextInput v-model="form.email" label="Work email" type="email" hint="Must end with @banyira.internal" :error="form.errors.email" />
        <div class="grid grid-cols-2 gap-3"><TextInput v-model="form.job_title" label="Job title" :error="form.errors.job_title" /><TextInput v-model="form.department" label="Department" :error="form.errors.department" /></div>
        <SelectInput v-model="form.role_id" label="Role" :options="roles.map((r) => ({ value: r.id, label: r.name }))" />
        <TextInput v-model="form.password" label="Temporary password" type="password" autocomplete="new-password" hint="At least 12 characters with upper and lower case, a number and a symbol." :error="form.errors.password" />
      </form>
      <template #actions><Btn variant="outline" @click="open = false">Cancel</Btn><Btn type="submit" form="emp" :disabled="form.processing">Create account</Btn></template>
    </Modal>
  </StaffLayout>
</template>
