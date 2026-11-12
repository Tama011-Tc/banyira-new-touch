<script setup lang="ts">
import { useId } from 'vue'
import Icon from './Icon.vue'

defineProps<{ label: string; error?: string; hint?: string; optional?: boolean }>()
const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="mb-1.5 block text-[14px] font-medium text-label">
      {{ label }} <span v-if="optional" class="font-normal text-label-2">Optional</span>
    </label>
    <slot :id="id" :described="error ? id + '-e' : hint ? id + '-h' : undefined" :invalid="!!error" />
    <Transition name="pop">
      <p v-if="error" :id="id + '-e'" class="mt-1.5 flex items-start gap-1.5 text-[13px] text-red-text"><Icon n="error" :size="15" class="mt-px" />{{ error }}</p>
    </Transition>
    <p v-if="!error && hint" :id="id + '-h'" class="mt-1.5 text-[13px] text-label-2">{{ hint }}</p>
  </div>
</template>
