<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import Status from '@/components/pw/Status.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { method, mk, pct } from '@/lib/format';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Flag, ShieldAlert } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ conversation: { id: number; title: string; listing_id: number; with: string; role: string }; sms_confirm: boolean; messages: { id: number; mine: boolean; body: string; status: string; at: string; flags: string[] }[]; offer: { versions: Record<string, any>[]; latest_id: number | null; status: string | null; agreement_id: number | null }; limits: Record<string, any>; rules: string }>();
const msg = useForm({ body: '' });
const send = () => msg.post(route('conversations.send', props.conversation.id), { preserveScroll: true, onSuccess: () => msg.reset('body') });
const latest = props.offer.versions.at(-1);
const proposal = useForm({ principal: ((latest?.principal ?? props.limits.min) / 100).toFixed(2), rate: (latest?.rate_bp ?? props.limits.rate_bp) / 100, term_months: latest?.term_months ?? props.limits.term_months, frequency: latest?.frequency ?? props.limits.frequency, interest_method: latest?.interest_method ?? props.limits.method, grace_instalments: latest?.grace ?? 0, note: '' });
const reporting = ref(false);
const report = useForm({ reason: 'off_platform', detail: '' });
const appealNote = ref('');
const code = ref('');
const codeSent = ref(false);
const sendCode = (vid: number) => router.post(route('conversations.sms-code', [props.conversation.id, vid]), {}, { preserveScroll: true, onSuccess: () => (codeSent.value = true) });
const accept = (vid: number) => router.post(route('conversations.accept', [props.conversation.id, vid]), { sms_code: code.value }, { preserveScroll: true });
const canAct = (v: Record<string, any>) => v.status === 'proposed' && !v.by_me && v.id === props.offer.latest_id;
</script>

