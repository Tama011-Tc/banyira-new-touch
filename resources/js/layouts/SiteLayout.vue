<script setup lang="ts">
import Logo from '@/components/pw/Logo.vue';
import { useSiteT } from '@/lib/site-i18n';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Menu, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watchEffect } from 'vue';

defineProps<{ title?: string }>();

const page = usePage();
const { t, locale, setLocale } = useSiteT();
const signedIn = computed(() => !!page.props.auth.user);
const brand = computed(() => (page.props.brand as { name: string }).name);

// The business plan's three typical uses of one loan product.
const products = computed(() => [
    { href: route('products') + '#compare', title: t('product.growth'), text: t('product.growth.short') },
    { href: route('products') + '#compare', title: t('product.wc'), text: t('product.wc.short') },
    { href: route('products') + '#compare', title: t('product.equipment'), text: t('product.equipment.short') },
    { href: route('products') + '#calculator', title: t('nav.calculator'), text: t('nav.calculator.text') },
]);
const nav = computed(() => [
    { label: t('nav.guide'), href: route('loan-guide'), name: 'loan-guide' },
    { label: t('nav.support'), href: route('support'), name: 'support' },
    { label: t('nav.about'), href: route('about'), name: 'about' },
]);
const applyHref = computed(() => (signedIn.value ? route('dashboard') : route('register', { account_type: 'business_owner' })));
const current = (name: string) => route().current(name);
const languages = [
    { code: 'en', label: 'English' },
    { code: 'ny', label: 'Chichewa' },
] as const;

const productsOpen = ref(false);
const mobileOpen = ref(false);
const menuRoot = ref<HTMLElement | null>(null);
const onDocClick = (e: MouseEvent) => {
    if (menuRoot.value && !menuRoot.value.contains(e.target as Node)) productsOpen.value = false;
};
const onKey = (e: KeyboardEvent) => {
    if (e.key === 'Escape') productsOpen.value = mobileOpen.value = false;
};
onMounted(() => {
    document.addEventListener('click', onDocClick);
    document.addEventListener('keydown', onKey);
});
watchEffect(() => {
    if (typeof document !== 'undefined') document.documentElement.lang = locale.value;
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick);
    document.removeEventListener('keydown', onKey);
});

const footer = computed(() => [
    { title: t('footer.borrow'), links: [[t('nav.products'), route('products')], [t('nav.guide'), route('loan-guide')], [t('nav.calculator'), route('products') + '#calculator'], [t('footer.apply'), route('register', { account_type: 'business_owner' })]] },
    { title: t('footer.invest'), links: [[t('nav.investors'), route('investors')], [t('footer.risks'), route('risks')], [t('footer.investorAccount'), route('register', { account_type: 'investor' })]] },
    { title: t('footer.help'), links: [[t('footer.faq'), route('support')], [t('footer.contact'), route('support') + '#contact'], [t('footer.complaint'), route('support') + '#complaints']] },
    { title: t('footer.company'), links: [[t('nav.about'), route('about')], [t('footer.checks'), route('about') + '#assessment'], [t('nav.signin'), route('login')]] },
]);
</script>

