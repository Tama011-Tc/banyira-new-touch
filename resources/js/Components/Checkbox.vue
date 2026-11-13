<script setup lang="ts">
import { useId } from 'vue'

// The whole row is the touch target (44px tall), not just the 22px box.
defineProps<{ error?: string; disabled?: boolean }>()
const model = defineModel<boolean>({ default: false })
const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="flex min-h-11 cursor-pointer items-start gap-3 py-2.5 text-[14px] leading-snug" :class="disabled && 'cursor-not-allowed opacity-50'">
      <span class="relative mt-px flex h-[18px] w-[18px] shrink-0 items-center justify-center">
        <input :id="id" v-model="model" type="checkbox" :disabled="disabled" class="peer absolute inset-0 cursor-pointer appearance-none rounded-[4px] border-[1.5px] border-separator-2 bg-surface transition-colors duration-150 checked:border-tint checked:bg-tint focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-tint-soft" :aria-invalid="!!error" :aria-describedby="error ? id + '-e' : undefined" />
        <svg viewBox="0 0 16 16" class="pointer-events-none relative h-3 w-3 scale-50 text-white opacity-0 transition-[opacity,transform] duration-300 ease-[var(--ease-spring)] motion-reduce:duration-0 peer-checked:scale-100 peer-checked:opacity-100" aria-hidden="true">
          <path d="M3.5 8.5l3 3 6-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </span>
      <span class="text-label"><slot /></span>
    </label>
    <p v-if="error" :id="id + '-e'" class="ml-[30px] text-[13px] text-red-text">{{ error }}</p>
  </div>
</template>
