<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { mk, pct } from '@/lib/format';
import { useCopy } from '@/lib/site-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import { TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ terms: { max_rate_bp: number; min_commitment: number; bands: { code: string; name: string; definition: string }[] } }>();
const t = props.terms;
const signedIn = computed(() => !!usePage().props.auth.user);
const start = computed(() => (signedIn.value ? route('dashboard') : route('register', { account_type: 'investor' })));
const min = mk(t.min_commitment);
const rate = pct(t.max_rate_bp);

const c = useCopy({
    en: {
        title: 'Invest in Malawian businesses',
        h1: 'Put your money behind real businesses',
        lead: 'Lend directly to vetted Malawian small businesses, choose your own rate, and see exactly where your money goes.',
        create: 'Create an investor account',
        account: 'Go to my account',
        readRisks: 'Read the risks',
        atRisk: 'Your capital is at risk. Returns are not guaranteed.',
        termsH: 'The terms, plainly',
        tMin: 'Smallest amount per business',
        tRate: 'Highest rate you can offer',
        tRateV: `${rate} a year`,
        tFee: 'Banyira fee for investors',
        tFeeV: 'None',
        tPay: 'Repayments to your bank',
        tPayV: 'Monthly',
        howH: 'How investing works',
        steps: [
            ['Browse vetted businesses', 'Every listing has passed identity checks and a credit review. See what the money is for, the terms and the risks.'],
            ['Offer your rate', `Make an offer on the amount and interest rate you want, up to ${rate} a year. The owner accepts or declines.`],
            ['Get repaid monthly', 'Repayments from the business are paid to your bank account. Track every payment in your portfolio.'],
        ],
        bandsH: 'Understand the risk before you lend',
        bandsP: 'Every listed business gets a risk band from our credit team. Businesses below band C are not listed at all.',
        bandsNote: 'A risk band is an estimate made at the time of assessment. It does not guarantee repayment.',
        features: [
            ['Spread your risk', `Lend from ${min} per business and build a portfolio across sectors and districts.`],
            ['Honest reporting', 'Your portfolio separates money actually received from projected interest. We never show projections as income.'],
            ['Protected negotiation', 'All conversations stay on the platform. Contact details are never shared in either direction.'],
        ],
        risksH: 'Key risks',
        risks: ['Businesses may repay late, in part or not at all', 'You can lose some or all of the money you lend', 'Loans cannot normally be withdrawn early', 'Lending to few businesses concentrates your risk'],
        understand: 'I understand, create my account',
        allRisks: 'Read all the risks',
    },
    ny: {
        title: 'Bwereketsani mabizinesi a ku Malawi',
        h1: 'Ikani ndalama zanu m\'mabizinesi enieni',
        lead: 'Bwereketsani mwachindunji mabizinesi ang\'onoang\'ono a ku Malawi omwe aunikidwa, sankhani chiwongola dzanja chanu, ndipo onani bwino lomwe komwe ndalama zanu zikupita.',
        create: 'Tsegulani akaunti ya obwereketsa',
        account: 'Pitani ku akaunti yanga',
        readRisks: 'Werengani zoopsa',
        atRisk: 'Ndalama zanu zili pa chiopsezo. Phindu silotsimikizika.',
        termsH: 'Mfundo zake, mosavuta',
        tMin: 'Ndalama zochepa kwambiri pa bizinesi iliyonse',
        tRate: 'Chiwongola dzanja chapamwamba chomwe mungapereke',
        tRateV: `${rate} pa chaka`,
        tFee: 'Chindapusa cha Banyira kwa obwereketsa',
        tFeeV: 'Palibe',
        tPay: 'Malipiro ku banki yanu',
        tPayV: 'Mwezi uliwonse',
        howH: 'Momwe kubwereketsa kumagwirira ntchito',
        steps: [
            ['Onani mabizinesi omwe aunikidwa', 'Bizinesi iliyonse yadutsa kuunikidwa kwa umunthu ndi ngongole. Onani cholinga cha ndalama, mfundo zake ndi zoopsa.'],
            ['Perekani chiwongola dzanja chanu', `Perekani ndalama ndi chiwongola dzanja chomwe mukufuna, mpaka ${rate} pa chaka. Mwini bizinesi amavomera kapena kukana.`],
            ['Landirani malipiro mwezi uliwonse', 'Malipiro ochokera ku bizinesi amapita ku akaunti yanu ya banki. Tsatirani malipiro onse mu portfolio yanu.'],
        ],
        bandsH: 'Mvetsetsani chiopsezo musanabwereketse',
        bandsP: 'Bizinesi iliyonse yomwe ili pa msika imapatsidwa gulu la chiopsezo ndi gulu lathu la ngongole. Mabizinesi a pansi pa gulu C saikidwa konse.',
        bandsNote: 'Gulu la chiopsezo ndi kuyerekeza kopangidwa pa nthawi yowunika. Silitsimikiza kuti ngongole idzabwezedwa.',
        features: [
            ['Gawani chiopsezo chanu', `Bwereketsani kuyambira ${min} pa bizinesi iliyonse ndipo mangani portfolio m'magawo ndi maboma osiyanasiyana.`],
            ['Malipoti oona', 'Portfolio yanu imalekanitsa ndalama zomwe mwalandiradi ndi chiwongola dzanja choyembekezeka. Sitionetsa zoyembekezeka ngati ndalama zomwe mwapeza.'],
            ['Kukambirana kotetezedwa', 'Zokambirana zonse zimachitikira pa nsanja. Manambala olumikizirana sagawidwa mbali iliyonse.'],
        ],
        risksH: 'Zoopsa zazikulu',
        risks: ['Mabizinesi angabweze mochedwa, pang\'ono kapena osabweza konse', 'Mutha kutaya gawo kapena ndalama zonse zomwe mwabwereketsa', 'Ngongole sizingatulutsidwe msanga nthawi zambiri', 'Kubwereketsa mabizinesi ochepa kumaonjezera chiopsezo'],
        understand: 'Ndamvetsa, tsegulani akaunti yanga',
        allRisks: 'Werengani zoopsa zonse',
    },
});

