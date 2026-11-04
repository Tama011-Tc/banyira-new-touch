<script setup lang="ts">
import { useId } from 'vue'

defineProps<{ label: string; error?: string; hint?: string; optional?: boolean }>()
const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="mb-1.5 block text-[13px] font-medium">
      {{ label }} <span v-if="optional" class="font-normal text-muted">(optional)</span>
    </label>
    <slot :id="id" :described="error ? id + '-e' : hint ? id + '-h' : undefined" :invalid="!!error" />
    <p v-if="error" :id="id + '-e'" class="mt-1 text-[13px] text-danger">{{ error }}</p>
    <p v-else-if="hint" :id="id + '-h'" class="mt-1 text-xs text-muted">{{ hint }}</p>
  </div>
</template>
