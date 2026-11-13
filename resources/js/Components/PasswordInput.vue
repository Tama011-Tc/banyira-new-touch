<script setup lang="ts">
import { ref } from 'vue'
import Field from './Field.vue'

// Password field with a show/hide control, so nobody has to type it blind.
defineOptions({ inheritAttrs: false })
defineProps<{ label: string; error?: string; hint?: string }>()
const model = defineModel<string>()
const show = ref(false)
</script>

<template>
  <Field :label="label" :error="error" :hint="hint" v-slot="{ id, described, invalid }">
    <div class="relative">
      <input :id="id" v-model="model" v-bind="$attrs" :type="show ? 'text' : 'password'" class="input pr-16" :aria-describedby="described" :aria-invalid="invalid" />
      <button type="button" class="absolute inset-y-1 right-1 rounded px-2.5 text-[13px] text-muted hover:text-ink" :aria-pressed="show" @click="show = !show">{{ show ? 'Hide' : 'Show' }}</button>
    </div>
  </Field>
</template>
