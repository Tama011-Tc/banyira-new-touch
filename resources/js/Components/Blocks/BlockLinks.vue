<script setup lang="ts">
import ContentLink from '@/Components/ContentLink.vue'

defineProps<{ links: { label: string; href: string }[] }>()
</script>

<template>
  <div v-if="links.length" class="mt-6 flex flex-wrap gap-6 text-sm">
    <ContentLink v-for="(l, i) in links" :key="i" :href="l.href" class="link" :class="i > 0 && 'text-muted'">{{ l.label }}</ContentLink>
  </div>
</template>
