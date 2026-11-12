<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

/*
 * Buttons: primary (filled tint), secondary (tinted fill), plain (text only),
 * destructive. Earlier variant names still work: outline → secondary,
 * ghost → plain, danger → destructive, dark → primary.
 * Every size keeps a 44px hit area on touch screens.
 */
type Variant = 'primary' | 'secondary' | 'plain' | 'destructive' | 'outline' | 'ghost' | 'danger' | 'dark' | 'light' | 'ghost-light'
const props = withDefaults(defineProps<{
  variant?: Variant
  size?: 'sm' | 'md' | 'lg'
  href?: string
  method?: 'post' | 'put' | 'patch' | 'delete'
  type?: 'button' | 'submit'
  disabled?: boolean
  full?: boolean
  external?: boolean
  label?: string
}>(), { variant: 'primary', size: 'md', type: 'button' })

const ALIAS: Record<string, string> = { outline: 'secondary', ghost: 'plain', danger: 'destructive', dark: 'primary' }
const v = computed(() => ALIAS[props.variant] ?? props.variant)

const cls = computed(() => [
  'press relative inline-flex select-none items-center justify-center gap-1.5 whitespace-nowrap font-medium',
  'after:absolute after:-inset-1 after:content-[""] sm:after:hidden',
  { sm: 'h-9 rounded-lg px-3.5 text-[14px]', md: 'h-11 rounded-lg px-5 text-[15px]', lg: 'h-12 rounded-[10px] px-6 text-[17px]' }[props.size],
  {
    primary: 'bg-tint text-white hover:bg-tint-hover',
    secondary: 'bg-fill text-tint-text hover:bg-[color-mix(in_srgb,var(--fill)_140%,transparent)]',
    plain: 'text-tint-text hover:bg-fill-2',
    destructive: 'bg-red-soft text-red-text hover:bg-[color-mix(in_srgb,var(--red-soft)_160%,transparent)]',
    light: 'bg-white text-tint-on-white hover:bg-white/90',
    'ghost-light': 'bg-white/15 text-white hover:bg-white/25',
  }[v.value],
  props.full && 'w-full',
  props.disabled && 'pointer-events-none opacity-40',
])
</script>

<template>
  <a v-if="href && external" :href="href" :class="cls" :aria-label="label"><slot /></a>
  <Link v-else-if="href" :href="href" :method="method" :as="method ? 'button' : 'a'" :class="cls" :disabled="disabled" :aria-label="label"><slot /></Link>
  <button v-else :type="type" :disabled="disabled" :class="cls" :aria-label="label"><slot /></button>
</template>
