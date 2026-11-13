<script lang="ts">
export type NavItem = { href: string; label: string; badge?: number | string; exact?: boolean }
export type NavGroup = { label?: string; items: NavItem[] }

// Survives page changes (each page mounts its own shell), so the highlight
// can start where it was and slide to the new item.
let lastPill = null as { y: number; h: number } | null
</script>

<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Icon from '@/Components/Icon.vue'
import Flash from '@/Components/Flash.vue'

const props = defineProps<{ nav: NavGroup[]; who: string; sub: string; logout: string; secure?: boolean }>()
const page = usePage()
const open = ref(false)
watch(() => page.url, () => (open.value = false))

const active = (i: NavItem) => {
  const path = page.url.split('?')[0]
  return i.exact ? path === i.href : path === i.href || path.startsWith(i.href + '/')
}
const signOut = () => router.post(props.logout)

// One highlight slides between nav items, so moving between sections reads
// as moving within one place rather than a fresh page.
const navEl = ref<HTMLElement>()
const pill = ref({ y: lastPill?.y ?? 0, h: lastPill?.h ?? 0, show: !!lastPill, ready: false })
const placePill = () => {
  const el = navEl.value?.querySelector<HTMLElement>('[aria-current="page"]')
  if (!el) { pill.value.show = false; lastPill = null; return }
  const next = { y: el.offsetTop, h: el.offsetHeight }
  lastPill = next
  if (!pill.value.ready && pill.value.show) {
    // Paint at the previous position first, then glide.
    requestAnimationFrame(() => { pill.value.ready = true; requestAnimationFrame(() => Object.assign(pill.value, next)) })
    return
  }
  Object.assign(pill.value, next, { show: true })
  requestAnimationFrame(() => (pill.value.ready = true))
}
onMounted(placePill)
watch(() => [page.url, props.nav], () => nextTick(placePill), { deep: true })
</script>

<template>
  <div class="min-h-screen lg:flex" :class="secure && 'border-t-[3px] border-clay'">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-50 focus:bg-surface focus:p-3">Skip to content</a>

    <!-- Desktop sidebar -->
    <aside class="hidden w-56 shrink-0 flex-col border-r border-line lg:sticky lg:top-0 lg:flex lg:h-screen">
      <div class="flex h-12 items-center gap-2 px-4">
        <Logo :size="20" />
        <span v-if="secure" class="rounded-sm bg-orange-soft px-1.5 py-px text-[10px] font-semibold uppercase tracking-wider text-orange-text">Internal</span>
      </div>
      <nav ref="navEl" aria-label="Main" class="relative flex-1 overflow-y-auto px-2 py-2">
        <span aria-hidden="true" class="pointer-events-none absolute left-2 right-2 rounded-md bg-mint"
          :class="[pill.ready && 'transition-[transform,height,opacity] duration-300 ease-[var(--ease-smooth)] motion-reduce:duration-0', pill.show ? 'opacity-100' : 'opacity-0']"
          :style="{ height: pill.h + 'px', transform: `translateY(${pill.y}px)`, top: 0 }" />
        <div v-for="(g, gi) in nav" :key="gi" :class="gi > 0 && 'mt-4'">
          <p v-if="g.label" class="relative px-2 pb-1 text-[12px] font-medium text-label-3">{{ g.label }}</p>
          <Link v-for="i in g.items" :key="i.href" :href="i.href" :aria-current="active(i) ? 'page' : undefined"
            class="relative flex h-7 items-center gap-2 rounded-md px-2 text-[13px] transition-colors duration-150"
            :class="active(i) ? 'font-medium text-ink' : 'text-muted hover:bg-fill-2 hover:text-ink'">
            <span class="flex-1 truncate">{{ i.label }}</span>
            <span v-if="i.badge" class="num text-[12px] font-medium text-tint-text">{{ i.badge }}</span>
          </Link>
        </div>
      </nav>
      <div class="flex items-center gap-2 border-t border-line px-4 py-3">
        <div class="min-w-0 flex-1">
          <p class="truncate text-[13px] font-medium">{{ who }}</p>
          <p class="truncate text-[12px] text-muted">{{ sub }}</p>
        </div>
        <button type="button" class="press flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-muted hover:bg-fill-2 hover:text-ink" aria-label="Sign out" title="Sign out" @click="signOut"><Icon n="logout" :size="15" /></button>
      </div>
    </aside>

    <!-- Mobile drawer -->
    <Teleport to="body">
      <Transition name="backdrop"><div v-if="open" class="fixed inset-0 z-40 bg-scrim lg:hidden" @click="open = false" /></Transition>
      <Transition name="drawer">
        <aside v-if="open" class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85%] flex-col border-r border-separator bg-surface lg:hidden" aria-label="Menu">
          <div class="flex h-14 items-center justify-between px-5">
            <span class="flex items-center gap-2"><Logo /><span v-if="secure" class="rounded-sm bg-orange-soft px-1.5 py-px text-[10px] font-semibold uppercase tracking-wider text-orange-text">Internal</span></span>
            <button type="button" aria-label="Close menu" class="press -mr-2 flex h-10 w-10 items-center justify-center" @click="open = false"><Icon n="x" /></button>
          </div>
          <nav aria-label="Main" class="flex-1 overflow-y-auto px-3 py-3">
            <div v-for="(g, gi) in nav" :key="gi" :class="gi > 0 && 'mt-5'">
              <p v-if="g.label" class="px-2 pb-1 text-[12px] font-medium text-label-3">{{ g.label }}</p>
              <Link v-for="i in g.items" :key="i.href" :href="i.href" :aria-current="active(i) ? 'page' : undefined"
                class="flex h-10 items-center gap-2 rounded-md px-2 text-[15px]" :class="active(i) ? 'bg-mint font-medium text-ink' : 'text-muted'">
                <span class="flex-1 truncate">{{ i.label }}</span>
                <span v-if="i.badge" class="num text-[13px] font-medium text-tint-text">{{ i.badge }}</span>
              </Link>
            </div>
          </nav>
          <div class="border-t border-line px-5 py-4 text-sm">
            <p class="font-medium">{{ who }}</p>
            <p class="text-[13px] text-muted">{{ sub }}</p>
            <button type="button" class="mt-2 text-[13px] text-muted underline" @click="signOut">Sign out</button>
          </div>
        </aside>
      </Transition>
    </Teleport>

    <div class="min-w-0 flex-1">
      <header class="sticky top-0 z-30 flex h-12 items-center justify-between border-b border-line bg-surface px-4 lg:hidden">
        <Logo :size="20" />
        <button type="button" aria-label="Open menu" :aria-expanded="open" class="press -mr-2 flex h-10 w-10 items-center justify-center" @click="open = true"><Icon n="menu" /></button>
      </header>
      <main id="main" class="page-in mx-auto max-w-6xl px-4 py-6 sm:px-8 lg:py-8">
        <slot />
      </main>
    </div>
    <Flash />
  </div>
</template>
