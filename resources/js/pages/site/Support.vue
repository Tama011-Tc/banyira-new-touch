<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { mk, pct } from '@/lib/format';
import { feeRange, type FeeTier } from '@/lib/loan';
import { useCopy } from '@/lib/site-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    terms: { max_rate_bp: number; min_trading_years: number; min_records_months: number; min_commitment: number; support_phone: string | null; support_hours: string | null };
    fee_tiers: FeeTier[];
    support_email: string;
}>();
const t = props.terms;
const signedIn = computed(() => !!usePage().props.auth.user);
const rate = pct(t.max_rate_bp);
const range = feeRange(props.fee_tiers);
const min = mk(t.min_commitment);
// The default config address is a placeholder; never show it as a working contact.
const emailReady = !props.support_email.endsWith('.test');

const c = useCopy({
    en: {
        title: 'Support',
        h1: 'How can we help?',
        search: 'Search questions, for example: repay early',
        searchLabel: 'Search help',
        noMatch: (q: string) => `No questions match "${q}". Try another word, or contact us below.`,
        groups: [
            {
                id: 'borrowing',
                title: 'Borrowing',
                items: [
                    ['Who can apply for a loan?', `Malawian businesses that have traded for at least ${t.min_trading_years} year and can show at least ${t.min_records_months} months of financial records, such as bank statements. You do not need collateral.`],
                    ['How much does a loan cost?', `You pay interest at the rate in the offer you accept, never more than ${rate} a year, and ${range ? `one Banyira fee of ${range} depending on the loan size` : 'no platform fee while none is approved'}. The fee is taken from the amount paid out and shown before you accept. There are no other charges.`],
                    ['Can I repay early?', 'Yes, at any time, and we never charge for it.'],
                    ['How do I accept an offer?', 'We send you an SMS when an investor makes an offer, with the amount, rate and term. Sign in to your Banyira account to read the full offer, including the fee and every repayment, then choose Accept or Decline. Offers are only accepted in your account, never by replying to a text.'],
                    ['What happens if I miss a repayment?', 'Contact us straight away and we will work with you on a plan. Missed repayments are recorded, and serious arrears can lead to the loan being declared in default.'],
                ],
            },
            {
                id: 'investing',
                title: 'Investing',
                items: [
                    ['How much do I need to start?', `You can lend from ${min} to a single business.`],
                    ['Is my money safe?', 'No investment in business loans is risk-free. Businesses can repay late or not at all, and you can lose some or all of what you lend. Spreading money across several businesses reduces the impact of any one failure.'],
                    ['How are repayments paid to me?', 'Repayments from businesses are paid to the bank account in your profile. You can see every payment in your portfolio and download a statement.'],
                    ['Can I get my money back early?', 'Not normally. Your money is committed for the full term of each loan.'],
                ],
            },
            {
                id: 'account',
                title: 'Your account',
                items: [
                    ['Why do you need my national ID?', 'We verify every investor and business owner to prevent fraud. Your ID number is encrypted and only compliance staff can review it.'],
                    ['Can I talk to a business or investor directly?', 'All conversations happen inside Banyira. Phone numbers, emails and links are blocked in messages to protect both sides.'],
                    ['I forgot my password', 'Use "Forgot password" on the sign-in page and we will email you a reset link.'],
                ],
            },
        ],
        contactH: 'Contact us',
        phoneH: 'Call or text',
        phoneP: 'For help with applying, an offer or a repayment.',
        phoneMissing: '[phone number to confirm]',
        hoursMissing: '[hours to confirm]',
        emailMissing: '[support email to confirm]',
        emailH: 'Email',
        emailP: 'Write to us about anything: applying, lending or your account.',
        teamH: 'Apply with our team',
        teamP: 'We are based in Blantyre. A member of our team can help you prepare and submit your application.',
        teamCta: 'Request help',
        complaintH: 'Make a complaint',
        complaintP: 'Not happy with something? Tell us. Every complaint is logged, tracked and answered by a named person.',
        complaintIn: 'Raise a complaint',
        complaintOut: 'Sign in to raise a complaint',
    },
    ny: {
        title: 'Thandizo',
        h1: 'Tingakuthandizeni bwanji?',
        search: 'Sakani mafunso, mwachitsanzo: kubweza msanga',
        searchLabel: 'Sakani thandizo',
        noMatch: (q: string) => `Palibe mafunso ogwirizana ndi "${q}". Yesani mawu ena, kapena lumikizanani nafe pansipa.`,
        groups: [
            {
                id: 'borrowing',
                title: 'Kubwereka',
                items: [
                    ['Ndani angafunsire ngongole?', `Mabizinesi a ku Malawi omwe akhala akuchita malonda kwa chaka chimodzi kapena kuposa ndipo ali ndi zolemba za ndalama za miyezi ${t.min_records_months}, monga masitatimenti a banki. Simufunika chikole.`],
                    ['Ngongole imadya ndalama zingati?', `Mumalipira chiwongola dzanja cha zopereka zomwe mwavomera, osapitirira ${rate} pa chaka, ndi ${range ? `chindapusa chimodzi cha Banyira cha ${range} malinga ndi kukula kwa ngongole` : 'palibe chindapusa pamene palibe chovomerezeka'}. Chindapusacho chimachotsedwa pa ndalama zoperekedwa ndipo chimaonetsedwa musanavomere. Palibe zolipira zina.`],
                    ['Kodi ndingabweze msanga?', 'Inde, nthawi iliyonse, ndipo sitilipiritsa kanthu.'],
                    ['Ndingavomere bwanji zopereka?', 'Timakutumizirani SMS wobwereketsa akapereka zopereka, ndi ndalama, chiwongola dzanja ndi nthawi. Lowani mu akaunti yanu ya Banyira kuti muwerenge zopereka zonse, kuphatikiza chindapusa ndi malipiro onse, kenako sankhani Vomerani kapena Kanani. Zopereka zimavomerezedwa mu akaunti yanu yokha, osati poyankha uthenga.'],
                    ['Chimachitika ndi chiyani ndikalephera kulipira?', 'Lumikizanani nafe nthawi yomweyo ndipo tidzakonza nanu dongosolo. Malipiro olephereka amalembedwa, ndipo ngongole yochedwa kwambiri ingalengezedwe kuti yalephereka.'],
                ],
            },
            {
                id: 'investing',
                title: 'Kubwereketsa',
                items: [
                    ['Ndikufunika ndalama zingati kuti ndiyambe?', `Mutha kubwereketsa kuyambira ${min} ku bizinesi imodzi.`],
                    ['Kodi ndalama zanga zili zotetezeka?', 'Palibe kubwereketsa mabizinesi kopanda chiopsezo. Mabizinesi angabweze mochedwa kapena osabweza, ndipo mutha kutaya gawo kapena zonse zomwe mwabwereketsa. Kugawa ndalama m\'mabizinesi angapo kumachepetsa vuto la bizinesi imodzi kulephera.'],
                    ['Malipiro amandifika bwanji?', 'Malipiro ochokera ku mabizinesi amapita ku akaunti ya banki yomwe ili mu mbiri yanu. Mutha kuona malipiro onse mu portfolio yanu ndi kutsitsa sitatimenti.'],
                    ['Kodi ndingatenge ndalama zanga msanga?', 'Nthawi zambiri ayi. Ndalama zanu zimakhala mu ngongole nthawi yonse ya ngongoleyo.'],
                ],
            },
            {
                id: 'account',
                title: 'Akaunti yanu',
                items: [
                    ['Chifukwa chiyani mukufuna nambala ya chiphaso changa?', 'Timatsimikiza aliyense wobwereketsa ndi mwini bizinesi kuti tipewe chinyengo. Nambala yanu imasungidwa mobisika ndipo ogwira ntchito oyang\'anira malamulo okha ndi omwe angayiwone.'],
                    ['Kodi ndingalankhule ndi bizinesi kapena wobwereketsa mwachindunji?', 'Zokambirana zonse zimachitikira mu Banyira. Manambala a foni, maimelo ndi maulalo amatsekedwa m\'mauthenga kuti mbali zonse zitetezedwe.'],
                    ['Ndayiwala mawu achinsinsi', 'Dinani "Forgot password" pa tsamba lolowera ndipo tidzakutumizirani ulalo wosinthira pa imelo.'],
                ],
            },
        ],
        contactH: 'Lumikizanani nafe',
        phoneH: 'Imbani kapena tumizani uthenga',
        phoneP: 'Thandizo pa kufunsira, zopereka kapena malipiro.',
        emailH: 'Imelo',
        emailP: 'Tilembereni pa chilichonse: kufunsira, kubwereketsa kapena akaunti yanu.',
        teamH: 'Funsirani ndi gulu lathu',
        teamP: 'Tili ku Blantyre. M\'modzi wa gulu lathu angakuthandizeni kukonza ndi kutumiza pempho lanu.',
        teamCta: 'Pemphani thandizo',
        complaintH: 'Perekani dandaulo',
        complaintP: 'Simukukondwera ndi kanthu? Tiuzeni. Dandaulo lililonse limalembedwa, kutsatiridwa ndi kuyankhidwa ndi munthu wodziwika.',
        complaintIn: 'Perekani dandaulo',
        complaintOut: 'Lowani kuti mupereke dandaulo',
    },
});

