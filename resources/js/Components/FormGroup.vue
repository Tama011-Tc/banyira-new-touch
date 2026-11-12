<script setup lang="ts">
// iOS Settings-style grouped section: a header above, a footer note below,
// rows inside one rounded surface separated by inset hairlines.
defineProps<{ title?: string; footer?: string; padded?: boolean }>()
</script>

<template>
  <section class="space-y-2">
    <h2 v-if="title" class="px-4 text-[13px] font-normal uppercase tracking-[0.02em] text-label-2">{{ title }}</h2>
    <div class="surface-card overflow-hidden" :class="padded ? 'space-y-5 p-4 sm:p-5' : 'divide-y divide-separator [&>*]:px-4'">
      <slot />
    </div>
    <p v-if="footer" class="px-4 text-[13px] text-label-2">{{ footer }}</p>
  </section>
</template>
