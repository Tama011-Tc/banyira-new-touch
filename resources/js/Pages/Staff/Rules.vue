<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import Status from '@/Components/Status.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Notice from '@/Components/Notice.vue'
import Btn from '@/Components/Btn.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import { date, mk, months } from '@/lib/format'

const props = defineProps<{ active: any; history: any[]; canPropose: boolean; pending: boolean }>()
const editing = ref(false)
const tomorrow = new Date(Date.now() + 864e5).toISOString().slice(0, 10)
const form = useForm({
  effective_from: tomorrow,
  interest_ceiling: String(Number(props.active.interest_ceiling)),
  notes: '',
  fee_tiers: props.active.fees.map((f: any) => ({ upper_bound: f.upper_bound ? String(Math.round(+f.upper_bound)) : '', rate: String(Number(f.rate)) })) as { upper_bound: string; rate: string }[],
  bands: (props.active.bands as any[]).map((b) => ({ risk_class: b.risk_class, label: b.label, min_score: b.min_score, max_amount: b.max_amount ? String(Math.round(+b.max_amount)) : '', max_months: b.max_months ?? '' })),
})
const fees = (r: any) => r.fees.map((f: any, i: number) => `${f.upper_bound ? '< ' + mk(f.upper_bound) : '≥ ' + mk(r.fees[i - 1]?.upper_bound ?? 0)}: ${Number(f.rate)}%`).join(' · ')
</script>

<template>
  <StaffLayout>
    <PageHead title="Financing rules" sub="Versioned. A change is proposed, approved by a different person, then applies from its effective date. Earlier versions are kept.">
      <Btn v-if="canPropose && !pending && !editing" @click="editing = true">Propose a change</Btn>
    </PageHead>

    <Card :title="`Active rules · ${active.version}`" :sub="`Effective ${date(active.effective_from)}`">
      <div class="grid gap-6 md:grid-cols-3">
        <div><p class="text-[13px] text-muted">Service fee tiers</p><p class="num mt-1 text-sm">{{ fees(active) }}</p><p class="mt-2 text-xs text-muted">Basis: {{ active.fee_basis }}. Collected: {{ active.fee_collection.replace(/_/g, ' ') }}. A tier applies when the total principal is below its upper limit.</p></div>
        <div><p class="text-[13px] text-muted">Investor interest ceiling</p><p class="num mt-1 text-[22px] font-semibold">{{ Number(active.interest_ceiling) }}%</p></div>
        <div>
          <p class="text-[13px] text-muted">Risk bands</p>
          <table class="mt-1 w-full text-sm"><tbody><tr v-for="b in active.bands" :key="b.risk_class"><td class="py-0.5"><RiskBadge :r="b.risk_class" /></td><td class="num py-0.5">≥ {{ b.min_score }}</td><td class="num py-0.5 text-right text-muted">{{ [b.max_amount && mk(b.max_amount), b.max_months && months(b.max_months)].filter(Boolean).join(', ') || 'no limits' }}</td></tr></tbody></table>
        </div>
      </div>
    </Card>

    <Notice v-if="pending" class="mt-5" tone="warn">A proposal is waiting for approval. New proposals are blocked until it is decided.</Notice>

    <Card v-if="editing" class="mt-5" title="Propose new rules">
      <form class="space-y-6" @submit.prevent="form.post('/internal/rules', { preserveScroll: true, onSuccess: () => (editing = false) })">
        <div class="grid gap-4 sm:grid-cols-2">
          <TextInput v-model="form.effective_from" label="Effective from" type="date" :error="form.errors.effective_from" />
          <TextInput v-model="form.interest_ceiling" label="Investor interest ceiling (% a year)" inputmode="decimal" :error="form.errors.interest_ceiling" />
        </div>
        <fieldset>
          <legend class="mb-2 text-[13px] font-medium">Service fee tiers</legend>
          <div v-for="(t, i) in form.fee_tiers" :key="i" class="mb-2 grid grid-cols-[1fr_1fr_auto] items-center gap-2">
            <input v-model="t.upper_bound" class="input" inputmode="numeric" :placeholder="i === form.fee_tiers.length - 1 ? 'No upper limit' : 'Below (MK)'" :disabled="i === form.fee_tiers.length - 1" :aria-label="`Tier ${i + 1} upper limit`" />
            <input v-model="t.rate" class="input" inputmode="decimal" placeholder="Rate %" :aria-label="`Tier ${i + 1} rate`" />
            <button v-if="form.fee_tiers.length > 1 && i < form.fee_tiers.length - 1" type="button" class="text-[13px] text-muted underline" @click="form.fee_tiers.splice(i, 1)">Remove</button><span v-else />
          </div>
          <button type="button" class="text-[13px] underline" @click="form.fee_tiers.splice(form.fee_tiers.length - 1, 0, { upper_bound: '', rate: '' })">Add tier</button>
          <p v-if="form.errors.fee_tiers" class="mt-1 text-[13px] text-danger">{{ form.errors.fee_tiers }}</p>
        </fieldset>
        <fieldset>
          <legend class="mb-2 text-[13px] font-medium">Risk bands</legend>
          <div class="overflow-x-auto"><table class="w-full min-w-[480px] text-sm">
            <thead class="text-left text-xs text-muted"><tr><th class="pb-1 font-medium">Class</th><th class="pb-1 font-medium">Minimum score</th><th class="pb-1 font-medium">Max amount (MK)</th><th class="pb-1 font-medium">Max months</th></tr></thead>
            <tbody><tr v-for="b in form.bands" :key="b.risk_class"><td class="py-1 pr-2"><RiskBadge :r="b.risk_class" /></td><td class="py-1 pr-2"><input v-model="b.min_score" class="input" inputmode="numeric" :aria-label="`${b.risk_class} minimum score`" /></td><td class="py-1 pr-2"><input v-model="b.max_amount" class="input" inputmode="numeric" placeholder="None" :aria-label="`${b.risk_class} max amount`" /></td><td class="py-1"><input v-model="b.max_months" class="input" inputmode="numeric" placeholder="None" :aria-label="`${b.risk_class} max months`" /></td></tr></tbody>
          </table></div>
        </fieldset>
        <TextArea v-model="form.notes" label="Reason for the change" :rows="2" :error="form.errors.notes" />
        <div class="flex gap-2"><Btn type="submit" :disabled="form.processing">Send for approval</Btn><Btn variant="outline" @click="editing = false">Cancel</Btn></div>
      </form>
    </Card>

    <h2 class="mb-3 mt-10 text-[15px]">History</h2>
    <ul class="divide-y divide-line rounded-lg border border-line bg-white">
      <li v-for="r in history" :key="r.id" class="grid gap-2 px-5 py-3 text-sm md:grid-cols-[120px_1fr_auto] md:items-center">
        <span class="font-mono text-[13px]">{{ r.version }}</span>
        <span><span class="num">{{ fees(r) }} · ceiling {{ Number(r.interest_ceiling) }}%</span><span class="block text-xs text-muted">{{ r.notes }} · proposed by {{ r.by ?? 'system' }}<template v-if="r.decidedBy">, decided by {{ r.decidedBy }}</template> · effective {{ date(r.effective_from) }}</span></span>
        <Status :s="r.status" />
      </li>
    </ul>
  </StaffLayout>
</template>
