<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { isInternal } from '@/lib/blocks'

// A link typed by an editor. The server has already refused unsafe addresses.
defineProps<{ href: string }>()
</script>

<template>
  <Link v-if="isInternal(href)" :href="href"><slot /></Link>
  <a v-else :href="href" rel="noopener noreferrer"><slot /></a>
</template>
