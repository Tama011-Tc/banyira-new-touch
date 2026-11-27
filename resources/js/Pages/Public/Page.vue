<script setup lang="ts">
import { computed, provide } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import BlockRenderer from '@/Components/Blocks/BlockRenderer.vue'
import { pageContextKey, type Block, type PageImage } from '@/lib/blocks'

const props = defineProps<{
  page: { title: string; description: string; blocks: Block[] }
  live: Record<string, any>
  media: Record<number, PageImage>
  /** Set only in the staff draft preview: the address of the editor. */
  preview?: string
}>()

const live = computed(() => props.live)
const media = computed(() => props.media)
provide(pageContextKey, {
  get live() { return live.value },
  get media() { return media.value },
  get preview() { return !!props.preview },
})
</script>

<template>
  <Head :title="page.title">
    <meta v-if="page.description" head-key="description" name="description" :content="page.description" />
  </Head>
  <PublicLayout>
    <BlockRenderer :blocks="page.blocks" />
    <p v-if="!page.blocks.length" class="mx-auto max-w-6xl px-5 py-24 text-muted sm:px-8">This page has no sections yet.</p>
    <div v-if="preview" role="status" class="fixed inset-x-0 bottom-0 z-50 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 border-t border-separator-2 bg-surface px-4 py-3 text-[14px]">
      <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-orange" aria-hidden="true" />Draft preview. Visitors cannot see this version.</span>
      <Link :href="preview" class="link font-medium">Back to the editor</Link>
    </div>
  </PublicLayout>
</template>
