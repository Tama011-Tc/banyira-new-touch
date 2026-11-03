<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { pct } from '@/lib/format';
import { useCopy } from '@/lib/site-i18n';
import { Link } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';

const props = defineProps<{ terms: { max_rate_bp: number } }>();
const rate = pct(props.terms.max_rate_bp);

const c = useCopy({
    en: {
        title: 'About us',
        h1: 'Helping Malawian businesses get the finance they deserve',
        lead: "Small businesses are the backbone of Malawi's economy, yet most cannot get a bank loan. Banyira connects them with investors who want their money to do some good, on terms both sides can understand.",
        storyH: 'Our story',
        story: [
            'Banyira began in Blantyre with a simple observation: good businesses were being held back, not by a lack of customers, but by a lack of fair credit. Banks asked for collateral most owners did not have, and informal lenders charged rates that swallowed their profits.',
            'We built a peer-to-peer platform where investors lend directly to vetted businesses. Each business is judged on how it trades. Each investor chooses who to back and at what rate. And everyone sees the same, honest numbers.',
            'Our goal is to finance more than 1,000 small businesses, starting in Blantyre and growing to Lilongwe and Mzuzu.',
        ],
        values: [
            ['Fair', `Rates never above ${rate} a year, against the 50–100% charged by many informal lenders. One fee, no hidden charges.`],
            ['Transparent', 'Businesses see every cost before they accept. Investors see the risks as well as the returns.'],
            ['Direct', 'Investors lend straight to businesses. No middlemen and no pooled funds.'],
            ['Supportive', 'Free guides on bookkeeping and financial management help owners grow beyond their first loan.'],
        ],
        controlsH: 'How we keep lending safe and honest',
        controlsP: 'Controls are built into the platform itself, not left to good intentions.',
        controls: [
            'Every business is identity-checked and its documents reviewed by an experienced credit analyst.',
            'The analyst who assesses a business can never be the person who approves it.',
            'Sales staff cannot approve credit, and IT staff cannot change loans or payments.',
            'Financial records are append-only, and every sensitive action is logged with who did it and why.',
            'Changes to fees or lending limits need two people to approve them.',
        ],
        statusH: 'Our regulatory status',
        statusP: 'Banyira is designed as a non-deposit-taking microfinance platform under the Microfinance Act 2010. We are not yet licensed. Until licensing and our payment-provider approvals are confirmed, the platform runs as a pilot with fictional data and simulated payments, and no real money moves.',
        business: 'I run a business',
        businessP: 'Apply for a loan',
        invest: 'I want to invest',
        investP: 'Lend to vetted businesses',
    },
    ny: {
        title: 'Za ife',
        h1: 'Kuthandiza mabizinesi a ku Malawi kupeza ndalama zomwe akuyenera',
        lead: 'Mabizinesi ang\'onoang\'ono ndiwo msana wa chuma cha Malawi, komabe ambiri sangathe kupeza ngongole ku banki. Banyira imawalumikiza ndi obwereketsa omwe akufuna kuti ndalama zawo zithandize, pa mfundo zomwe mbali zonse zingamvetse.',
        storyH: 'Mbiri yathu',
        story: [
            'Banyira inayambira ku Blantyre ndi chinthu chimodzi chomwe tinaona: mabizinesi abwino ankabwerera m\'mbuyo, osati chifukwa chosowa makasitomala, koma chifukwa chosowa ngongole yachilungamo. Mabanki ankafuna chikole chomwe eni mabizinesi ambiri analibe, ndipo obwereketsa a mseri ankalipiritsa chiwongola dzanja chomwe chinkameza phindu lawo.',
            'Tinamanga nsanja yomwe obwereketsa amabwereketsa mwachindunji mabizinesi omwe aunikidwa. Bizinesi iliyonse imaunikidwa malinga ndi momwe ikuchitira malonda. Wobwereketsa aliyense amasankha yemwe angamuthandize ndi pa chiwongola dzanja chotani. Ndipo aliyense amaona ziwerengero zofanana ndi zoona.',
            'Cholinga chathu ndi kupereka ndalama kwa mabizinesi ang\'onoang\'ono oposa 1,000, kuyambira ku Blantyre ndi kufikira ku Lilongwe ndi Mzuzu.',
        ],
        values: [
            ['Chilungamo', `Chiwongola dzanja sichiposa ${rate} pa chaka, poyerekeza ndi 50–100% cha obwereketsa ambiri a mseri. Chindapusa chimodzi, palibe zolipira zobisika.`],
            ['Poyera', 'Mabizinesi amaona mtengo wonse asanavomere. Obwereketsa amaona zoopsa komanso phindu.'],
            ['Mwachindunji', 'Obwereketsa amabwereketsa mabizinesi mwachindunji. Palibe apakati ndipo palibe ndalama zophatikizana.'],
            ['Othandiza', 'Malangizo aulere pa kusunga zolemba ndi kayendetsedwe ka ndalama amathandiza eni mabizinesi kukula kupitirira ngongole yawo yoyamba.'],
        ],
        controlsH: 'Momwe timasungira kubwereketsa kukhala kotetezeka ndi koona',
        controlsP: 'Zoteteza zili mkati mwa nsanja yokha, osati kusiyidwa ku zolinga zabwino.',
        controls: [
            'Bizinesi iliyonse imatsimikiziridwa umunthu wake ndipo zikalata zake zimaunikidwa ndi katswiri wa ngongole wodziwa ntchito.',
            'Katswiri amene waunika bizinesi sangakhale munthu amene akuivomereza.',
            'Ogwira ntchito ogulitsa sangavomereze ngongole, ndipo ogwira ntchito a IT sangasinthe ngongole kapena malipiro.',
            'Zolemba za ndalama zimangowonjezeredwa, ndipo chochita chilichonse chofunika chimalembedwa ndi amene wachita ndi chifukwa chake.',
            'Kusintha zindapusa kapena malire a ngongole kumafunika anthu awiri kuvomereza.',
        ],
        statusH: 'Chilolezo chathu',
        statusP: 'Banyira yapangidwa ngati nsanja ya microfinance yosasunga ndalama za anthu pansi pa Microfinance Act 2010. Tilibe chilolezo pakadali pano. Mpaka chilolezo ndi chivomerezo cha opereka malipiro zitsimikizidwe, nsanjayi ikugwira ntchito ngati yoyesera ndi zambiri zongopeka ndi malipiro oyerekeza, ndipo palibe ndalama zenizeni zomwe zikuyenda.',
        business: 'Ndili ndi bizinesi',
        businessP: 'Funsirani ngongole',
        invest: 'Ndikufuna kubwereketsa',
        investP: 'Bwereketsani mabizinesi omwe aunikidwa',
    },
});
</script>

