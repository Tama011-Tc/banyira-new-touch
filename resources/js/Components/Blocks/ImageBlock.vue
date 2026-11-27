<script setup lang="ts">
import { computed } from 'vue'
import { usePageContext } from '@/lib/blocks'

const props = defineProps<{ data: Record<string, any> }>()
const page = usePageContext()
const image = computed(() => page.media[props.data.media_id])
</script>

<template>
  <figure v-if="image" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
    <img :src="image.url" :alt="image.alt" :width="image.width" :height="image.height" loading="lazy" class="mx-auto h-auto max-w-full rounded-lg border border-separator" />
    <figcaption v-if="data.caption" class="mt-3 text-center text-[13px] text-muted">{{ data.caption }}</figcaption>
  </figure>
</template>
