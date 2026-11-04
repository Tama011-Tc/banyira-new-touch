<script setup lang="ts">
import Field from './Field.vue'

defineOptions({ inheritAttrs: false })
defineProps<{ label: string; error?: string; hint?: string; optional?: boolean }>()
const model = defineModel<string | number | null>()
</script>

<template>
  <Field :label="label" :error="error" :hint="hint" :optional="optional" v-slot="{ id, described, invalid }">
    <input :id="id" v-model="model" v-bind="$attrs" class="input" :aria-describedby="described" :aria-invalid="invalid" />
  </Field>
</template>
