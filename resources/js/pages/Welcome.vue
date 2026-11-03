<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { mk, pct } from '@/lib/format';
import { feeFor, repayments, type FeeTier } from '@/lib/loan';
import { useSiteT } from '@/lib/site-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ terms: { max_rate_bp: number; max_term_months: number; min_trading_years: number; min_records_months: number; min_commitment: number; support_phone: string | null; support_hours: string | null }; fee_tiers: FeeTier[] }>();
const terms = props.terms;
const { t } = useSiteT();
const signedIn = computed(() => !!usePage().props.auth.user);
const apply = computed(() => (signedIn.value ? route('dashboard') : route('register', { account_type: 'business_owner' })));

// The hero thread: an illustrative offer worked through the platform's own formula.
const offer = { amount: 40_000_000, months: 18, rate: 21 };
const offerPay = repayments(offer.amount, offer.months, offer.rate).pay;

// Worked example for "what it costs".
const ex = { amount: 100_000_000, months: 12, rate: 24 };
const exFee = feeFor(ex.amount, props.fee_tiers);
const exPlan = repayments(ex.amount, ex.months, ex.rate);

const benefits = computed(() => [t('home.b1', { months: terms.max_term_months }), t('home.b2'), t('home.b3'), t('home.b4', { rate: pct(terms.max_rate_bp) })]);
const uses = computed(() => [
    { id: 'growth', title: t('product.growth'), text: t('home.uses.growth') },
    { id: 'working-capital', title: t('product.wc'), text: t('home.uses.wc') },
    { id: 'equipment', title: t('product.equipment'), text: t('home.uses.equipment') },
]);
const steps = computed(() => [1, 2, 3, 4].map((n) => [t(`home.s${n}.t` as 'home.s1.t'), t(`home.s${n}.p` as 'home.s1.p')]));
const who = computed(() => [t('home.who.1', { years: terms.min_trading_years }), t('home.who.2', { months: terms.min_records_months }), t('home.who.3')]);
</script>

