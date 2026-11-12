<script setup lang="ts">
import { useId } from 'vue'

// iOS-style switch. The thumb moves on a spring-like curve; colour carries state.
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
      class="press relative h-[31px] w-[51px] shrink-0 rounded-full transition-colors duration-200 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-tint-soft disabled:opacity-40"
      :class="model ? 'bg-green' : 'bg-fill'" @click="model = !model">
      <span class="absolute left-[2px] top-[2px] h-[27px] w-[27px] rounded-full bg-white shadow-[0_3px_8px_rgb(0_0_0/0.15),0_1px_1px_rgb(0_0_0/0.16)] transition-transform duration-300 ease-[cubic-bezier(0.34,1.36,0.64,1)]"
        :class="model && 'translate-x-5'" />
    </button>
  </div>
</template>
