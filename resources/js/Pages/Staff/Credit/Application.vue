<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import StaffLayout from '@/Layouts/StaffLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Tabs from '@/Components/Tabs.vue'
import Card from '@/Components/Card.vue'
import Stat from '@/Components/Stat.vue'
import Status from '@/Components/Status.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import Notice from '@/Components/Notice.vue'
import Btn from '@/Components/Btn.vue'
import TextArea from '@/Components/TextArea.vue'
import ConfirmAction from '@/Components/ConfirmAction.vue'
import BarChart from '@/Components/BarChart.vue'
import { date, dateTime, label, mk, mkShort, months } from '@/lib/format'
import { useCan } from '@/lib/permissions'

const props = defineProps<{ application: any; assessment: any | null; rules: { version: string; bands: any[] }; can: { approve: boolean } }>()
const app = props.application
const a = props.assessment
// Approvers can open an application without access to the credit queue; send them back to Approvals.
const can = useCan()
const back = can('applications.assess') ? { href: '/internal/credit', label: 'Queue' } : { href: '/internal/approvals', label: 'Approvals' }
const editable = computed(() => a?.status === 'draft' && a.mine)
const tab = ref(a ? 'scoring' : 'business')
const tabs = [
  { key: 'business', label: 'Business' }, { key: 'documents', label: 'Documents', count: app.documents.length }, { key: 'financials', label: 'Financials' },
  { key: 'scoring', label: 'Assessment' }, { key: 'history', label: 'History' },
]

const CHECKS: [string, string][] = [
  ['identity', 'Owner identity matches national ID'], ['registration', 'Registration certificate matches the registry'], ['address', 'Business address confirmed'],
  ['statements', 'Six months of statements received'], ['inflows_reconcile', 'Inflows reconcile with stated turnover'], ['quotation', 'Quotation or plan supports the amount requested'],
]
const form = useForm({
  scores: { ...(a?.scores ?? {}) } as Record<number, number>,
  notes: { ...(a?.notes ?? {}) } as Record<number, string>,
  checks: Object.fromEntries(CHECKS.map(([k]) => [k, !!a?.checks?.[k]])) as Record<string, boolean>,
  risks: [...(a?.risks ?? []), ''] as string[],
  final_class: a?.final_class ?? '',
  override_reason: a?.override_reason ?? '',
  recommendation: a?.recommendation ?? '',
  comments: a?.comments ?? '',
  submit_now: false,
})
const score = computed(() => {
  if (!a) return null
  if (a.criteria.some((c: any) => !form.scores[c.id])) return null
  return Math.round(a.criteria.reduce((t: number, c: any) => t + (form.scores[c.id] / 5) * c.weight, 0) * 100) / 100
})
const suggested = computed(() => score.value === null ? null : (props.rules.bands.find((b: any) => score.value! >= b.min_score)?.risk_class ?? null))
const finalClass = computed(() => form.final_class || suggested.value)
const breach = computed(() => {
  const b = props.rules.bands.find((x: any) => x.risk_class === finalClass.value)
  if (!b) return null
  const out = []
  if (b.max_amount && +app.amount > +b.max_amount) out.push(`maximum ${mk(b.max_amount)}`)
  if (b.max_months && app.term_months > b.max_months) out.push(`maximum ${months(b.max_months)}`)
  return out.length ? `Class ${finalClass.value} is limited to ${out.join(' and ')}.` : null
})
const save = (submit = false) => {
  form.submit_now = submit
  form.transform((d) => ({ ...d, final_class: d.final_class || null, risks: d.risks.filter(Boolean) })).put(`/internal/credit/assessments/${a.id}`, { preserveScroll: true })
}
const info = useForm({ message: '' })
const chart = app.records.map((r: any) => ({ l: r.period.split(' ')[0], v: +r.inflow, v2: +r.outflow }))
</script>