<template>
    <Head v-if="title" :title="title" />
    <div class="site flex min-h-svh flex-col bg-white text-[#14212b]">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:shadow">Skip to content</a>

        <header class="sticky top-0 z-40 border-b border-[#14212b]/10 bg-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:gap-8 sm:px-6 lg:h-[72px]">
                <Link :href="route('home')" class="flex shrink-0 items-center gap-2.5" :aria-label="`${brand} home`"><Logo :size="30" /><span class="site-heading hidden text-lg min-[420px]:inline sm:text-xl">Banyira</span></Link>

                <nav class="hidden h-full items-center gap-6 lg:flex" aria-label="Main">
                    <div ref="menuRoot" class="relative flex h-full items-center">
                        <button type="button" class="flex h-full items-center gap-1 border-b-[3px] pt-[3px] font-semibold" :class="current('products') || productsOpen ? 'border-[#0a7553]' : 'border-transparent hover:border-[#14212b]/20'" :aria-expanded="productsOpen" aria-controls="products-menu" @click.stop="productsOpen = !productsOpen">
                            {{ t('nav.products') }}<ChevronDown class="size-4 transition-transform" :class="productsOpen ? 'rotate-180' : ''" aria-hidden="true" />
                        </button>
                        <div v-show="productsOpen" id="products-menu" class="absolute -left-5 top-full w-[26rem] border border-[#14212b]/10 bg-white py-2 shadow-[0_18px_40px_-20px_rgba(20,33,43,0.35)]">
                            <a v-for="p in products" :key="p.title" :href="p.href" class="block px-5 py-3 hover:bg-[#e6efee]" @click="productsOpen = false">
                                <span class="block font-bold">{{ p.title }}</span><span class="block text-sm text-[#14212b]/65">{{ p.text }}</span>
                            </a>
                            <div class="mx-5 mt-2 border-t border-[#14212b]/10 pb-1 pt-3 text-sm">{{ t('nav.lend') }} <Link :href="route('investors')" class="site-link">{{ t('nav.investors') }}</Link></div>
                        </div>
                    </div>
                    <Link v-for="n in nav" :key="n.name" :href="n.href" class="flex h-full items-center whitespace-nowrap border-b-[3px] pt-[3px] font-semibold" :class="current(n.name) ? 'border-[#0a7553]' : 'border-transparent hover:border-[#14212b]/20'">{{ n.label }}</Link>
                </nav>

                <div class="ml-auto flex items-center gap-3 sm:gap-5">
                    <div class="hidden items-center rounded-md border border-[#14212b]/15 p-0.5 text-sm md:flex" role="group" :aria-label="t('lang.label')">
                        <button v-for="l in languages" :key="l.code" type="button" class="rounded px-2.5 py-1 font-semibold" :class="locale === l.code ? 'bg-[#14212b] text-white' : 'text-[#14212b]/70 hover:text-[#14212b]'" :aria-pressed="locale === l.code" :lang="l.code" @click="locale !== l.code && setLocale(l.code)">{{ l.label }}</button>
                    </div>
                    <!-- On phones the switch shows the other language's name, so it is always one tap away. -->
                    <button type="button" class="text-sm font-semibold underline decoration-[#14212b]/30 underline-offset-4 md:hidden" :lang="locale === 'en' ? 'ny' : 'en'" @click="setLocale(locale === 'en' ? 'ny' : 'en')">{{ locale === 'en' ? 'Chichewa' : 'English' }}</button>
                    <Link :href="signedIn ? route('dashboard') : route('login')" class="hidden whitespace-nowrap font-semibold hover:underline hover:underline-offset-4 sm:block">{{ signedIn ? t('nav.account') : t('nav.signin') }}</Link>
                    <Link :href="applyHref" class="site-btn-primary whitespace-nowrap px-3.5 py-2 text-sm sm:px-5 sm:py-2.5 sm:text-[15px]">{{ t('nav.apply') }}</Link>
                    <button type="button" class="-mr-2 p-2 lg:hidden" :aria-expanded="mobileOpen" aria-controls="mobile-menu" :aria-label="mobileOpen ? t('nav.menu.close') : t('nav.menu.open')" @click="mobileOpen = !mobileOpen"><X v-if="mobileOpen" class="size-6" /><Menu v-else class="size-6" /></button>
                </div>
            </div>

            <div v-show="mobileOpen" id="mobile-menu" class="max-h-[calc(100svh-4rem)] overflow-y-auto border-t border-[#14212b]/10 bg-white px-4 pb-8 lg:hidden">
                <div class="mt-4 grid grid-cols-2 rounded-md border border-[#14212b]/15 p-0.5 md:hidden" role="group" :aria-label="t('lang.label')">
                    <button v-for="l in languages" :key="l.code" type="button" class="rounded py-2 font-semibold" :class="locale === l.code ? 'bg-[#14212b] text-white' : 'text-[#14212b]/70'" :aria-pressed="locale === l.code" :lang="l.code" @click="locale !== l.code && setLocale(l.code)">{{ l.label }}</button>
                </div>
                <p class="pt-5 text-sm font-semibold text-[#14212b]/60">{{ t('nav.products') }}</p>
                <a v-for="p in products" :key="p.title" :href="p.href" class="block py-2.5 text-lg font-semibold" @click="mobileOpen = false">{{ p.title }}</a>
                <Link :href="route('investors')" class="block py-2.5 text-lg font-semibold">{{ t('nav.investors') }}</Link>
                <div class="my-3 border-t border-[#14212b]/10" />
                <Link v-for="n in nav" :key="n.name" :href="n.href" class="block py-2.5 text-lg font-semibold">{{ n.label }}</Link>
                <div class="mt-6 grid gap-3">
                    <Link :href="signedIn ? route('dashboard') : route('login')" class="site-btn-outline">{{ signedIn ? t('nav.account') : t('nav.signin') }}</Link>
                    <Link :href="applyHref" class="site-btn-primary">{{ t('nav.apply') }}</Link>
                </div>
            </div>
        </header>

        <main id="main" class="grow"><slot /></main>

        <footer class="bg-[#14212b] text-white/75">
            <div class="mx-auto max-w-7xl px-4 pb-10 pt-16 sm:px-6">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(4,1fr)]">
                    <div>
                        <Link :href="route('home')" class="flex items-center gap-2.5 text-white"><Logo :size="32" /><span class="site-heading text-xl">Banyira</span></Link>
                        <p class="mt-4 max-w-xs text-sm leading-relaxed">{{ t('footer.about') }}</p>
                    </div>
                    <div v-for="col in footer" :key="col.title">
                        <h2 class="font-bold text-white">{{ col.title }}</h2>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li v-for="[label, href] in col.links" :key="href"><a :href="href" class="hover:text-white hover:underline hover:underline-offset-4">{{ label }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-14 max-w-4xl space-y-3 border-t border-white/15 pt-6 text-xs leading-relaxed text-white/60">
                    <p>{{ t('footer.legal1', { brand }) }}</p>
                    <p>{{ t('footer.legal2') }}</p>
                    <p>&copy; {{ new Date().getFullYear() }} {{ brand }}</p>
                </div>
            </div>
        </footer>
    </div>
</template>
