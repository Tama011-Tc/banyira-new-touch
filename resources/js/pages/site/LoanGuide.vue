<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { mk, pct } from '@/lib/format';
import { useCopy } from '@/lib/site-i18n';
import { Link } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';

const props = defineProps<{ terms: { max_rate_bp: number; min_trading_years: number; min_records_months: number; bands: { code: string; name: string; definition: string; max_amount: number | null; max_term_months: number | null }[] } }>();
const t = props.terms;
const apply = route('register', { account_type: 'business_owner' });
const rate = pct(t.max_rate_bp);

// Documents mirror config('banyira.documents'): ID, registration and statements are required.
const c = useCopy({
    en: {
        title: 'How to get a loan',
        h1: 'From application to funding in six clear steps',
        lead: 'No collateral, no hidden charges and no surprises. Here is exactly what happens.',
        start: 'Start your application',
        steps: [
            ['Create your account', 'Sign up as a business owner with your email and phone number. Prefer help? One of our team can apply with you.'],
            ['Tell us about your business', 'Your sector, district, how long you have traded, what the money is for and how you will repay it. You can save and finish later.'],
            ['Upload your documents', 'Photos or scans are fine. Only our credit and compliance staff can see them.'],
            ['Credit review', 'An analyst assesses your records and gives your business a risk band. A different person approves or declines, and always tells you why.'],
            ['Your business is listed', 'Investors see what the money is for, the terms and the risks. Your contact details are never shared.'],
            ['Accept offers in your account', 'We text you when an investor makes an offer. Sign in to read it in full, with the fee and every repayment, then accept or decline. The money is released once the loan is agreed.'],
        ],
        whoH: 'Who can apply',
        who: [`Your business has been trading for at least ${t.min_trading_years} year`, `You can show at least ${t.min_records_months} months of financial records`, 'The business operates in Malawi', 'The owner has a national ID', 'Repayment comes from business income, not collateral'],
        docsH: 'What you will need',
        docs: [
            ['National ID of the owner', true],
            ['Business registration certificate', true],
            [`Bank statements covering at least ${t.min_records_months} months`, true],
            ['Financial statements or sales records', false],
            ['Tax clearance certificate', false],
            ['A phone number in your name for SMS alerts', true],
        ] as [string, boolean][],
        required: 'Required',
        helpful: 'Helpful if you have it',
        files: 'PDF, JPG or PNG files up to 5 MB each. Phone photos are fine.',
        decideH: 'How we decide',
        decideP: 'We look at how your business trades and how much it can comfortably repay. Every approved business gets one of these risk bands, which investors see.',
        limit: 'Limit',
        upTo: 'up to',
        months: 'months',
        rateNote: `Rates are offered by investors and never exceed ${rate} a year. Every offer is accepted or declined in your Banyira account.`,
        readyH: 'Ready when you are',
        readyP: 'Applying is free and there is no obligation to accept any offer.',
        applyNow: 'Apply now',
        ask: 'Ask a question',
    },
    ny: {
        title: 'Momwe mungapezere ngongole',
        h1: 'Kuchokera pofunsira mpaka kulandira ndalama m\'masitepe asanu ndi limodzi',
        lead: 'Palibe chikole, palibe zolipira zobisika ndipo palibe zodabwitsa. Izi ndi zomwe zimachitika.',
        start: 'Yambani kufunsira',
        steps: [
            ['Tsegulani akaunti yanu', 'Lembetsani ngati mwini bizinesi ndi imelo ndi nambala ya foni. Mukufuna thandizo? M\'modzi wa gulu lathu angafunsire nanu.'],
            ['Tiuzeni za bizinesi yanu', 'Gawo lanu, boma, nthawi yomwe mwakhala mukuchita malonda, cholinga cha ndalama ndi momwe mudzabwezere. Mutha kusunga ndi kumaliza pambuyo pake.'],
            ['Kwezani zikalata zanu', 'Zithunzi kapena zosikaniwa ndizovomerezeka. Ogwira ntchito a ngongole ndi oyang\'anira malamulo okha ndi omwe angaziwone.'],
            ['Kuunika ngongole', 'Katswiri amawunika zolemba zanu ndikupatsa bizinesi yanu gulu la chiopsezo. Munthu wina amavomera kapena kukana, ndipo amakuuzani chifukwa chake nthawi zonse.'],
            ['Bizinesi yanu imaikidwa pa msika', 'Obwereketsa amaona cholinga cha ndalama, mfundo zake ndi zoopsa. Manambala anu olumikizirana sagawidwa.'],
            ['Vomerani zopereka mu akaunti yanu', 'Timakutumizirani uthenga wobwereketsa akapereka zopereka. Lowani kuti muwerenge zonse, ndi chindapusa ndi malipiro onse, kenako vomerani kapena kanani. Ndalama zimatulutsidwa ngongole ikagwirizanidwa.'],
        ],
        whoH: 'Omwe angafunsire',
        who: ['Bizinesi yanu yakhala ikuchita malonda kwa chaka chimodzi kapena kuposa', `Muli ndi zolemba za ndalama za miyezi ${t.min_records_months} kapena kuposa`, 'Bizinesi ikugwira ntchito ku Malawi', 'Mwini bizinesi ali ndi chiphaso cha dziko', 'Kubweza kumachokera ku ndalama za bizinesi, osati chikole'],
        docsH: 'Zomwe mudzafunikira',
        docs: [
            ['Chiphaso cha dziko cha mwini bizinesi', true],
            ['Satifiketi yolembetsa bizinesi', true],
            [`Masitatimenti a banki a miyezi ${t.min_records_months} kapena kuposa`, true],
            ['Zolemba za ndalama kapena za malonda', false],
            ['Satifiketi ya msonkho', false],
            ['Nambala ya foni ya dzina lanu yolandirira mauthenga', true],
        ] as [string, boolean][],
        required: 'Chofunikira',
        helpful: 'Chothandiza ngati muli nacho',
        files: 'Mafayilo a PDF, JPG kapena PNG mpaka 5 MB iliyonse. Zithunzi za foni ndizovomerezeka.',
        decideH: 'Momwe timasankhira',
        decideP: 'Timaona momwe bizinesi yanu ikuchitira malonda ndi kuchuluka komwe ingabweze mosavuta. Bizinesi iliyonse yovomerezedwa imapatsidwa limodzi la magulu a chiopsezo awa, omwe obwereketsa amaona.',
        limit: 'Malire',
        upTo: 'mpaka',
        months: 'miyezi',
        rateNote: `Chiwongola dzanja chimaperekedwa ndi obwereketsa ndipo sichiposa ${rate} pa chaka. Zopereka zilizonse zimavomerezedwa kapena kukanidwa mu akaunti yanu ya Banyira.`,
        readyH: 'Takonzeka nthawi yomwe mwakonzeka',
        readyP: 'Kufunsira ndi kwaulere ndipo simukakamizidwa kuvomera zopereka zilizonse.',
        applyNow: 'Funsirani tsopano',
        ask: 'Funsani funso',
    },
});
</script>

