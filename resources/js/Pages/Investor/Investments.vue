<script setup lang="ts">
import { ref } from 'vue'
import InvestorLayout from '@/Layouts/InvestorLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import DataTable from '@/Components/DataTable.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Status from '@/Components/Status.vue'
import Progress from '@/Components/Progress.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import { date, mk, months } from '@/lib/format'

defineProps<{ investments: any[] }>()
const sel = ref<any>(null)
</script>

<template>
  <InvestorLayout>
    <PageHead title="My investments" sub="Investments begin when funds are disbursed to the business." />
    <DataTable caption="Investments" :rows="investments" empty="You have no investments yet. Accepted offers become investments once the agreement is signed and funds are released."
      :columns="[{ key: 'business', label: 'Business' }, { key: 'principal', label: 'Invested', align: 'right' }, { key: 'terms', label: 'Terms' }, { key: 'started', label: 'Started' }, { key: 'repaid', label: 'Principal repaid' }, { key: 'interest', label: 'Interest', align: 'right' }, { key: 'outstanding', label: 'Outstanding', align: 'right' }, { key: 'status', label: 'Status' }]">
      <template #cell-business="{ row }"><span class="flex items-center gap-2">{{ row.business }} <RiskBadge :r="row.risk" /></span></template>
      <template #cell-principal="{ row }">{{ mk(row.principal) }}</template>
      <template #cell-terms="{ row }">{{ Number(row.rate) }}% · {{ months(row.term) }}</template>
      <template #cell-started="{ row }">{{ date(row.started) }}</template>
      <template #cell-repaid="{ row }"><div class="w-28"><Progress :value="(+row.repaid / +row.principal) * 100" :label="`${row.business} principal repaid`" /><span class="num text-xs text-muted">{{ mk(row.repaid) }}</span></div></template>
      <template #cell-interest="{ row }">{{ mk(row.interest) }}</template>
      <template #cell-outstanding="{ row }">{{ mk(row.outstanding) }}</template>
      <template #cell-status="{ row }"><Status :s="row.status" /></template>
      <template #actions="{ row }"><Btn size="sm" variant="outline" @click="sel = row">Schedule</Btn></template>
    </DataTable>

    <Modal :open="!!sel" :title="sel ? `${sel.business} · ${sel.reference}` : ''" @close="sel = null">
      <div v-if="sel" class="-mx-1 max-h-[55vh] overflow-y-auto text-ink">
        <table class="w-full text-[13px]">
          <thead class="text-left text-xs text-muted"><tr><th class="pb-2 font-medium">#</th><th class="pb-2 font-medium">Due</th><th class="pb-2 text-right font-medium">Principal</th><th class="pb-2 text-right font-medium">Interest</th><th class="pb-2 font-medium">Status</th></tr></thead>
          <tbody>
            <tr v-for="s in sel.schedule" :key="s.installment_no" class="border-t border-line">
              <td class="py-1.5 text-muted">{{ s.installment_no }}</td><td class="py-1.5">{{ date(s.due_date) }}</td><td class="num py-1.5 text-right">{{ mk(s.principal_due, 2) }}</td><td class="num py-1.5 text-right">{{ mk(s.interest_due, 2) }}</td><td class="py-1.5 pl-3"><Status :s="s.status" /></td>
            </tr>
          </tbody>
        </table>
      </div>
      <template #actions><Btn variant="outline" @click="sel = null">Close</Btn></template>
    </Modal>
  </InvestorLayout>
</template>
