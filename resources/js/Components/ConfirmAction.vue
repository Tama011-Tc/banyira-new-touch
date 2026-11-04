<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Btn from './Btn.vue'
import Modal from './Modal.vue'
import Notice from './Notice.vue'

// A button that asks for confirmation, then sends a request. If the server
// refuses the action, the dialog stays open and shows why.
const props = withDefaults(defineProps<{
  href: string
  method?: 'post' | 'put' | 'patch' | 'delete'
  title: string
  confirm: string
  data?: Record<string, unknown>
  danger?: boolean
  variant?: 'primary' | 'dark' | 'outline' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
}>(), { method: 'post', variant: 'outline', size: 'sm' })

const open = ref(false)
const busy = ref(false)
const error = ref<string | null>(null)
const go = () => {
  error.value = null
  router.visit(props.href, {
    method: props.method, data: props.data as any, preserveScroll: true,
    onStart: () => (busy.value = true),
    onSuccess: () => (open.value = false),
    onError: (errs) => (error.value = Object.values(errs)[0] as string),
    onFinish: () => (busy.value = false),
  })
}
</script>

<template>
  <span class="contents">
    <Btn :variant="variant" :size="size" @click="open = true; error = null"><slot /></Btn>
    <Modal :open="open" :title="title" @close="open = false">
      <div class="text-muted"><slot name="body" /></div>
      <div v-if="error" class="mt-4"><Notice tone="error">{{ error }}</Notice></div>
      <template #actions>
        <Btn variant="outline" @click="open = false">Cancel</Btn>
        <Btn :variant="danger ? 'danger' : 'primary'" :disabled="busy" @click="go">{{ busy ? 'Working…' : confirm }}</Btn>
      </template>
    </Modal>
  </span>
</template>
