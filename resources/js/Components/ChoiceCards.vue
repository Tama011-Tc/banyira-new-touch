<script setup lang="ts">
import { computed, useId } from 'vue'
import Icon from './Icon.vue'

// A single-choice question answered by tapping a card. Native radios underneath,
// so arrow keys, forms and screen readers all work without extra code.
type Option = string | { value: string; label: string; description?: string }
const props = defineProps<{ label: string; options: Option[]; error?: string; columns?: 1 | 2 }>()
const model = defineModel<string | null>()
const name = useId()
const opts = computed(() => props.options.map((o) => (typeof o === 'string' ? { value: o, label: o, description: undefined } : o)))
</script>

<template>
  <fieldset :aria-describedby="error ? name + '-e' : undefined">
    <legend class="sr-only">{{ label }}</legend>
    <div class="grid gap-2" :class="columns === 2 && 'sm:grid-cols-2'">
      <label v-for="o in opts" :key="o.value" class="choice press flex min-h-14 cursor-pointer items-center gap-3 rounded-lg border border-separator-2 bg-surface px-4 py-3">
        <input v-model="model" type="radio" :name="name" :value="o.value" class="sr-only" />
        <span class="min-w-0 flex-1">
          <span class="block text-[15px] font-medium text-label">{{ o.label }}</span>
          <span v-if="o.description" class="mt-0.5 block text-[13px] text-label-2">{{ o.description }}</span>
        </span>
        <span class="mark flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-[1.5px] border-separator-2 text-white" aria-hidden="true"><Icon n="check" :size="12" :stroke="3" /></span>
      </label>
    </div>
    <p v-if="error" :id="name + '-e'" class="mt-2 flex items-start gap-1.5 text-[13px] text-red-text"><Icon n="error" :size="15" class="mt-px" />{{ error }}</p>
  </fieldset>
</template>

<style scoped>
.choice:hover { border-color: var(--label-3); }
.choice:has(input:checked) { border-color: var(--tint); background: var(--tint-soft); }
.choice:has(input:focus-visible) { outline: 2px solid color-mix(in srgb, var(--tint) 70%, transparent); outline-offset: 2px; }
.mark { transition: background-color 150ms ease, border-color 150ms ease; }
.mark :deep(svg) { transform: scale(0); transition: transform 450ms var(--ease-spring); }
input:checked ~ .mark { border-color: var(--tint); background: var(--tint); }
input:checked ~ .mark :deep(svg) { transform: scale(1); }
@media (prefers-reduced-motion: reduce) { .mark :deep(svg) { transition-duration: 1ms; } }
</style>