<template>
    <SiteLayout :title="t('home.title')">
        <!-- Hero: investors' offers reach the owner by SMS and are accepted in the account. -->
        <section class="border-b border-[#14212b]/10">
            <div class="mx-auto grid max-w-7xl gap-14 px-4 pb-16 pt-12 sm:px-6 lg:grid-cols-[1.15fr_1fr] lg:items-center lg:pb-24 lg:pt-20">
                <div>
                    <h1 class="site-display max-w-[14ch] text-balance text-[2.6rem] sm:text-6xl lg:text-[4.4rem]">{{ t('home.h1') }}</h1>
                    <p class="mt-6 max-w-[34rem] text-lg leading-relaxed text-[#14212b]/75 sm:text-xl">{{ t('home.lead') }}</p>
                    <div class="mt-9 flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-6">
                        <Link :href="apply" class="site-btn-primary px-8 py-3.5 text-base">{{ t('home.apply') }}</Link>
                        <Link :href="route('products') + '#calculator'" class="site-link self-start sm:self-auto">{{ t('home.calc') }}</Link>
                    </div>
                    <ul class="mt-10 grid max-w-xl gap-x-8 gap-y-2.5 text-[15px] sm:grid-cols-2">
                        <li v-for="b in benefits" :key="b" class="flex items-start gap-2.5"><Check class="mt-0.5 size-4 shrink-0 text-[#0a7553]" :stroke-width="3" aria-hidden="true" />{{ b }}</li>
                    </ul>
                </div>

                <!-- The journey in one phone: the SMS alert, then the offer accepted in the account. -->
                <figure class="pointer-events-none mx-auto w-full max-w-[22rem] select-none" :aria-label="t('home.sms.label')">
                    <div class="rounded-[2rem] bg-[#14212b] p-3 shadow-[0_30px_60px_-30px_rgba(20,33,43,0.6)]">
                        <div class="space-y-3 rounded-[1.5rem] bg-[#e6efee] px-4 pb-5 pt-4 text-[14.5px] leading-snug">
                            <p class="sms-in flex gap-2.5 rounded-xl bg-white px-4 py-3 shadow-sm" style="animation-delay: 0.3s">
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-[#e8b931]" aria-hidden="true" />
                                <span>{{ t('home.sms.offer', { amount: mk(offer.amount), rate: offer.rate, months: offer.months }) }}</span>
                            </p>
                            <div class="sms-in rounded-xl bg-white px-4 pb-4 pt-3 shadow-sm" style="animation-delay: 1.3s">
                                <p class="font-bold">{{ t('home.app.title') }}</p>
                                <dl class="mt-2 text-[13.5px]">
                                    <div class="flex justify-between gap-3 border-b border-[#14212b]/10 py-1.5"><dt class="text-[#14212b]/70">{{ t('home.app.amount') }}</dt><dd class="tabular font-semibold">{{ mk(offer.amount) }}</dd></div>
                                    <div class="flex justify-between gap-3 border-b border-[#14212b]/10 py-1.5"><dt class="text-[#14212b]/70">{{ t('home.app.rate') }}</dt><dd class="tabular font-semibold">{{ t('home.app.year', { rate: offer.rate }) }}</dd></div>
                                    <div class="flex justify-between gap-3 border-b border-[#14212b]/10 py-1.5"><dt class="text-[#14212b]/70">{{ t('home.app.term') }}</dt><dd class="font-semibold">{{ t('home.app.months', { months: offer.months }) }}</dd></div>
                                    <div class="flex justify-between gap-3 py-1.5"><dt class="text-[#14212b]/70">{{ t('home.app.monthly') }}</dt><dd class="tabular font-semibold">{{ mk(offerPay) }}</dd></div>
                                </dl>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs font-semibold" aria-hidden="true">
                                    <span class="rounded bg-[#0a7553]/85 py-1.5 text-center text-white">{{ t('home.app.accept') }}</span>
                                    <span class="rounded border border-[#14212b]/20 py-1.5 text-center text-[#14212b]/70">{{ t('home.app.decline') }}</span>
                                </div>
                            </div>
                            <p class="sms-in flex items-center gap-2 rounded-xl bg-[#0a7553] px-4 py-2.5 font-semibold text-white" style="animation-delay: 2.4s"><Check class="size-4 shrink-0" :stroke-width="3" aria-hidden="true" />{{ t('home.sms.done') }}</p>
                        </div>
                    </div>
                    <figcaption class="mt-4 text-center text-sm text-[#14212b]/65">{{ t('home.sms.caption') }}</figcaption>
                </figure>
            </div>
        </section>

        <!-- What the money is for -->
        <section id="products" class="scroll-mt-20">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-24">
                <h2 class="site-heading max-w-2xl text-balance text-3xl sm:text-[2.6rem]">{{ t('home.uses.h') }}</h2>
                <div class="mt-12 grid gap-10 md:grid-cols-3 md:gap-8">
                    <article v-for="u in uses" :key="u.id" class="border-t-[3px] border-[#14212b] pt-5">
                        <h3 class="site-heading text-2xl">{{ u.title }}</h3>
                        <p class="mt-3 leading-relaxed text-[#14212b]/75">{{ u.text }}</p>
                        <Link :href="route('products') + '#compare'" class="site-link mt-5 inline-block">{{ t('home.uses.more', { name: u.title.toLowerCase() }) }}</Link>
                    </article>
                </div>
            </div>
        </section>

        <!-- What it costs: a worked example instead of headline numbers -->
        <section class="bg-[#e6efee]">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-24">
                <div>
                    <h2 class="site-heading text-balance text-3xl sm:text-[2.6rem]">{{ t('home.cost.h') }}</h2>
                    <p class="mt-5 max-w-lg text-lg leading-relaxed text-[#14212b]/75">{{ t('home.cost.p1') }}</p>
                    <p class="mt-4 max-w-lg text-[#14212b]/75">{{ t('home.cost.p2', { rate: pct(terms.max_rate_bp) }) }}</p>
                </div>
                <div class="rounded-md bg-white p-6 shadow-[0_20px_50px_-30px_rgba(20,33,43,0.4)] sm:p-8">
                    <p class="font-bold">{{ t('home.cost.example', { amount: mk(ex.amount), months: ex.months, rate: ex.rate }) }}</p>
                    <dl class="mt-5 text-[15px]">
                        <div v-if="exFee" class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt>{{ t('home.cost.fee', { pct: pct(exFee.bp) }) }}</dt><dd class="tabular font-semibold">{{ mk(exFee.amount) }}</dd></div>
                        <div v-if="exFee" class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt>{{ t('home.cost.paid') }}</dt><dd class="tabular font-semibold">{{ mk(ex.amount - exFee.amount) }}</dd></div>
                        <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt>{{ t('home.cost.monthly') }}</dt><dd class="tabular font-semibold">{{ mk(exPlan.pay) }}</dd></div>
                        <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt>{{ t('home.cost.interest') }}</dt><dd class="tabular font-semibold">{{ mk(exPlan.interest) }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt>{{ t('home.cost.early') }}</dt><dd class="tabular font-semibold">MK 0</dd></div>
                    </dl>
                    <p class="mt-3 text-xs leading-relaxed text-[#14212b]/60">{{ t('home.cost.note') }}</p>
                </div>
            </div>
        </section>

        <!-- Eligibility and the process -->
        <section>
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-24">
                <div class="grid gap-12 lg:grid-cols-[1fr_1.5fr]">
                    <div>
                        <h2 class="site-heading text-3xl sm:text-[2.6rem]">{{ t('home.who.h') }}</h2>
                        <p class="mt-5 max-w-md text-lg leading-relaxed text-[#14212b]/75">{{ t('home.who.p') }}</p>
                        <ul class="mt-6 space-y-3">
                            <li v-for="x in who" :key="x" class="flex items-start gap-3 text-lg"><Check class="mt-1 size-5 shrink-0 text-[#0a7553]" :stroke-width="3" aria-hidden="true" />{{ x }}</li>
                        </ul>
                    </div>
                    <div>
                        <h2 class="sr-only">{{ t('home.steps.h') }}</h2>
                        <ol class="grid gap-x-8 gap-y-10 sm:grid-cols-2">
                            <li v-for="(s, i) in steps" :key="i" class="flex gap-5">
                                <span class="site-heading w-8 shrink-0 text-4xl text-[#0a7553]" aria-hidden="true">{{ i + 1 }}</span>
                                <div><h3 class="text-lg font-bold">{{ s[0] }}</h3><p class="mt-1 leading-relaxed text-[#14212b]/75">{{ s[1] }}</p></div>
                            </li>
                        </ol>
                        <div class="mt-12 flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-6 sm:pl-[3.25rem]">
                            <Link :href="apply" class="site-btn-primary">{{ t('home.apply') }}</Link>
                            <Link :href="route('loan-guide')" class="site-link self-start sm:self-auto">{{ t('home.guide') }}</Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Who we fund, said as a sentence -->
        <section class="border-y border-[#14212b]/10">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-20">
                <p class="site-heading max-w-5xl text-balance text-2xl leading-snug sm:text-[2.1rem] sm:leading-[1.2]">{{ t('home.fund') }}</p>
            </div>
        </section>

        <!-- Investors -->
        <section class="on-ink bg-[#14212b] text-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-16 sm:px-6 lg:grid-cols-[1.4fr_1fr] lg:items-end lg:py-20">
                <div>
                    <h2 class="site-heading text-3xl sm:text-[2.6rem]">{{ t('home.inv.h') }}</h2>
                    <p class="mt-5 max-w-xl text-lg leading-relaxed text-white/80">{{ t('home.inv.p', { min: mk(terms.min_commitment) }) }}</p>
                </div>
                <div class="flex lg:justify-end">
                    <Link :href="route('investors')" class="site-btn bg-white text-[#14212b] hover:bg-[#e6efee]">{{ t('home.inv.cta') }}</Link>
                </div>
            </div>
        </section>

        <!-- Help -->
        <section>
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-14 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <p class="text-lg"><span class="font-bold">{{ t('home.help.b') }}</span> <span class="text-[#14212b]/75">{{ t('home.help.p') }}</span></p>
                    <p class="mt-1 text-[15px]">
                        {{ t('home.help.phone') }}:
                        <a v-if="terms.support_phone" :href="`tel:${terms.support_phone.replace(/\s/g, '')}`" class="site-link">{{ terms.support_phone }}</a>
                        <span v-else class="font-semibold text-[#b23a2e]">{{ t('home.help.phoneMissing') }}</span>
                        <span v-if="terms.support_hours" class="text-[#14212b]/75">, {{ terms.support_hours }}</span>
                        <span v-else class="font-semibold text-[#b23a2e]">, {{ t('home.help.hoursMissing') }}</span>
                    </p>
                </div>
                <Link :href="route('support') + '#contact'" class="site-btn-outline self-start sm:self-auto">{{ t('home.help.cta') }}</Link>
            </div>
        </section>
    </SiteLayout>
</template>
