<script setup lang="ts">
import { computed } from 'vue'
import Field from './Field.vue'

defineOptions({ inheritAttrs: false })
const props = defineProps<{ label: string; options: (string | { value: string | number; label: string })[]; error?: string; hint?: string; placeholder?: string }>()
const model = defineModel<string | number | null>()
const opts = computed(() => props.options.map((o) => (typeof o === 'string' ? { value: o, label: o } : o)))
</script>

<template>
  <Field :label="label" :error="error" :hint="hint" v-slot="{ id, described, invalid }">
    <select :id="id" v-model="model" v-bind="$attrs" class="input" :aria-describedby="described" :aria-invalid="invalid">
      <option v-if="placeholder" :value="null" disabled>{{ placeholder }}</option>
      <option v-for="o in opts" :key="o.value" :value="o.value">{{ o.label }}</option>
    </select>
  </Field>
</template>
