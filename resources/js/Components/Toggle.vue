<script setup lang="ts">
import { useId } from 'vue'

// Switch. The thumb lands on a spring; colour carries state.
defineProps<{ label: string; description?: string; disabled?: boolean }>()
const model = defineModel<boolean>({ default: false })
const id = useId()
</script>

<template>
  <div class="flex min-h-11 items-center justify-between gap-4 py-2">
    <label :for="id" class="cursor-pointer">
      <span class="block text-[15px] text-label">{{ label }}</span>
      <span v-if="description" :id="id + '-d'" class="block text-[13px] text-label-2">{{ description }}</span>
    </label>
    <button :id="id" type="button" role="switch" :aria-checked="model" :aria-describedby="description ? id + '-d' : undefined" :disabled="disabled"
      class="relative h-[24px] w-[40px] shrink-0 rounded-full transition-colors duration-200 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-tint-soft disabled:opacity-40"
      :class="model ? 'bg-tint' : 'bg-separator-2'" @click="model = !model">
      <span class="absolute left-[2px] top-[2px] h-[20px] w-[20px] rounded-full bg-white transition-transform duration-500 ease-[var(--ease-spring)] motion-reduce:duration-0"
        :class="model && 'translate-x-4'" />
    </button>
  </div>
</template>
