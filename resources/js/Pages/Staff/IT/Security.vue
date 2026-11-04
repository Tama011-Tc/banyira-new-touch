<script setup lang="ts">
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import Status from '@/Components/Status.vue'
import Notice from '@/Components/Notice.vue'
import { dateTime, label } from '@/lib/format'

defineProps<{ attempts: any; locked: any[] }>()
</script>

<template>
  <StaffLayout>
    <PageHead title="Security and sign-ins" sub="Every sign-in attempt for customers and employees. Flagged rows show repeated failures from one address or an employee signing in from a new address." />
    <Notice v-if="locked.length" tone="warn" title="Locked accounts">
      <span v-for="u in locked" :key="u.id" class="mr-4">{{ u.name }} ({{ label(u.type) }}) until {{ dateTime(u.locked_until) }}</span>
    </Notice>
    <div class="mt-5">
      <DataTable caption="Sign-in attempts" :rows="attempts.data"
        :columns="[{ key: 'created_at', label: 'When' }, { key: 'guard', label: 'Portal' }, { key: 'email', label: 'Email' }, { key: 'ip', label: 'IP address' }, { key: 'success', label: 'Result' }, { key: 'flagged', label: '' }]">
        <template #cell-created_at="{ row }">{{ dateTime(row.created_at) }}</template>
        <template #cell-guard="{ row }">{{ row.guard === 'staff' ? 'Employee' : 'Customer' }}</template>
        <template #cell-ip="{ row }"><span class="font-mono text-[13px]">{{ row.ip }}</span></template>
        <template #cell-success="{ row }"><Status :s="row.success ? 'success' : 'failed'" /><span v-if="row.reason" class="ml-2 text-xs text-muted">{{ label(row.reason) }}</span></template>
        <template #cell-flagged="{ row }"><span v-if="row.flagged" class="text-xs font-medium text-danger">Flagged</span></template>
      </DataTable>
      <Pagination :page="attempts" />
    </div>
  </StaffLayout>
</template>
