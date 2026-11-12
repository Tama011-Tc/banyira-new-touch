<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Icon from './Icon.vue'
import type { Paginated } from '@/types'

const props = defineProps<{ page: Paginated<unknown> }>()
const prev = computed(() => props.page.links[0]?.url)
const next = computed(() => props.page.links[props.page.links.length - 1]?.url)
</script>

<template>
  <nav v-if="page.total" aria-label="Pagination" class="mt-4 flex items-center justify-between gap-3 text-[13px] text-label-2">
    <span class="num">{{ page.from }}–{{ page.to }} of {{ page.total }}</span>
    <div v-if="page.last_page > 1" class="flex items-center gap-1">
      <component :is="prev ? Link : 'span'" :href="prev ?? undefined" preserve-scroll aria-label="Previous page"
        class="press flex h-9 w-9 items-center justify-center rounded-full" :class="prev ? 'text-tint-text hover:bg-fill-2' : 'text-label-3'"><Icon n="chevleft" :size="18" /></component>
      <span class="num min-w-12 text-center">{{ page.current_page }} / {{ page.last_page }}</span>
      <component :is="next ? Link : 'span'" :href="next ?? undefined" preserve-scroll aria-label="Next page"
        class="press flex h-9 w-9 items-center justify-center rounded-full" :class="next ? 'text-tint-text hover:bg-fill-2' : 'text-label-3'"><Icon n="chev" :size="18" /></component>
    </div>
  </nav>
</template>
