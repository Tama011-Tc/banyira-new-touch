<script setup lang="ts">
import { computed } from 'vue'

// Initials on a soft tint chosen from the name, so a person keeps their colour.
const props = withDefaults(defineProps<{ name: string; size?: number }>(), { size: 32 })
const TONES = ['bg-tint-soft text-tint-text', 'bg-green-soft text-green-text', 'bg-orange-soft text-orange-text', 'bg-fill text-label']
const initials = computed(() => props.name.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase())
const tone = computed(() => TONES[[...props.name].reduce((a, c) => a + c.charCodeAt(0), 0) % TONES.length])
</script>

<template>
  <span class="inline-flex shrink-0 items-center justify-center rounded-full font-semibold" :class="tone"
    :style="{ width: size + 'px', height: size + 'px', fontSize: Math.round(size * 0.38) + 'px' }" aria-hidden="true">{{ initials }}</span>
</template>
