<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { mk, pct } from '@/lib/format';
import { feeFor, feeRange, repayments, type FeeTier } from '@/lib/loan';
import { useCopy } from '@/lib/site-i18n';
import { Link } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

const props = defineProps<{
    terms: { max_rate_bp: number; max_term_months: number; max_grace: number };
    fee_tiers: FeeTier[];
}>();
const t = props.terms;
const apply = route('register', { account_type: 'business_owner' });
const rate = pct(t.max_rate_bp);
const range = feeRange(props.fee_tiers);

const c = useCopy({
    en: {
        title: 'Business loans',
        h1: 'Business loans built around your business',
        lead: 'One loan with the same fair rules, shaped around what you need the money for.',
        rules: [
            `Interest is set by investor offers and is never above ${rate} a year`,
            range ? `One Banyira fee of ${range} by loan size, taken from the money paid out` : 'No platform fee is approved yet, so none is charged',
            'No collateral, and nothing to pay for repaying early',
            'You borrow what your business can comfortably repay',
        ],
        compareH: 'Compare the three uses',
        compareCaption: 'Typical terms. Your actual term is agreed in each offer.',
        rowFor: 'Good for',
        rowTerm: 'Typical term',
        rowRepay: 'Repayments',
        rowExtra: 'Worth knowing',
        products: [
            { id: 'growth', name: 'Growth loans', for: 'A second shop or stall, hiring and training staff, delivering a large order', term: `6 to ${t.max_term_months} months`, repay: 'Monthly', extra: `Up to ${t.max_grace} months interest-only while the new investment starts earning` },
            { id: 'working-capital', name: 'Working capital', for: 'Seasonal stock and inputs, paying suppliers on time, covering slow-paying customers', term: '3 to 12 months', repay: 'Weekly or monthly', extra: 'Weekly repayments to match how money comes in' },
            { id: 'equipment', name: 'Equipment loans', for: 'Maize mills, motorcycles and delivery vehicles, solar, sewing and workshop equipment', term: `12 to ${t.max_term_months} months`, repay: 'Monthly', extra: 'Repayments spread over the working life of the asset' },
        ],
        applyNow: 'Apply now',
        calcH: 'What could my loan cost?',
        calcP: 'Move the sliders to see an example. Your actual rate depends on the offers investors make.',
        amount: 'How much do you need?',
        term: 'Over how long?',
        months: 'months',
        exRate: 'Example interest rate',
        aYear: 'a year',
        ceiling: 'our ceiling',
        repay: 'Repay',
        monthly: 'Monthly',
        weekly: 'Weekly',
        eachMonth: 'Each month you would repay',
        eachWeek: 'Each week you would repay',
        nPayments: (n: number, weekly: boolean) => `${n} ${weekly ? 'weekly' : 'monthly'} repayments`,
        totalInterest: 'Total interest',
        totalRepaid: 'Total repaid',
        fee: (p: string) => `Banyira fee (${p})`,
        receive: 'You receive',
        note: 'Example only, using reducing-balance interest. It is not an offer. The amount you can borrow and your rate depend on our credit check and on the offers investors make. Repaying early never costs extra.',
    },
    ny: {
        title: 'Ngongole za bizinesi',
        h1: 'Ngongole za bizinesi zokonzedwa malinga ndi bizinesi yanu',
        lead: 'Ngongole imodzi yokhala ndi malamulo achilungamo omwewo, yokonzedwa malinga ndi zomwe mukufuna ndalamazo.',
        rules: [
            `Chiwongola dzanja chimakhazikitsidwa ndi zopereka za obwereketsa ndipo sichiposa ${rate} pa chaka`,
            range ? `Chindapusa chimodzi cha Banyira cha ${range} malinga ndi kukula kwa ngongole, chochotsedwa pa ndalama zoperekedwa` : 'Palibe chindapusa chovomerezeka pakadali pano, choncho palibe cholipiritsidwa',
            'Palibe chikole, ndipo palibe cholipira pobweza msanga',
            'Mumabwereka zomwe bizinesi yanu ingabweze mosavuta',
        ],
        compareH: 'Yerekezerani ntchito zitatu',
        compareCaption: 'Mfundo zofala. Nthawi yanu yeniyeni imagwirizanidwa m\'zopereka zilizonse.',
        rowFor: 'Zoyenera',
        rowTerm: 'Nthawi yofala',
        rowRepay: 'Malipiro',
        rowExtra: 'Zofunika kudziwa',
        products: [
            { id: 'growth', name: 'Ngongole zokulitsa bizinesi', for: 'Shopu kapena golosale yachiwiri, kulemba ndi kuphunzitsa antchito, kupereka oda yaikulu', term: `Miyezi 6 mpaka ${t.max_term_months}`, repay: 'Mwezi uliwonse', extra: `Mpaka miyezi ${t.max_grace} yolipira chiwongola dzanja chokha pamene ndalama zatsopano zikuyamba kubala` },
            { id: 'working-capital', name: 'Ndalama zogulira katundu', for: 'Katundu ndi zolowa za nyengo, kulipira ogulitsa pa nthawi, makasitomala ochedwa kulipira', term: 'Miyezi 3 mpaka 12', repay: 'Sabata iliyonse kapena mwezi uliwonse', extra: 'Malipiro a sabata iliyonse ogwirizana ndi momwe ndalama zimalowera' },
            { id: 'equipment', name: 'Ngongole za zipangizo', for: 'Zigayo, njinga zamoto ndi magalimoto onyamula katundu, sola, makina osokera ndi zida', term: `Miyezi 12 mpaka ${t.max_term_months}`, repay: 'Mwezi uliwonse', extra: 'Malipiro ogawidwa pa nthawi yomwe chipangizocho chikugwira ntchito' },
        ],
        applyNow: 'Funsirani tsopano',
        calcH: 'Ngongole yanga ingandidye zingati?',
        calcP: 'Sunthani zowongolera kuti muone chitsanzo. Chiwongola dzanja chanu chenicheni chimadalira zopereka za obwereketsa.',
        amount: 'Mukufuna ndalama zingati?',
        term: 'Kwa nthawi yaitali bwanji?',
        months: 'miyezi',
        exRate: 'Chitsanzo cha chiwongola dzanja',
        aYear: 'pa chaka',
        ceiling: 'malire athu',
        repay: 'Bwezani',
        monthly: 'Mwezi uliwonse',
        weekly: 'Sabata iliyonse',
        eachMonth: 'Mwezi uliwonse mungabweze',
        eachWeek: 'Sabata iliyonse mungabweze',
        nPayments: (n: number, weekly: boolean) => `Malipiro ${n} a ${weekly ? 'sabata' : 'mwezi'}`,
        totalInterest: 'Chiwongola dzanja chonse',
        totalRepaid: 'Zonse zobwezedwa',
        fee: (p: string) => `Chindapusa cha Banyira (${p})`,
        receive: 'Mumalandira',
        note: 'Ichi ndi chitsanzo chabe, chogwiritsa ntchito chiwongola dzanja cha ngongole yotsala. Si zopereka. Ndalama zomwe mungabwereke ndi chiwongola dzanja zimadalira kuunika kwathu ndi zopereka za obwereketsa. Kubweza msanga sikulipiritsidwa.',
    },
});