<template>
    <Head :title="conversation.title" />
    <AppLayout>
        <PageHeader :title="conversation.title" :description="`Conversation with ${conversation.with}`"><Button variant="ghost" size="sm" @click="reporting = !reporting"><Flag class="size-4" aria-hidden="true" /> Report</Button><Button variant="outline" size="sm" as-child><Link v-if="conversation.role === 'investor'" :href="route('marketplace.show', conversation.listing_id)">View listing</Link></Button></PageHeader>

        <form v-if="reporting" class="mb-6 grid max-w-xl gap-3 rounded-lg border bg-card p-4" @submit.prevent="report.post(route('conversations.report', conversation.id), { onSuccess: () => (reporting = false) })">
            <FormField label="What is the problem?" for="rr" :error="report.errors.reason"><SelectField id="rr" v-model="report.reason"><option value="off_platform">Asked to move outside the platform</option><option value="suspicious">Suspicious request</option><option value="fraud">Possible fraud</option><option value="abuse">Abuse or harassment</option><option value="other">Something else</option></SelectField></FormField>
            <FormField label="Details" for="rd"><textarea id="rd" v-model="report.detail" rows="2" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
            <Button type="submit" size="sm" class="w-fit">Send report to moderators</Button>
        </form>

        <div class="grid gap-6 lg:grid-cols-5">
            <section class="flex flex-col lg:col-span-3">
                <p class="mb-3 flex gap-2 rounded-lg bg-muted p-3 text-xs text-muted-foreground"><ShieldAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ rules }}</p>
                <ul class="flex min-h-[18rem] flex-1 flex-col gap-3 rounded-lg border bg-card p-4" aria-live="polite">
                    <li v-for="m in messages" :key="m.id" class="max-w-[85%]" :class="m.mine ? 'self-end' : 'self-start'">
                        <div class="rounded-lg px-3 py-2 text-sm" :class="m.mine ? 'bg-primary text-primary-foreground' : 'bg-muted'">{{ m.body || 'Message held by moderators' }}</div>
                        <p class="mt-1 text-xs text-muted-foreground" :class="m.mine ? 'text-right' : ''">{{ m.at }}
                            <template v-if="m.mine && m.status !== 'delivered' && m.status !== 'released'"> · <Status :value="m.status" /></template>
                        </p>
                        <div v-if="m.mine && m.status === 'blocked'" class="mt-1 rounded-md border border-gold/50 bg-gold/10 p-2 text-xs"><p>Not delivered: it looks like it contains {{ m.flags.map((f) => f.replace('_', ' ')).join(', ') }}. If this is a mistake:</p><div class="mt-1 flex gap-2"><Input v-model="appealNote" placeholder="Why this is fine" class="h-8 text-xs" /><Button size="sm" variant="outline" @click="router.post(route('messages.appeal', m.id), { note: appealNote })">Appeal</Button></div></div>
                        <p v-if="m.mine && m.status === 'pending_review'" class="mt-1 text-xs text-muted-foreground">Waiting for a moderator to review.</p>
                    </li>
                    <li v-if="!messages.length" class="m-auto text-sm text-muted-foreground">No messages yet. Introduce yourself and ask your questions.</li>
                </ul>
                <form class="mt-3 flex gap-2" @submit.prevent="send">
                    <label class="sr-only" for="body">Message</label>
                    <textarea id="body" v-model="msg.body" rows="2" maxlength="2000" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Write a message" />
                    <Button type="submit" :disabled="msg.processing || !msg.body.trim()">Send</Button>
                </form>
                <p v-if="msg.errors.body" class="mt-1 text-sm text-destructive">{{ msg.errors.body }}</p>
            </section>

            <aside class="space-y-4 lg:col-span-2">
                <section class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">Offers</h2>
                    <p class="text-xs text-muted-foreground">Each change is a new version. An agreement exists only when both parties accept the same latest version.</p>
                    <div v-if="offer.agreement_id" class="mt-3 rounded-md bg-accent p-3 text-sm">Agreed. <Link :href="route('agreements.show', offer.agreement_id)" class="font-medium text-primary hover:underline">View the simulated agreement and schedule</Link></div>
                    <ol class="mt-4 space-y-3">
                        <li v-for="v in [...offer.versions].reverse()" :key="v.id" class="rounded-md border p-3 text-sm" :class="v.id === offer.latest_id && v.status === 'proposed' ? 'border-primary' : 'opacity-80'">
                            <div class="flex items-center justify-between"><strong>Version {{ v.version }}</strong><Status :value="v.status" /></div>
                            <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs"><dt class="text-muted-foreground">Amount</dt><dd>{{ mk(v.principal) }}</dd><dt class="text-muted-foreground">Rate</dt><dd>{{ pct(v.rate_bp) }} a year</dd><dt class="text-muted-foreground">Term</dt><dd>{{ v.term_months }} months, {{ v.frequency }}</dd><dt class="text-muted-foreground">Method</dt><dd>{{ method(v.interest_method) }}</dd><dt class="text-muted-foreground">Grace</dt><dd>{{ v.grace }} instalment(s)</dd><dt class="text-muted-foreground">Fees</dt><dd v-if="v.fees.approved">borrower {{ pct(v.fees.origination_bp) }}{{ v.fees.origination_fixed ? ' + ' + mk(v.fees.origination_fixed) : '' }} · investor {{ pct(v.fees.investor_service_bp) }}{{ v.fees.investor_service_fixed ? ' + ' + mk(v.fees.investor_service_fixed) : '' }}</dd><dd v-else>none (no approved fee schedule)</dd></dl>
                            <p v-if="v.note" class="mt-2 text-xs italic">“{{ v.note }}”</p>
                            <p v-if="v.status === 'proposed' && v.expires_at" class="mt-2 text-xs text-muted-foreground">Open until {{ v.expires_at }}</p>
                            <p class="mt-2 text-xs text-muted-foreground">Accepted by: {{ v.investor_accepted ? 'investor' : '' }}{{ v.investor_accepted && v.business_accepted ? ' and ' : '' }}{{ v.business_accepted ? 'business' : '' }}{{ !v.investor_accepted && !v.business_accepted ? 'nobody yet' : '' }} · #{{ v.hash }}</p>
                            <div v-if="v.status === 'proposed' && v.by_me && v.id === offer.latest_id" class="mt-3"><Button size="sm" variant="outline" @click="router.post(route('conversations.withdraw', [conversation.id, v.id]))">Withdraw my proposal</Button></div>
                            <div v-if="canAct(v)" class="mt-3 flex gap-2"><template v-if="sms_confirm"><Button v-if="!codeSent" size="sm" @click="sendCode(v.id)">Accept: text me a code</Button><template v-else><Input v-model="code" inputmode="numeric" maxlength="6" placeholder="6-digit code" class="h-8 w-32" aria-label="SMS code" /><Button size="sm" :disabled="code.length !== 6" @click="accept(v.id)">Confirm</Button></template></template><Button v-else size="sm" @click="accept(v.id)">Accept these terms</Button><Button size="sm" variant="outline" @click="router.post(route('conversations.decline', [conversation.id, v.id]))">Decline</Button></div>
                        </li>
                        <li v-if="!offer.versions.length" class="text-sm text-muted-foreground">No proposal yet.</li>
                    </ol>
                </section>
                <section v-if="!offer.agreement_id" class="rounded-lg border bg-card p-5">
                    <h2 class="font-semibold">{{ offer.versions.length ? 'Counter-propose' : 'Propose terms' }}</h2>
                    <p class="text-xs text-muted-foreground">Allowed: {{ mk(limits.min) }}–{{ mk(limits.max) }}, rate up to {{ pct(limits.max_rate_bp) }}, term up to {{ limits.term_months }} months.</p>
                    <form class="mt-3 space-y-3" @submit.prevent="proposal.post(route('conversations.propose', conversation.id), { preserveScroll: true })">
                        <FormField label="Amount (MK)" for="pp" :error="proposal.errors.principal"><Input id="pp" v-model="proposal.principal" inputmode="decimal" /></FormField>
                        <div class="grid grid-cols-2 gap-3">
                            <FormField label="Rate (% a year)" for="pr" :error="proposal.errors.rate"><Input id="pr" v-model.number="proposal.rate" type="number" step="0.01" /></FormField>
                            <FormField label="Term (months)" for="pt" :error="proposal.errors.term_months"><Input id="pt" v-model.number="proposal.term_months" type="number" min="1" /></FormField>
                            <FormField label="Repayments" for="pf"><SelectField id="pf" v-model="proposal.frequency"><option value="monthly">Monthly</option><option value="weekly">Weekly</option></SelectField></FormField>
                            <FormField label="Grace (instalments)" for="pg" :error="proposal.errors.grace"><Input id="pg" v-model.number="proposal.grace_instalments" type="number" min="0" max="3" /></FormField>
                        </div>
                        <FormField label="Interest method" for="pm"><SelectField id="pm" v-model="proposal.interest_method"><option value="reducing">Reducing balance</option><option value="flat">Flat rate</option></SelectField></FormField>
                        <FormField label="Note (optional)" for="pn" :error="proposal.errors.note"><Input id="pn" v-model="proposal.note" /></FormField>
                        <Button type="submit" class="w-full" :disabled="proposal.processing">Send proposal</Button>
                    </form>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
