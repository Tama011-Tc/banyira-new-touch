<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Icon from '@/Components/Icon.vue'
import Btn from '@/Components/Btn.vue'
import Flash from '@/Components/Flash.vue'

const page = usePage<{ navProducts: { slug: string; name: string; summary: string }[] }>()
const products = computed(() => page.props.navProducts ?? [])
const user = computed(() => page.props.auth.user)
const open = ref(false)
const dd = ref(false)
const scrolled = ref(false)

const nav = [['/how-to-get-a-loan', 'How to Get a Loan'], ['/how-to-invest', 'How to Invest'], ['/about', 'About Us'], ['/contact', 'Contact']]
const isActive = (h: string) => (h === '/' ? page.url === '/' : page.url.startsWith(h))
const dashboard = computed(() => (user.value?.type === 'business' ? '/business' : '/investor'))

const onScroll = () => (scrolled.value = window.scrollY > 8)
const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') { dd.value = false; open.value = false } }
onMounted(() => { onScroll(); window.addEventListener('scroll', onScroll, { passive: true }); window.addEventListener('keydown', onKey) })
onBeforeUnmount(() => { window.removeEventListener('scroll', onScroll); window.removeEventListener('keydown', onKey) })
watch(() => page.url, () => { open.value = false; dd.value = false })
const closeDd = (e: FocusEvent) => { if (!(e.currentTarget as HTMLElement).contains(e.relatedTarget as Node)) dd.value = false }
</script>

