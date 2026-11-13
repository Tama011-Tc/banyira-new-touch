<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'

// One row in a FormGroup: label on the left, value or control on the right.
defineProps<{ label: string; value?: string | number | null; href?: string; icon?: string; mono?: boolean }>()
</script>

<template>
  <component :is="href ? Link : 'div'" :href="href" class="flex min-h-10 items-center gap-3 py-2 text-[14px]" :class="href && 'press transition-colors hover:bg-fill-2'">
    <span v-if="icon" class="flex shrink-0 items-center justify-center text-label-2"><Icon :n="icon" :size="16" /></span>
    <span class="flex-1 text-label">{{ label }}</span>
    <span class="min-w-0 truncate text-right text-label-2" :class="mono && 'font-mono text-[13px]'"><slot>{{ value ?? '—' }}</slot></span>
    <Icon v-if="href" n="chev" :size="16" class="text-label-3" />
  </component>
</template>