// Calculator: one control updates fee, payout, repayment and total together.
const calc = reactive({ amount: 1_000_000, months: 12, rate: Math.min(24, t.max_rate_bp / 100), weekly: false });
const fee = computed(() => feeFor(calc.amount * 100, props.fee_tiers));
const plan = computed(() => repayments(calc.amount * 100, calc.months, calc.rate, calc.weekly));
const rows = ['for', 'term', 'repay', 'extra'] as const;
const rowLabel = computed(() => ({ for: c.value.rowFor, term: c.value.rowTerm, repay: c.value.rowRepay, extra: c.value.rowExtra }));
</script>

<template>
    <SiteLayout :title="c.title">
        <section class="border-b border-[#14212b]/10">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.2fr_1fr] lg:items-end lg:py-20">
                <div>
                    <h1 class="site-display text-balance text-[2.6rem] sm:text-6xl">{{ c.h1 }}</h1>
                    <p class="mt-5 max-w-xl text-lg leading-relaxed text-[#14212b]/75">{{ c.lead }}</p>
                </div>
                <ul class="space-y-3">
                    <li v-for="r in c.rules" :key="r" class="flex items-start gap-3"><Check class="mt-0.5 size-5 shrink-0 text-[#0a7553]" :stroke-width="3" aria-hidden="true" />{{ r }}</li>
                </ul>
            </div>
        </section>

        <section id="compare" class="scroll-mt-20 py-14 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <h2 class="site-heading text-balance text-3xl sm:text-[2.6rem]">{{ c.compareH }}</h2>
                <p class="mt-3 text-[#14212b]/75">{{ c.compareCaption }}</p>

                <!-- Wide screens: one ruled comparison table. -->
                <table class="mt-10 hidden w-full table-fixed border-collapse text-left md:table">
                    <thead>
                        <tr>
                            <td class="w-44" />
                            <th v-for="p in c.products" :key="p.id" scope="col" class="border-t-[3px] border-[#14212b] px-4 pb-4 pt-5 align-top"><span class="site-heading block text-2xl">{{ p.name }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="text-[15px] leading-relaxed">
                        <tr v-for="row in rows" :key="row" class="border-t border-[#14212b]/10">
                            <th scope="row" class="py-4 pr-4 align-top font-semibold text-[#14212b]/70">{{ rowLabel[row] }}</th>
                            <td v-for="p in c.products" :key="p.id" class="px-4 py-4 align-top">{{ p[row] }}</td>
                        </tr>
                        <tr class="border-t border-[#14212b]/10">
                            <td />
                            <td v-for="p in c.products" :key="p.id" class="px-4 pt-6"><Link :href="apply" class="site-btn-primary">{{ c.applyNow }}</Link></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Phones: the same rows, one product after another. -->
                <div class="mt-8 space-y-10 md:hidden">
                    <section v-for="p in c.products" :key="p.id" class="border-t-[3px] border-[#14212b] pt-5">
                        <h3 class="site-heading text-2xl">{{ p.name }}</h3>
                        <dl class="mt-3 text-[15px] leading-relaxed">
                            <div class="border-b border-[#14212b]/10 py-3"><dt class="font-semibold text-[#14212b]/70">{{ c.rowFor }}</dt><dd>{{ p.for }}</dd></div>
                            <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt class="shrink-0 font-semibold text-[#14212b]/70">{{ c.rowTerm }}</dt><dd class="text-right">{{ p.term }}</dd></div>
                            <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt class="shrink-0 font-semibold text-[#14212b]/70">{{ c.rowRepay }}</dt><dd class="text-right">{{ p.repay }}</dd></div>
                            <div class="py-3"><dt class="font-semibold text-[#14212b]/70">{{ c.rowExtra }}</dt><dd>{{ p.extra }}</dd></div>
                        </dl>
                        <Link :href="apply" class="site-btn-primary mt-2 w-full">{{ c.applyNow }}</Link>
                    </section>
                </div>
            </div>
        </section>

        <section id="calculator" class="on-ink scroll-mt-20 bg-[#14212b] py-14 text-white lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <h2 class="site-heading text-balance text-3xl sm:text-[2.6rem]">{{ c.calcH }}</h2>
                <p class="mt-3 max-w-2xl text-lg text-white/75">{{ c.calcP }}</p>
                <div class="mt-10 grid gap-8 lg:grid-cols-[1.2fr_1fr]">
                    <form class="space-y-8 rounded-md border border-white/15 p-6 sm:p-8" @submit.prevent>
                        <div>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4"><label for="amt" class="font-semibold">{{ c.amount }}</label><output for="amt" class="tabular text-2xl font-extrabold">{{ mk(calc.amount * 100) }}</output></div>
                            <input id="amt" v-model.number="calc.amount" type="range" min="100000" max="10000000" step="50000" class="mt-3 w-full" />
                            <div class="mt-1 flex justify-between text-xs text-white/60"><span>MK 100,000</span><span>MK 10,000,000</span></div>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4"><label for="term" class="font-semibold">{{ c.term }}</label><output for="term" class="tabular text-2xl font-extrabold">{{ calc.months }} {{ c.months }}</output></div>
                            <input id="term" v-model.number="calc.months" type="range" min="3" :max="t.max_term_months" step="1" class="mt-3 w-full" />
                            <div class="mt-1 flex justify-between text-xs text-white/60"><span>3 {{ c.months }}</span><span>{{ t.max_term_months }} {{ c.months }}</span></div>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4"><label for="rate" class="font-semibold">{{ c.exRate }}</label><output for="rate" class="tabular text-2xl font-extrabold">{{ calc.rate }}% {{ c.aYear }}</output></div>
                            <input id="rate" v-model.number="calc.rate" type="range" min="5" :max="t.max_rate_bp / 100" step="0.5" class="mt-3 w-full" />
                            <div class="mt-1 flex justify-between text-xs text-white/60"><span>5%</span><span>{{ rate }} ({{ c.ceiling }})</span></div>
                        </div>
                        <fieldset>
                            <legend class="font-semibold">{{ c.repay }}</legend>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <label v-for="opt in [{ v: false, l: c.monthly }, { v: true, l: c.weekly }]" :key="String(opt.v)" class="cursor-pointer rounded-md py-2.5 text-center font-semibold ring-1 ring-white/25 has-[:checked]:bg-white has-[:checked]:text-[#14212b] has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-[#e8b931]"><input v-model="calc.weekly" type="radio" :value="opt.v" class="sr-only" />{{ opt.l }}</label>
                            </div>
                        </fieldset>
                    </form>

                    <div class="flex flex-col rounded-md bg-white p-6 text-[#14212b] sm:p-8" aria-live="polite">
                        <p class="text-[#14212b]/75">{{ calc.weekly ? c.eachWeek : c.eachMonth }}</p>
                        <p class="tabular mt-1 text-5xl font-extrabold tracking-tight">{{ mk(plan.pay) }}</p>
                        <p class="mt-1 text-sm text-[#14212b]/65">{{ c.nPayments(plan.n, calc.weekly) }}</p>
                        <dl class="mt-6 text-[15px]">
                            <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-2.5"><dt>{{ c.totalInterest }}</dt><dd class="tabular font-semibold">{{ mk(plan.interest) }}</dd></div>
                            <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-2.5"><dt>{{ c.totalRepaid }}</dt><dd class="tabular font-semibold">{{ mk(plan.total) }}</dd></div>
                            <div v-if="fee" class="flex justify-between gap-4 border-b border-[#14212b]/10 py-2.5"><dt>{{ c.fee(pct(fee.bp)) }}</dt><dd class="tabular font-semibold">{{ mk(fee.amount) }}</dd></div>
                            <div v-if="fee" class="flex justify-between gap-4 py-2.5"><dt>{{ c.receive }}</dt><dd class="tabular font-bold text-[#0a7553]">{{ mk(calc.amount * 100 - fee.amount) }}</dd></div>
                        </dl>
                        <div class="grow" />
                        <Link :href="apply" class="site-btn-primary mt-6">{{ c.applyNow }}</Link>
                        <p class="mt-4 text-xs leading-relaxed text-[#14212b]/65">{{ c.note }}</p>
                    </div>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