<template>
  <div class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-50 focus:bg-surface focus:p-3">Skip to content</a>
    <header class="sticky top-0 z-40 border-b bg-paper transition-[border-color] duration-300" :class="scrolled || open ? 'border-line' : 'border-transparent'">
      <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6">
        <Link href="/" aria-label="Banyira home" class="press"><Logo /></Link>

        <nav aria-label="Primary" class="hidden items-center gap-7 text-sm lg:flex">
          <Link href="/" class="navlink" :data-active="isActive('/')">Home</Link>
          <div class="relative" @focusout="closeDd" @mouseleave="dd = false">
            <button type="button" class="navlink flex items-center gap-1" :data-active="isActive('/products')" :aria-expanded="dd" aria-haspopup="true" @click="dd = !dd" @mouseenter="dd = true">
              Our Products <span class="transition-transform duration-200" :class="dd && 'rotate-180'"><Icon n="chevdown" :size="14" /></span>
            </button>
            <Transition name="pop">
              <div v-if="dd" class="absolute left-1/2 top-full w-[22rem] -translate-x-1/2 pt-3" style="transform-origin: top center">
                <div class="rounded-xl border border-separator-2 bg-surface p-2">
                  <Link v-for="p in products" :key="p.slug" :href="`/products/${p.slug}`" class="block rounded-lg px-3 py-2.5 transition-colors duration-150 hover:bg-mint">
                    <span class="block font-medium">{{ p.name }}</span>
                    <span class="block text-[13px] text-muted">{{ p.summary }}</span>
                  </Link>
                  <Link href="/products" class="mt-1 block border-t border-line px-3 pb-1.5 pt-2.5 text-[13px] text-muted transition-colors hover:text-ink">All products and fees →</Link>
                </div>
              </div>
            </Transition>
          </div>
          <Link v-for="[h, l] in nav" :key="h" :href="h" class="navlink" :data-active="isActive(h)">{{ l }}</Link>
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
          <Btn v-if="user && user.type !== 'employee'" :href="dashboard" size="sm">My dashboard</Btn>
          <template v-else>
            <Link href="/login" class="navlink text-sm">Login</Link>
            <Btn href="/register" size="sm">Get Started</Btn>
          </template>
        </div>

        <button type="button" class="press -mr-2 flex h-10 w-10 items-center justify-center lg:hidden" :aria-expanded="open" aria-label="Menu" @click="open = !open">
          <span class="relative block h-3 w-4" aria-hidden="true">
            <span class="absolute left-0 top-0 h-[1.5px] w-4 bg-ink transition-transform duration-300 ease-[var(--ease-out)]" :class="open && 'translate-y-[5.25px] rotate-45'" />
            <span class="absolute bottom-0 left-0 h-[1.5px] w-4 bg-ink transition-transform duration-300 ease-[var(--ease-out)]" :class="open && '-translate-y-[5.25px] -rotate-45'" />
          </span>
        </button>
      </div>

      <div class="reveal lg:hidden" :data-open="open">
        <div>
          <nav aria-label="Mobile" class="px-4 pb-5 sm:px-6">
            <Link href="/" class="flex h-11 items-center border-b border-line">Home</Link>
            <p class="pb-1 pt-4 text-[13px] text-muted">Our Products</p>
            <Link v-for="p in products" :key="p.slug" :href="`/products/${p.slug}`" class="flex h-10 items-center pl-3 text-[15px] text-muted">{{ p.name }}</Link>
            <Link v-for="[h, l] in nav" :key="h" :href="h" class="flex h-11 items-center border-b border-line">{{ l }}</Link>
            <div class="mt-4 grid grid-cols-2 gap-2">
              <Btn v-if="user && user.type !== 'employee'" :href="dashboard" full class="col-span-2">My dashboard</Btn>
              <template v-else><Btn variant="outline" href="/login">Login</Btn><Btn href="/register">Get Started</Btn></template>
            </div>
          </nav>
        </div>
      </div>
    </header>

    <main id="main" tabindex="-1" class="flex-1 outline-none"><slot /></main>

    <footer class="border-t border-line">
      <div class="mx-auto grid max-w-6xl gap-8 px-4 py-14 text-sm sm:px-6 md:grid-cols-[1.5fr_1fr_1fr_1fr]">
        <div>
          <Logo />
          <p class="mt-3 max-w-xs text-muted">A peer-to-peer marketplace connecting Malawian businesses that need financing with people who want to invest in them.</p>
        </div>
        <div>
          <h2 class="font-sans text-[13px] font-medium tracking-normal">Businesses</h2>
          <ul class="mt-3 space-y-2">
            <li><Link href="/products" class="footlink">Our products</Link></li>
            <li><Link href="/how-to-get-a-loan" class="footlink">How to get a loan</Link></li>
            <li><Link href="/register/business" class="footlink">Apply for financing</Link></li>
          </ul>
        </div>
        <div>
          <h2 class="font-sans text-[13px] font-medium tracking-normal">Investors</h2>
          <ul class="mt-3 space-y-2">
            <li><Link href="/how-to-invest" class="footlink">How to invest</Link></li>
            <li><Link href="/legal#risk" class="footlink">Investment risk</Link></li>
            <li><Link href="/register/investor" class="footlink">Open an investor account</Link></li>
          </ul>
        </div>
        <div>
          <h2 class="font-sans text-[13px] font-medium tracking-normal">Company</h2>
          <ul class="mt-3 space-y-2">
            <li><Link href="/about" class="footlink">About us</Link></li>
            <li><Link href="/contact" class="footlink">Contact</Link></li>
            <li><Link href="/legal" class="footlink">Terms, privacy and complaints</Link></li>
          </ul>
        </div>
      </div>
      <div class="border-t border-line">
        <div class="mx-auto max-w-6xl space-y-2 px-4 py-6 text-xs leading-relaxed text-muted sm:px-6">
          <p><span class="font-medium text-ink">Investing involves risk.</span> Investors may experience delayed repayments or lose some or all of their invested capital. Banyira's credit assessments and risk classifications do not guarantee repayment or returns.</p>
          <p>Applying for financing does not guarantee approval or funding. © {{ new Date().getFullYear() }} Banyira. Lilongwe, Malawi.</p>
        </div>
      </div>
    </footer>
    <Flash />
  </div>
</template>

<style scoped>
.navlink { position: relative; color: var(--color-muted); transition: color 150ms ease; }
.navlink:hover, .navlink[data-active='true'] { color: var(--color-ink); }
.navlink::after {
  content: ''; position: absolute; left: 0; right: 0; bottom: -6px; height: 1.5px; border-radius: 1px;
  background: var(--color-forest); transform: scaleX(0); transform-origin: left; transition: transform 260ms var(--ease-out);
}
.navlink[data-active='true']::after { transform: scaleX(1); }
@media (hover: hover) and (pointer: fine) { .navlink:hover::after { transform: scaleX(1); } }
.footlink { color: var(--color-muted); transition: color 150ms ease; }
.footlink:hover { color: var(--color-ink); }
</style>