const q = ref('');
const filtered = computed(() => {
    const s = q.value.trim().toLowerCase();
    return c.value.groups.map((g) => ({ ...g, items: s ? g.items.filter(([a, b]) => (a + b).toLowerCase().includes(s)) : g.items })).filter((g) => g.items.length);
});
</script>

<template>
    <SiteLayout :title="c.title">
        <section class="on-ink bg-[#14212b] text-white">
            <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:py-20">
                <h1 class="site-display text-balance text-[2.6rem] sm:text-6xl">{{ c.h1 }}</h1>
                <div class="relative mt-8 max-w-xl">
                    <Search class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-[#14212b]/50" aria-hidden="true" />
                    <input v-model="q" type="search" :placeholder="c.search" :aria-label="c.searchLabel" class="w-full rounded-md border-0 bg-white py-3.5 pl-12 pr-4 text-[#14212b] placeholder:text-[#14212b]/55 focus:outline focus:outline-[3px] focus:outline-[#e8b931]" />
                </div>
                <nav class="mt-6 flex flex-wrap gap-x-6 gap-y-2" :aria-label="c.title">
                    <a v-for="g in c.groups" :key="g.id" :href="`#${g.id}`" class="font-semibold underline decoration-white/40 underline-offset-4 hover:decoration-white">{{ g.title }}</a>
                    <a href="#contact" class="font-semibold underline decoration-white/40 underline-offset-4 hover:decoration-white">{{ c.contactH }}</a>
                </nav>
            </div>
        </section>

        <section class="py-14 lg:py-20">
            <div class="mx-auto max-w-4xl space-y-12 px-4 sm:px-6">
                <div v-for="g in filtered" :id="g.id" :key="g.id" class="scroll-mt-20">
                    <h2 class="site-heading text-3xl">{{ g.title }}</h2>
                    <div class="mt-5 border-t-[3px] border-[#14212b]">
                        <details v-for="[question, answer] in g.items" :key="question" class="group border-b border-[#14212b]/10" :open="!!q">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-[17px] font-semibold hover:text-[#0a7553] [&::-webkit-details-marker]:hidden">{{ question }}<ChevronDown class="size-5 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true" /></summary>
                            <p class="max-w-[65ch] pb-5 leading-relaxed text-[#14212b]/80">{{ answer }}</p>
                        </details>
                    </div>
                </div>
                <p v-if="!filtered.length" class="rounded-md bg-[#e6efee] p-6">{{ c.noMatch(q) }}</p>
            </div>
        </section>

        <section id="contact" class="scroll-mt-20 bg-[#e6efee] py-14 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <h2 class="site-heading text-3xl sm:text-[2.6rem]">{{ c.contactH }}</h2>
                <div class="mt-10 grid gap-10 md:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                    <div class="border-t-[3px] border-[#14212b] pt-5">
                        <h3 class="text-lg font-bold">{{ c.phoneH }}</h3>
                        <p class="mt-1 text-[#14212b]/75">{{ c.phoneP }}</p>
                        <a v-if="t.support_phone" :href="`tel:${t.support_phone.replace(/\s/g, '')}`" class="site-link mt-3 inline-block">{{ t.support_phone }}</a>
                        <p v-else class="mt-3 font-semibold text-[#b23a2e]">{{ c.phoneMissing }}</p>
                        <p v-if="t.support_hours" class="mt-1 text-sm text-[#14212b]/75">{{ t.support_hours }}</p>
                        <p v-else class="mt-1 text-sm font-semibold text-[#b23a2e]">{{ c.hoursMissing }}</p>
                    </div>
                    <div class="border-t-[3px] border-[#14212b] pt-5">
                        <h3 class="text-lg font-bold">{{ c.emailH }}</h3>
                        <p class="mt-1 text-[#14212b]/75">{{ c.emailP }}</p>
                        <a v-if="emailReady" :href="`mailto:${support_email}`" class="site-link mt-3 inline-block break-all">{{ support_email }}</a>
                        <p v-else class="mt-3 font-semibold text-[#b23a2e]">{{ c.emailMissing }}</p>
                    </div>
                    <div class="border-t-[3px] border-[#14212b] pt-5">
                        <h3 class="text-lg font-bold">{{ c.teamH }}</h3>
                        <p class="mt-1 text-[#14212b]/75">{{ c.teamP }}</p>
                        <a v-if="emailReady" :href="`mailto:${support_email}?subject=Help%20with%20my%20application`" class="site-link mt-3 inline-block">{{ c.teamCta }}</a>
                    </div>
                    <div id="complaints" class="scroll-mt-20 border-t-[3px] border-[#14212b] pt-5">
                        <h3 class="text-lg font-bold">{{ c.complaintH }}</h3>
                        <p class="mt-1 text-[#14212b]/75">{{ c.complaintP }}</p>
                        <Link :href="signedIn ? route('complaints.index') : route('login')" class="site-link mt-3 inline-block">{{ signedIn ? c.complaintIn : c.complaintOut }}</Link>
                    </div>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
