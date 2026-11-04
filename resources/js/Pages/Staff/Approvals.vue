<script setup lang="ts">
import { reactive, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Card from '@/Components/Card.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Modal from '@/Components/Modal.vue'
import Btn from '@/Components/Btn.vue'
import Notice from '@/Components/Notice.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import { date, mk, months } from '@/lib/format'

const props = defineProps<{ assessments: any[]; toPublish: any[]; rules: any[]; ceiling: string }>()

const decision = ref<{ a: any; approve: boolean } | null>(null)
const dForm = useForm({ approve: true, note: '' })
const openDecision = (a: any, approve: boolean) => { decision.value = { a, approve }; dForm.approve = approve; dForm.note = '' }
const decide = () => dForm.post(`/internal/approvals/assessments/${decision.value!.a.id}`, { preserveScroll: true, onSuccess: () => (decision.value = null) })

const pub = ref<any>(null)
const pForm = useForm({ public_name: '', summary: '', purpose: '', indicative_rate: '' })
const openPublish = (x: any) => { pub.value = x; Object.assign(pForm, { public_name: x.business, summary: x.summary ?? '', purpose: x.purpose, indicative_rate: '' }); pForm.clearErrors() }
const publish = () => pForm.post(`/internal/approvals/applications/${pub.value.id}/publish`, { preserveScroll: true, onSuccess: () => (pub.value = null) })

const ruleNote = reactive<Record<number, string>>({})
const decideRules = (id: number, approve: boolean) => router.post(`/internal/approvals/rules/${id}`, { approve, note: ruleNote[id] ?? '' }, { preserveScroll: true })
</script>

<template>
  <StaffLayout>
    <PageHead title="Approvals" sub="Decisions that need a second person. You cannot approve your own work." />

    <h2 class="mb-3 text-[15px]">Credit recommendations <span class="num text-muted">{{ assessments.length }}</span></h2>
    <div v-if="!assessments.length" class="rounded-lg border border-dashed border-line-2 px-6 py-8 text-center text-sm text-muted">No recommendations waiting.</div>
    <div class="space-y-4">
      <Card v-for="a in assessments" :key="a.id">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="font-mono text-[12px] text-muted">{{ a.reference }} · {{ a.industry }}</p>
            <Link :href="`/internal/credit/applications/${a.applicationId}`" class="mt-1 block text-[17px] font-medium hover:underline">{{ a.business }}</Link>
            <p class="num text-sm text-muted">{{ mk(a.amount) }} over {{ months(a.term) }}</p>
          </div>
          <div class="text-right">
            <p class="text-[13px] text-muted">Score {{ a.score }} · recommends <span class="font-medium text-ink">{{ a.recommendation }}</span></p>
            <p class="mt-1 flex items-center justify-end gap-2 text-[13px] text-muted">Class <RiskBadge :r="a.final || a.suggested" long /></p>
          </div>
        </div>
        <p class="mt-4 text-sm">{{ a.purpose }}</p>
        <p class="mt-3 rounded-md bg-[#f4f4f1] px-3 py-2 text-sm text-muted">“{{ a.comments }}” — {{ a.assessor }}, {{ date(a.submitted) }}</p>
        <p v-if="a.override" class="mt-2 text-[13px] text-maize">Class overridden from {{ a.suggested }}: {{ a.override }}</p>
        <div class="mt-4 flex gap-2">
          <template v-if="!a.mine"><Btn @click="openDecision(a, true)">Approve</Btn><Btn variant="outline" @click="openDecision(a, false)">Reject</Btn></template>
          <p v-else class="text-[13px] text-muted">You submitted this assessment, so another approver must decide.</p>
        </div>
      </Card>
    </div>

    <h2 class="mb-3 mt-10 text-[15px]">Ready to publish <span class="num text-muted">{{ toPublish.length }}</span></h2>
    <div v-if="!toPublish.length" class="rounded-lg border border-dashed border-line-2 px-6 py-8 text-center text-sm text-muted">No approved applications waiting to be listed.</div>
    <ul v-else class="divide-y divide-line rounded-lg border border-line bg-white">
      <li v-for="x in toPublish" :key="x.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
        <span><span class="font-medium">{{ x.business }}</span> <span class="ml-2 font-mono text-[13px] text-muted">{{ x.reference }}</span><span class="block text-[13px] text-muted">{{ mk(x.amount) }} · {{ months(x.term) }}</span></span>
        <span class="flex items-center gap-3"><RiskBadge :r="x.risk" /><Btn size="sm" @click="openPublish(x)">Publish</Btn></span>
      </li>
    </ul>

    <h2 class="mb-3 mt-10 text-[15px]">Financing rule changes <span class="num text-muted">{{ rules.length }}</span></h2>
    <div v-if="!rules.length" class="rounded-lg border border-dashed border-line-2 px-6 py-8 text-center text-sm text-muted">No rule changes proposed.</div>
    <Card v-for="r in rules" :key="r.id" :title="`Proposal ${r.version}`" :sub="`By ${r.by}, effective ${date(r.effective_from)}`">
      <p class="text-sm">{{ r.notes }}</p>
      <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
        <div><dt class="text-muted">Interest ceiling</dt><dd class="num">{{ Number(r.interest_ceiling) }}%</dd></div>
        <div><dt class="text-muted">Fee tiers</dt><dd class="num">{{ r.fees.map((f: any) => (f.upper_bound ? '< ' + mk(f.upper_bound) : 'above') + ': ' + Number(f.rate) + '%').join(' · ') }}</dd></div>
        <div><dt class="text-muted">Band limits</dt><dd class="num">{{ r.bands.map((b: any) => b.risk_class + (b.max_months ? ' ' + b.max_months + 'mo' : '') + (b.max_amount ? ' ' + mk(b.max_amount) : '')).join(' · ') }}</dd></div>
      </dl>
      <div v-if="!r.mine" class="mt-4 flex flex-wrap items-end gap-2">
        <input v-model="ruleNote[r.id]" class="input max-w-xs" placeholder="Note (optional)" aria-label="Decision note" />
        <Btn @click="decideRules(r.id, true)">Approve</Btn><Btn variant="outline" @click="decideRules(r.id, false)">Reject</Btn>
      </div>
      <p v-else class="mt-4 text-[13px] text-muted">You proposed this change, so another approver must decide.</p>
    </Card>

    <Modal :open="!!decision" :title="decision?.approve ? 'Approve this application?' : 'Reject this application?'" @close="decision = null">
      <p v-if="decision?.approve" class="text-muted">{{ decision.a.business }} will be approved with class {{ decision.a.final || decision.a.suggested }}. You can then publish it to investors.</p>
      <p v-else class="text-muted">The business will be told the application was not approved.</p>
      <div class="mt-4"><TextArea v-model="dForm.note" :label="decision?.approve ? 'Note' : 'Reason (shown to the business)'" :optional="decision?.approve" :rows="3" :error="dForm.errors.note ?? (dForm.errors as Record<string, string>).decision" /></div>
      <template #actions><Btn variant="outline" @click="decision = null">Cancel</Btn><Btn :variant="decision?.approve ? 'primary' : 'danger'" :disabled="dForm.processing" @click="decide">{{ decision?.approve ? 'Approve' : 'Reject' }}</Btn></template>
    </Modal>

    <Modal :open="!!pub" title="Publish to the marketplace" @close="pub = null">
      <form id="pub" class="space-y-4 text-ink" @submit.prevent="publish">
        <Notice>Investors see only what you enter here, plus amount, class, industry, region and period. Never include contact details or registration numbers.</Notice>
        <TextInput v-model="pForm.public_name" label="Public name" :error="pForm.errors.public_name" />
        <TextArea v-model="pForm.summary" label="Business summary" :rows="3" :error="pForm.errors.summary" />
        <TextInput v-model="pForm.purpose" label="Financing purpose" :error="pForm.errors.purpose" />
        <TextInput v-model="pForm.indicative_rate" label="Indicative annual rate (%)" inputmode="decimal" :hint="`A guide for investors. Maximum ${Number(ceiling)}%.`" :error="pForm.errors.indicative_rate" />
      </form>
      <template #actions><Btn variant="outline" @click="pub = null">Cancel</Btn><Btn type="submit" form="pub" :disabled="pForm.processing">Publish</Btn></template>
    </Modal>
  </StaffLayout>
</template>