<template>
    <SiteLayout :title="c.title">
        <section class="border-b border-[#14212b]/10">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:py-24">
                <h1 class="site-display max-w-4xl text-balance text-[2.6rem] sm:text-6xl">{{ c.h1 }}</h1>
                <p class="mt-6 max-w-2xl text-xl leading-relaxed text-[#14212b]/75">{{ c.lead }}</p>
            </div>
        </section>

        <section class="py-14 lg:py-20">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2">
                <div>
                    <h2 class="site-heading text-3xl">{{ c.storyH }}</h2>
                    <div class="mt-5 max-w-[62ch] space-y-4 text-lg leading-relaxed text-[#14212b]/80">
                        <p v-for="p in c.story" :key="p">{{ p }}</p>
                    </div>
                </div>
                <dl class="grid content-start gap-x-8 gap-y-8 sm:grid-cols-2">
                    <div v-for="[title, text] in c.values" :key="title" class="border-t-[3px] border-[#14212b] pt-4">
                        <dt class="site-heading text-2xl">{{ title }}</dt>
                        <dd class="mt-2 leading-relaxed text-[#14212b]/75">{{ text }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section id="assessment" class="on-ink scroll-mt-20 bg-[#14212b] py-14 text-white lg:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_1.3fr]">
                <div>
                    <h2 class="site-heading text-balance text-3xl">{{ c.controlsH }}</h2>
                    <p class="mt-3 text-lg text-white/75">{{ c.controlsP }}</p>
                </div>
                <ul class="space-y-4">
                    <li v-for="x in c.controls" :key="x" class="flex gap-4 border-t border-white/15 pt-4"><Check class="mt-0.5 size-5 shrink-0 text-[#7fd3b4]" :stroke-width="3" aria-hidden="true" /><p>{{ x }}</p></li>
                </ul>
            </div>
        </section>

        <section class="py-14 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="rounded-md border-t-[3px] border-[#14212b] bg-[#e6efee] p-7 sm:p-10">
                    <h2 class="site-heading text-2xl">{{ c.statusH }}</h2>
                    <p class="mt-3 max-w-3xl leading-relaxed text-[#14212b]/80">{{ c.statusP }}</p>
                </div>
                <div class="mt-12 grid gap-4 sm:grid-cols-2">
                    <Link :href="route('register', { account_type: 'business_owner' })" class="block rounded-md bg-[#0a7553] p-7 text-white hover:bg-[#085f43]"><span class="site-heading block text-2xl">{{ c.business }}</span><span class="mt-1 block text-white/85">{{ c.businessP }}</span></Link>
                    <Link :href="route('investors')" class="block rounded-md bg-[#14212b] p-7 text-white hover:bg-black"><span class="site-heading block text-2xl">{{ c.invest }}</span><span class="mt-1 block text-white/85">{{ c.investP }}</span></Link>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