<template>
    <SiteLayout :title="c.title">
        <section class="border-b border-[#14212b]/10">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-20">
                <h1 class="site-display max-w-4xl text-balance text-[2.6rem] sm:text-6xl">{{ c.h1 }}</h1>
                <p class="mt-5 max-w-2xl text-lg leading-relaxed text-[#14212b]/75">{{ c.lead }}</p>
                <Link :href="apply" class="site-btn-primary mt-8">{{ c.start }}</Link>
            </div>
        </section>

        <section class="py-14 lg:py-20">
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                <ol class="space-y-10">
                    <li v-for="([title, text], i) in c.steps" :key="i" class="flex gap-5 sm:gap-8">
                        <span class="site-heading w-10 shrink-0 text-5xl text-[#0a7553]" aria-hidden="true">{{ i + 1 }}</span>
                        <div class="border-t border-[#14212b]/15 pt-3"><h2 class="text-xl font-bold">{{ title }}</h2><p class="mt-1.5 max-w-[60ch] leading-relaxed text-[#14212b]/75">{{ text }}</p></div>
                    </li>
                </ol>
            </div>
        </section>

        <section class="bg-[#e6efee] py-14 lg:py-20">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2">
                <div>
                    <h2 class="site-heading text-3xl">{{ c.whoH }}</h2>
                    <ul class="mt-6 space-y-3.5">
                        <li v-for="x in c.who" :key="x" class="flex gap-3 text-lg"><Check class="mt-1 size-5 shrink-0 text-[#0a7553]" :stroke-width="3" aria-hidden="true" />{{ x }}</li>
                    </ul>
                </div>
                <div>
                    <h2 class="site-heading text-3xl">{{ c.docsH }}</h2>
                    <dl class="mt-6 rounded-md bg-white px-6">
                        <div v-for="[text, req] in c.docs" :key="text" class="flex justify-between gap-4 border-b border-[#14212b]/10 py-3.5 last:border-0">
                            <dt class="font-medium">{{ text }}</dt>
                            <dd class="shrink-0 text-sm font-semibold" :class="req ? 'text-[#0a7553]' : 'text-[#14212b]/65'">{{ req ? c.required : c.helpful }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-sm text-[#14212b]/75">{{ c.files }}</p>
                </div>
            </div>
        </section>

        <section class="py-14 lg:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_1.4fr]">
                <div>
                    <h2 class="site-heading text-3xl">{{ c.decideH }}</h2>
                    <p class="mt-4 max-w-md text-lg leading-relaxed text-[#14212b]/75">{{ c.decideP }}</p>
                    <p class="mt-4 max-w-md text-[#14212b]/75">{{ c.rateNote }}</p>
                </div>
                <dl class="border-t-[3px] border-[#14212b]">
                    <div v-for="b in t.bands" :key="b.code" class="flex gap-5 border-b border-[#14212b]/10 py-5">
                        <dt class="site-heading w-10 shrink-0 text-2xl">{{ b.code }}</dt>
                        <dd>
                            <p class="font-bold">{{ b.name }}</p>
                            <p class="mt-0.5 text-[15px] leading-relaxed text-[#14212b]/75">{{ b.definition }}</p>
                            <p v-if="b.max_amount || b.max_term_months" class="mt-1 text-sm font-semibold">{{ c.limit }}: <span v-if="b.max_amount">{{ c.upTo }} {{ mk(b.max_amount) }}</span><span v-if="b.max_amount && b.max_term_months">, </span><span v-if="b.max_term_months">{{ b.max_term_months }} {{ c.months }}</span></p>
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="on-ink bg-[#14212b] text-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-14 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="site-heading text-3xl">{{ c.readyH }}</h2><p class="mt-2 text-lg text-white/75">{{ c.readyP }}</p></div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link :href="apply" class="site-btn-primary">{{ c.applyNow }}</Link>
                    <Link :href="route('support')" class="site-btn border-2 border-white/50 text-white hover:bg-white/10">{{ c.ask }}</Link>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
