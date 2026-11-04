<script setup lang="ts">
import Field from './Field.vue'

defineOptions({ inheritAttrs: false })
withDefaults(defineProps<{ label: string; error?: string; hint?: string; rows?: number; optional?: boolean }>(), { rows: 4 })
const model = defineModel<string | null>()
</script>

<template>
  <Field :label="label" :error="error" :hint="hint" :optional="optional" v-slot="{ id, described, invalid }">
    <textarea :id="id" v-model="model" v-bind="$attrs" :rows="rows" class="input" :aria-describedby="described" :aria-invalid="invalid" />
  </Field>
</template>
