<script setup lang="ts">
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import DataTable from '@/Components/DataTable.vue'
import FormGroup from '@/Components/FormGroup.vue'
import GroupRow from '@/Components/GroupRow.vue'
import Notice from '@/Components/Notice.vue'
import SelectInput from '@/Components/SelectInput.vue'
import Status from '@/Components/Status.vue'
import TextInput from '@/Components/TextInput.vue'
import Btn from '@/Components/Btn.vue'
import { dateTime, label } from '@/lib/format'

const props = defineProps<{
  employee: { id: number; name: string; email: string; status: string; last_login_at: string | null; mfa: boolean; locked: boolean; role: string | null; roleId: number | null; title: string | null; staffNo: string | null; department: string | null; createdAt: string; passwordChangedAt: string | null }
  permissions: { slug: string; name: string; group: string }[]
  roles: { id: number; name: string }[]
  sessions: number
  signIns: { id: number; ip: string; success: boolean; reason: string | null; flagged: boolean; created_at: string }[] | null
  activity: { id: number; area: string; action: string; created_at: string }[] | null
  isSelf: boolean
}>()

const url = computed(() => `/internal/it/users/${props.employee.id}`)
const groups = computed(() => [...new Set(props.permissions.map((p) => p.group))])
const error = computed(() => (usePage().props.errors as Record<string, string>).action)

const details = useForm({ name: props.employee.name, job_title: props.employee.title ?? '', department: props.employee.department ?? '' })
const role = useForm({ action: 'role', role_id: props.employee.roleId })
</script>

<template>
  <StaffLayout>
    <PageHead :title="employee.name" :sub="`${employee.email} · ${employee.staffNo ?? 'No staff number'}`" :back="{ href: '/internal/it/users', label: 'Employees' }">
      <Status :s="employee.locked ? 'locked' : employee.status" />
    </PageHead>

    <Notice v-if="isSelf" class="mb-5">This is your own account. Another administrator has to change its access; you can update your name and password under My profile.</Notice>
    <Notice v-if="error" tone="error" class="mb-5">{{ error }}</Notice>

    <div class="grid items-start gap-6 lg:grid-cols-2">
      <div class="space-y-6">
        <form @submit.prevent="details.put(`${url}/details`, { preserveScroll: true })">
          <FormGroup title="Details" padded>
            <TextInput v-model="details.name" label="Full name" :error="details.errors.name" />
            <div class="grid gap-4 sm:grid-cols-2"><TextInput v-model="details.job_title" label="Job title" :error="details.errors.job_title" /><TextInput v-model="details.department" label="Department" :error="details.errors.department" /></div>
            <Btn type="submit" :disabled="details.processing || !details.isDirty">Save details</Btn>
          </FormGroup>
        </form>

        <FormGroup v-if="!isSelf" title="Role" footer="Changing the role signs the person out so the new permissions apply." padded>
          <SelectInput v-model="role.role_id" label="Role" :options="roles.map((r) => ({ value: r.id, label: r.name }))" :error="role.errors.role_id" />
          <Btn :disabled="role.processing || role.role_id === employee.roleId" @click="role.put(url, { preserveScroll: true })">Change role</Btn>
        </FormGroup>

        <FormGroup title="Sign-in">
          <GroupRow label="Authenticator (MFA)"><Status :s="employee.mfa ? 'active' : 'pending'" /></GroupRow>
          <GroupRow label="Active sessions" :value="sessions" />
          <GroupRow label="Last sign-in" :value="dateTime(employee.last_login_at)" />
          <GroupRow label="Password last changed" :value="dateTime(employee.passwordChangedAt)" />
          <GroupRow label="Account created" :value="dateTime(employee.createdAt)" />
        </FormGroup>

        <div v-if="!isSelf" class="flex flex-wrap gap-2">
          <ConfirmAction v-if="employee.locked" :href="url" method="put" :data="{ action: 'unlock' }" title="Unlock this account?" confirm="Unlock">Unlock<template #body>They can try to sign in again straight away.</template></ConfirmAction>
          <ConfirmAction v-if="sessions" :href="url" method="put" :data="{ action: 'sign_out' }" title="Sign out everywhere?" confirm="Sign out">Sign out everywhere<template #body>Every session for this account ends now.</template></ConfirmAction>
          <ConfirmAction v-if="employee.mfa" :href="url" method="put" :data="{ action: 'reset_mfa' }" title="Reset the authenticator?" confirm="Reset MFA" danger>Reset MFA<template #body>Use this when the phone is lost. They set up a new authenticator at the next sign-in.</template></ConfirmAction>
          <ConfirmAction v-if="employee.status === 'active'" :href="url" method="put" :data="{ action: 'suspend' }" title="Suspend this account?" confirm="Suspend" variant="danger" danger>Suspend<template #body>{{ employee.name }} is signed out and cannot sign in until reactivated.</template></ConfirmAction>
          <ConfirmAction v-else :href="url" method="put" :data="{ action: 'activate' }" title="Reactivate this account?" confirm="Reactivate">Reactivate<template #body>{{ employee.name }} can sign in again.</template></ConfirmAction>
        </div>
      </div>

      <Card title="What this person can do" :sub="employee.role ?? 'No role'">
        <p v-if="!permissions.length" class="text-[14px] text-muted">No permissions.</p>
        <div v-for="g in groups" :key="g" class="mb-4 last:mb-0">
          <h3 class="t-label">{{ g }}</h3>
          <ul class="mt-1 divide-y divide-separator text-[14px]">
            <li v-for="p in permissions.filter((x) => x.group === g)" :key="p.slug" class="py-1.5">{{ p.name }}</li>
          </ul>
        </div>
      </Card>
    </div>

    <section v-if="signIns" class="mt-8">
      <h2 class="mb-3 text-[16px]">Recent sign-ins</h2>
      <DataTable caption="Recent sign-ins" empty="No sign-ins yet" :rows="signIns"
        :columns="[{ key: 'created_at', label: 'When' }, { key: 'ip', label: 'IP address' }, { key: 'success', label: 'Result' }]">
        <template #cell-created_at="{ row }">{{ dateTime(row.created_at) }}</template>
        <template #cell-success="{ row }"><Status :s="row.success ? 'success' : 'failed'" /><span v-if="row.reason" class="ml-2 text-xs text-muted">{{ label(row.reason) }}</span></template>
      </DataTable>
    </section>

    <section v-if="activity" class="mt-8">
      <h2 class="mb-3 text-[16px]">Recent activity</h2>
      <DataTable caption="Recent activity" empty="Nothing recorded yet" :rows="activity"
        :columns="[{ key: 'created_at', label: 'When' }, { key: 'area', label: 'Area' }, { key: 'action', label: 'Action' }]">
        <template #cell-created_at="{ row }">{{ dateTime(row.created_at) }}</template>
        <template #cell-area="{ row }">{{ label(row.area) }}</template>
      </DataTable>
    </section>
  </StaffLayout>
</template>
