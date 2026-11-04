<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = withDefaults(defineProps<{
  variant?: 'primary' | 'dark' | 'outline' | 'ghost' | 'danger' | 'light' | 'ghost-light'
  size?: 'sm' | 'md' | 'lg'
  href?: string
  method?: 'post' | 'put' | 'patch' | 'delete'
  type?: 'button' | 'submit'
  disabled?: boolean
  full?: boolean
  external?: boolean
}>(), { variant: 'primary', size: 'md', type: 'button' })

const cls = computed(() => [
  'press inline-flex items-center justify-center gap-1.5 rounded-md font-medium whitespace-nowrap select-none',
  { sm: 'h-8 px-3 text-[13px]', md: 'h-10 px-4 text-sm', lg: 'h-11 px-5 text-[15px]' }[props.size],
  {
    primary: 'bg-forest text-white hover:bg-forest-2',
    dark: 'bg-ink text-white hover:bg-black',
    outline: 'border border-line-2 bg-white text-ink hover:border-ink',
    ghost: 'text-ink hover:bg-mint',
    danger: 'bg-danger text-white hover:bg-[#912018]',
    light: 'bg-white text-forest hover:bg-mint',
    'ghost-light': 'border border-white/40 text-white hover:border-white hover:bg-white/10',
  }[props.variant],
  props.full && 'w-full',
  props.disabled && 'opacity-40 pointer-events-none',
])
</script>

<template>
  <a v-if="href && external" :href="href" :class="cls"><slot /></a>
  <Link v-else-if="href" :href="href" :method="method" :as="method ? 'button' : 'a'" :class="cls" :disabled="disabled"><slot /></Link>
  <button v-else :type="type" :disabled="disabled" :class="cls"><slot /></button>
</template>
