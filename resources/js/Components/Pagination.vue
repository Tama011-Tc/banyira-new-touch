<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type { Paginated } from '@/types'

defineProps<{ page: Paginated<unknown> }>()
</script>

<template>
  <div v-if="page.total" class="mt-2.5 flex items-center justify-between text-[13px] text-muted">
    <span class="num">{{ page.from }}–{{ page.to }} of {{ page.total }}</span>
    <nav v-if="page.last_page > 1" aria-label="Pagination" class="flex items-center gap-1">
      <template v-for="(l, i) in page.links" :key="i">
        <Link v-if="l.url && (i === 0 || i === page.links.length - 1)" :href="l.url" preserve-scroll class="rounded px-2.5 py-1 hover:bg-mint">{{ i === 0 ? 'Previous' : 'Next' }}</Link>
      </template>
      <span class="num px-1">{{ page.current_page }} / {{ page.last_page }}</span>
    </nav>
  </div>
</template>