<template>
  <StaffLayout>
    <PageHead :title="app.business.legal_name" :sub="`${app.reference} · ${app.product.name} · ${mk(app.amount)} over ${months(app.term_months)}`" :back="back">
      <ConfirmAction v-if="app.status === 'submitted'" :href="`/internal/credit/applications/${app.id}/review`" variant="primary" size="md" title="Take this application?" confirm="Assign to me">
        Assign to me
        <template #body>It moves to “Under review” and you become the responsible officer.</template>
      </ConfirmAction>
      <Btn v-if="app.status === 'under_review' && app.assignedToMe" @click="router.post(`/internal/credit/applications/${app.id}/assessment`, {}, { preserveScroll: true })">Start assessment</Btn>
    </PageHead>

    <div class="mb-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
      <Status :s="app.statusLabel" />
      <span class="text-muted">Officer: {{ app.assignee ?? 'unassigned' }}</span>
      <span class="text-muted">Submitted {{ date(app.submitted_at) }}</span>
      <span v-if="app.risk_class" class="flex items-center gap-2 text-muted">Class <RiskBadge :r="app.risk_class" /></span>
    </div>

    <Tabs v-model="tab" :tabs="tabs" />

    <div class="mt-6">
      <!-- Business -->
      <div v-if="tab === 'business'" class="grid gap-5 lg:grid-cols-2">
        <Card title="Business">
          <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div v-for="[k, l] in [['legal_name', 'Registered name'], ['trading_name', 'Trading name'], ['registration_number', 'Registration no.'], ['business_type', 'Type'], ['industry', 'Industry'], ['years_operating', 'Years operating'], ['employees', 'Employees'], ['region', 'Region'], ['district', 'District'], ['address', 'Address']]" :key="k">
              <dt class="text-muted">{{ l }}</dt><dd>{{ app.business[k] ?? '—' }}</dd>
            </div>
          </dl>
          <p class="mt-4 border-t border-line pt-4 text-sm">{{ app.business.description }}</p>
        </Card>
        <div class="space-y-5">
          <Card title="Owner and verification">
            <dl class="space-y-2 text-sm"><div class="flex justify-between"><dt class="text-muted">Owner</dt><dd>{{ app.business.owner.name }}</dd></div><div class="flex justify-between"><dt class="text-muted">Email</dt><dd>{{ app.business.owner.email }}</dd></div><div class="flex justify-between"><dt class="text-muted">Phone</dt><dd>{{ app.business.owner.phone }}</dd></div></dl>
            <ul class="mt-4 divide-y divide-line border-t border-line text-sm"><li v-for="v in app.business.verifications" :key="v.kind" class="flex justify-between py-2"><span>{{ label(v.kind) }}</span><Status :s="v.status" /></li></ul>
          </Card>
          <Card title="Request"><p class="text-sm">{{ app.purpose }}</p><p class="mt-3 text-[13px] text-muted">Product limits: {{ mk(app.product.min_amount) }}–{{ mk(app.product.max_amount) }}, {{ app.product.min_months }}–{{ months(app.product.max_months) }}.</p></Card>
        </div>
      </div>

      <!-- Documents -->
      <div v-if="tab === 'documents'">
        <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
          <li v-for="d in app.documents" :key="d.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
            <div><a :href="`/internal/credit/documents/${d.id}`" class="hover:underline">{{ d.type }}</a><p class="text-[13px] text-muted">{{ d.original_name }} · {{ Math.ceil(d.size / 1024) }} KB · {{ date(d.created_at) }}</p></div>
            <div class="flex items-center gap-3">
              <Status :s="d.status" />
              <template v-if="d.status === 'pending'">
                <Btn size="sm" variant="outline" @click="router.post(`/internal/credit/documents/${d.id}/review`, { status: 'accepted' }, { preserveScroll: true })">Accept</Btn>
                <Btn size="sm" variant="ghost" @click="router.post(`/internal/credit/documents/${d.id}/review`, { status: 'rejected' }, { preserveScroll: true })">Reject</Btn>
              </template>
            </div>
          </li>
        </ul>
        <p class="mt-2 text-xs text-muted">Opening a document is recorded in the audit log.</p>
        <Card v-if="['under_review', 'assessment_in_progress'].includes(app.status)" class="mt-6" title="Request more information">
          <form class="space-y-3" @submit.prevent="info.post(`/internal/credit/applications/${app.id}/information`, { preserveScroll: true, onSuccess: () => info.reset() })">
            <TextArea v-model="info.message" label="What do you need from the business?" :rows="3" :error="info.errors.message" hint="Sent by SMS and email. The application waits until the business responds." />
            <Btn type="submit" variant="outline" :disabled="info.processing">Send request</Btn>
          </form>
        </Card>
        <div v-if="app.requests.length" class="mt-6 space-y-2">
          <div v-for="r in app.requests" :key="r.id" class="rounded-lg border border-line bg-surface p-4 text-sm">
            <div class="flex justify-between gap-3"><p>“{{ r.message }}”</p><Status :s="r.status" /></div>
            <p class="mt-1 text-xs text-muted">{{ r.by }} · {{ dateTime(r.created_at) }}</p>
            <p v-if="r.response" class="mt-3 rounded-md bg-fill px-3 py-2">{{ r.response }}</p>
          </div>
        </div>
      </div>

      <!-- Financials -->
      <div v-if="tab === 'financials'" class="space-y-6">
        <div class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
          <Stat label="Average monthly inflow" :value="mkShort(app.cash.avgIn)" />
          <Stat label="Average monthly outflow" :value="mkShort(app.cash.avgOut)" />
          <Stat label="Instalment at ceiling rate" :value="app.cash.instalment ? mkShort(app.cash.instalment) : '—'" note="Worst case" />
          <Stat label="Debt service cover" :value="app.cash.cover !== null ? app.cash.cover + '×' : '—'" :warn="app.cash.cover !== null && app.cash.cover < 1.2" note="Net inflow ÷ instalment" />
        </div>
        <Card title="Monthly records (from business)"><BarChart v-if="chart.length" label="Money in and out" :data="chart" :legend="['Money in', 'Money out']" :fmt="(n) => mkShort(n)" /><p v-else class="text-sm text-muted">No records provided.</p></Card>
      </div>

      <!-- Scoring -->
      <div v-if="tab === 'scoring'">
        <Notice v-if="!a">The assessment starts once the application is under review and assigned to an officer.</Notice>
        <div v-else class="grid gap-6 lg:grid-cols-[1fr_300px]">
          <div class="space-y-5">
            <Notice v-if="a.status !== 'draft'" :tone="a.status === 'approved' ? 'ok' : a.status === 'rejected' ? 'error' : 'info'" :title="a.status === 'submitted' ? 'Awaiting decision' : 'Decided'">
              Recommended {{ a.recommendation }} by {{ a.assessor }} on {{ date(a.submitted_at) }}.<template v-if="a.decider"> {{ label(a.status) }} by {{ a.decider }}<template v-if="a.decision_note">: “{{ a.decision_note }}”</template>.</template>
            </Notice>
            <Notice v-else-if="!a.mine" tone="warn">This assessment belongs to {{ a.assessor }}. You can read it but not change it.</Notice>

            <Card title="Verification checklist">
              <label v-for="[k, l] in CHECKS" :key="k" class="flex items-center gap-3 border-b border-line py-2 text-sm last:border-0">
                <input v-model="form.checks[k]" type="checkbox" class="h-4 w-4" :disabled="!editable" />{{ l }}
              </label>
            </Card>

            <Card :title="`Scoring · criteria ${a.criteriaVersion}`" sub="Rate each criterion from 1 (weak) to 5 (strong). Weights add up to 100.">
              <div class="space-y-5">
                <div v-for="c in a.criteria" :key="c.id">
                  <div class="flex items-baseline justify-between gap-3"><p class="text-sm font-medium">{{ c.label }} <span class="font-normal text-muted">· {{ c.weight }}%</span></p><span class="num text-sm">{{ form.scores[c.id] ?? '–' }} / 5</span></div>
                  <p class="text-[13px] text-muted">{{ c.guidance }}</p>
                  <div class="mt-2 flex gap-1" role="radiogroup" :aria-label="c.label">
                    <button v-for="s in 5" :key="s" type="button" role="radio" :aria-checked="form.scores[c.id] === s" :disabled="!editable"
                      class="h-9 w-11 rounded border text-sm" :class="form.scores[c.id] === s ? 'border-ink bg-ink text-surface' : 'border-line-2 bg-surface hover:border-ink'" @click="form.scores[c.id] = s">{{ s }}</button>
                  </div>
                  <input v-if="editable || form.notes[c.id]" v-model="form.notes[c.id]" class="input mt-2 h-9" placeholder="Evidence or note (optional)" :aria-label="`Note for ${c.label}`" :disabled="!editable" />
                </div>
              </div>
              <p v-if="form.errors.scores" class="mt-3 text-[13px] text-danger">{{ form.errors.scores }}</p>
            </Card>

            <Card title="Risks identified">
              <div class="space-y-2">
                <input v-for="(r, i) in form.risks" :key="i" v-model="form.risks[i]" class="input" :placeholder="i === form.risks.length - 1 ? 'Add a risk' : ''" :aria-label="`Risk ${i + 1}`" :disabled="!editable" @input="i === form.risks.length - 1 && form.risks[i] && form.risks.push('')" />
              </div>
            </Card>

            <Card title="Recommendation">
              <div class="space-y-4">
                <div class="flex gap-2">
                  <label v-for="r in ['approve', 'reject']" :key="r" class="flex flex-1 cursor-pointer items-center gap-2 rounded-md border px-3 py-2.5 text-sm" :class="form.recommendation === r ? 'border-ink' : 'border-line'">
                    <input v-model="form.recommendation" type="radio" :value="r" :disabled="!editable" />{{ r === 'approve' ? 'Recommend approval' : 'Recommend rejection' }}
                  </label>
                </div>
                <p v-if="form.errors.recommendation" class="text-[13px] text-danger">{{ form.errors.recommendation }}</p>
                <div v-if="form.recommendation === 'approve'" class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label for="final" class="mb-1.5 block text-[13px] font-medium">Risk class</label>
                    <select id="final" v-model="form.final_class" class="input" :disabled="!editable"><option value="">Use suggested ({{ suggested ?? 'none' }})</option><option v-for="b in rules.bands" :key="b.risk_class" :value="b.risk_class">{{ b.risk_class }} · {{ b.label }}</option></select>
                    <p v-if="form.errors.final_class" class="mt-1 text-[13px] text-danger">{{ form.errors.final_class }}</p>
                  </div>
                  <TextArea v-if="form.final_class && form.final_class !== suggested" v-model="form.override_reason" label="Reason for override" :rows="2" :error="form.errors.override_reason" :disabled="!editable" />
                </div>
                <Notice v-if="form.recommendation === 'approve' && breach" tone="warn">{{ breach }} The business must reduce its request, or the application should be rejected.</Notice>
                <TextArea v-model="form.comments" label="Assessment comments" :rows="4" :error="form.errors.comments" :disabled="!editable" />
              </div>
            </Card>

            <div v-if="editable" class="flex flex-wrap gap-2">
              <Btn variant="outline" :disabled="form.processing" @click="save(false)">Save draft</Btn>
              <Btn :disabled="form.processing || score === null || !form.recommendation || !form.comments.trim() || (form.recommendation === 'approve' && (!finalClass || !!breach))" @click="save(true)">Submit recommendation</Btn>
            </div>
          </div>

          <aside class="space-y-5 lg:sticky lg:top-6 lg:self-start">
            <Card title="Result">
              <p class="text-[13px] text-muted">Weighted score</p>
              <p class="num text-[32px] font-semibold tracking-tight">{{ score ?? '—' }}<span class="text-base font-normal text-muted"> / 100</span></p>
              <p class="mt-3 text-[13px] text-muted">Suggested class</p>
              <p class="mt-1"><RiskBadge v-if="suggested" :r="suggested" long /><span v-else class="text-sm text-muted">{{ score === null ? 'Score every criterion' : 'Below the lowest class' }}</span></p>
            </Card>
            <Card :title="`Risk bands · ${rules.version}`">
              <table class="w-full text-[13px]"><tbody>
                <tr v-for="b in rules.bands" :key="b.risk_class" class="border-t border-line first:border-0"><td class="py-1.5"><RiskBadge :r="b.risk_class" /></td><td class="num py-1.5 text-right">≥ {{ b.min_score }}</td><td class="num py-1.5 text-right text-muted">{{ [b.max_amount && mkShort(b.max_amount), b.max_months && b.max_months + ' mo'].filter(Boolean).join(' · ') || '—' }}</td></tr>
              </tbody></table>
              <p class="mt-3 text-xs text-muted">A rating is an assessment, not a guarantee of repayment.</p>
            </Card>
          </aside>
        </div>
      </div>

      <!-- History -->
      <Card v-if="tab === 'history'" flush>
        <ol class="divide-y divide-line">
          <li v-for="(h, i) in app.history" :key="i" class="flex justify-between gap-4 px-5 py-3 text-sm">
            <div><Status :s="h.to" /><p v-if="h.note" class="mt-0.5 text-[13px] text-muted">{{ h.note }}</p></div>
            <span class="text-right text-xs text-muted">{{ h.by ?? 'System' }}<br />{{ dateTime(h.at) }}</span>
          </li>
        </ol>
      </Card>
    </div>
  </StaffLayout>
</template>
