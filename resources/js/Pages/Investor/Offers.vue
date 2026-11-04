<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Tabs from '@/Components/Tabs.vue'
import DataTable from '@/Components/DataTable.vue'
import Status from '@/Components/Status.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import Btn from '@/Components/Btn.vue'
import { date, mk, months } from '@/lib/format'

const props = defineProps<{ offers: any[]; tab: string }>()
const tab = ref(props.tab)
const tabs = computed(() => ['all', 'pending', 'accepted', 'rejected', 'withdrawn', 'expired'].map((k) => ({ key: k, label: k[0].toUpperCase() + k.slice(1), count: k === 'all' ? undefined : props.offers.filter((o) => o.status === k).length || undefined })))
const rows = computed(() => props.offers.filter((o) => tab.value === 'all' || o.status === tab.value))
</script>

<template>
  <InvestorLayout>
    <PageHead title="My offers" sub="An offer is not an investment until it is accepted, signed and funded.">
      <Btn href="/investor/marketplace">Make a new offer</Btn>
    </PageHead>
    <Tabs v-model="tab" :tabs="tabs" />
    <div class="mt-4">
      <DataTable caption="Offers" :rows="rows" empty="No offers here."
        :columns="[{ key: 'business', label: 'Business' }, { key: 'amount', label: 'Amount', align: 'right' }, { key: 'terms', label: 'Rate and period' }, { key: 'created', label: 'Made' }, { key: 'status', label: 'Status' }]">
        <template #cell-business="{ row }"><Link :href="`/investor/marketplace/${row.listingId}`" class="flex items-center gap-2 hover:underline">{{ row.business }} <RiskBadge :r="row.risk" /></Link></template>
        <template #cell-amount="{ row }">{{ mk(row.amount) }}</template>
        <template #cell-terms="{ row }">{{ Number(row.rate) }}% · {{ months(row.term) }}</template>
        <template #cell-created="{ row }">{{ date(row.created) }}</template>
        <template #cell-status="{ row }"><Status :s="row.status" /><p v-if="row.status === 'pending'" class="text-xs text-muted">expires {{ date(row.expires) }}</p></template>
        <template #actions="{ row }">
          <ConfirmAction v-if="row.status === 'pending'" :href="`/investor/offers/${row.id}/withdraw`" danger title="Withdraw this offer?" confirm="Withdraw offer">
            Withdraw
            <template #body>The business will no longer see it. You can make a new offer while the listing is open.</template>
          </ConfirmAction>
          <Btn v-if="row.status === 'accepted'" size="sm" variant="outline" href="/investor/agreements">Agreement</Btn>
        </template>
      </DataTable>
    </div>
  </InvestorLayout>
</template>
