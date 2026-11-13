<script setup lang="ts">
defineProps<{ title?: string; sub?: string; flush?: boolean }>()
</script>

<template>
  <section class="surface-card" :class="!flush && 'p-4 sm:p-5'">
    <header v-if="title || $slots.action" class="flex flex-wrap items-start justify-between gap-3" :class="flush ? 'px-5 pb-3 pt-4' : 'mb-4'">
      <div>
        <h2 class="text-[14px] font-semibold tracking-[-0.01em]">{{ title }}</h2>
        <p v-if="sub" class="mt-0.5 text-[13px] text-label-2">{{ sub }}</p>
      </div>
      <slot name="action" />
    </header>
    <slot />
  </section>
</template>