// Same order as the app's Risk badge: lowest risk first.
const bandTone = ['bg-[#0a7553]', 'bg-[#1f7a8c]', 'bg-[#8a6400]', 'bg-[#b23a2e]'];
</script>

<template>
    <SiteLayout :title="c.title">
        <section class="on-ink bg-[#14212b] text-white">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:py-24">
                <div>
                    <h1 class="site-display text-balance text-[2.6rem] sm:text-6xl">{{ c.h1 }}</h1>
                    <p class="mt-5 max-w-xl text-lg text-white/75 sm:text-xl">{{ c.lead }}</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link :href="start" class="site-btn-primary">{{ signedIn ? c.account : c.create }}</Link>
                        <Link :href="route('risks')" class="site-btn bg-white text-[#14212b] hover:bg-[#e6efee]">{{ c.readRisks }}</Link>
                    </div>
                    <p class="mt-5 flex items-center gap-2 text-sm text-white/85"><TriangleAlert class="size-4 shrink-0" aria-hidden="true" />{{ c.atRisk }}</p>
                </div>
                <div class="rounded-md bg-white p-6 text-[#14212b] sm:p-8">
                    <p class="font-bold">{{ c.termsH }}</p>
                    <dl class="mt-4 text-[15px]">
                        <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt>{{ c.tMin }}</dt><dd class="tabular shrink-0 font-semibold">{{ min }}</dd></div>
                        <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt>{{ c.tRate }}</dt><dd class="tabular shrink-0 font-semibold">{{ c.tRateV }}</dd></div>
                        <div class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3"><dt>{{ c.tFee }}</dt><dd class="shrink-0 font-semibold">{{ c.tFeeV }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt>{{ c.tPay }}</dt><dd class="shrink-0 font-semibold">{{ c.tPayV }}</dd></div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="py-14 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <h2 class="site-heading text-balance text-3xl sm:text-[2.6rem]">{{ c.howH }}</h2>
                <ol class="mt-10 grid gap-10 md:grid-cols-3 md:gap-8">
                    <li v-for="([title, text], i) in c.steps" :key="i" class="flex gap-5">
                        <span class="site-heading w-8 shrink-0 text-4xl text-[#0a7553]" aria-hidden="true">{{ i + 1 }}</span>
                        <div><h3 class="text-lg font-bold">{{ title }}</h3><p class="mt-1 leading-relaxed text-[#14212b]/75">{{ text }}</p></div>
                    </li>
                </ol>
            </div>
        </section>

        <section class="bg-[#e6efee] py-14 lg:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_1.4fr]">
                <div>
                    <h2 class="site-heading text-balance text-3xl sm:text-[2.6rem]">{{ c.bandsH }}</h2>
                    <p class="mt-4 max-w-md text-lg leading-relaxed text-[#14212b]/75">{{ c.bandsP }}</p>
                </div>
                <div>
                    <dl class="rounded-md bg-white px-6 sm:px-8">
                        <div v-for="(b, i) in t.bands" :key="b.code" class="flex gap-5 border-b border-[#14212b]/10 py-5 last:border-0">
                            <dt class="shrink-0"><span class="inline-flex h-8 min-w-10 items-center justify-center rounded px-2 font-extrabold text-white" :class="bandTone[i] ?? 'bg-[#5b6670]'">{{ b.code }}</span></dt>
                            <dd><p class="font-bold">{{ b.name }}</p><p class="mt-0.5 text-[15px] leading-relaxed text-[#14212b]/75">{{ b.definition }}</p></dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-sm text-[#14212b]/75">{{ c.bandsNote }}</p>
                </div>
            </div>
        </section>

        <section class="py-14 lg:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 md:grid-cols-3 md:gap-8">
                <div v-for="[title, text] in c.features" :key="title" class="border-t-[3px] border-[#14212b] pt-5">
                    <h3 class="site-heading text-2xl">{{ title }}</h3>
                    <p class="mt-2 leading-relaxed text-[#14212b]/75">{{ text }}</p>
                </div>
            </div>
        </section>

        <section class="px-4 pb-16 sm:px-6 lg:pb-24">
            <div class="mx-auto max-w-7xl rounded-md border-t-[3px] border-[#14212b] bg-[#e6efee] p-7 sm:p-10">
                <h2 class="site-heading text-2xl">{{ c.risksH }}</h2>
                <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                    <li v-for="r in c.risks" :key="r" class="flex gap-3"><span class="mt-2.5 h-0.5 w-3 shrink-0 bg-[#14212b]" aria-hidden="true" />{{ r }}</li>
                </ul>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <Link :href="start" class="site-btn-ink">{{ signedIn ? c.account : c.understand }}</Link>
                    <Link :href="route('risks')" class="site-btn-outline">{{ c.allRisks }}</Link>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
