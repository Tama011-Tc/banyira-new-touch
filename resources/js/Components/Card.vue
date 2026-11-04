<script setup lang="ts">
defineProps<{ title?: string; sub?: string; flush?: boolean }>()
</script>

<template>
  <section class="rounded-lg border border-line bg-white" :class="!flush && 'p-5'">
    <header v-if="title || $slots.action" class="flex flex-wrap items-start justify-between gap-3" :class="flush ? 'border-b border-line px-5 py-3.5' : 'mb-4'">
      <div>
        <h2 class="text-[15px]">{{ title }}</h2>
        <p v-if="sub" class="text-[13px] text-muted">{{ sub }}</p>
      </div>
      <slot name="action" />
    </header>
    <slot />
  </section>
</template>
