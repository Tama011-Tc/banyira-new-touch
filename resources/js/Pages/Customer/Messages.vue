<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import CustomerLayout from '@/Layouts/CustomerLayout.vue'
import PageHead from '@/Components/PageHead.vue'
import Btn from '@/Components/Btn.vue'
import Icon from '@/Components/Icon.vue'
import { dateTime } from '@/lib/format'

const props = defineProps<{ area: 'business' | 'investor'; conversations: any[]; active: any | null }>()
const base = `/${props.area}/messages`
const form = useForm<{ body: string; file: File | null }>({ body: '', file: null })
const fileInput = ref<HTMLInputElement>()
const thread = ref<HTMLElement>()
const send = () => form.post(`${base}/${props.active.id}`, { preserveScroll: true, forceFormData: true, onSuccess: () => { form.reset(); if (fileInput.value) fileInput.value.value = '' } })
const scroll = () => nextTick(() => thread.value?.scrollTo({ top: thread.value.scrollHeight }))
onMounted(scroll)
watch(() => props.active?.messages.length, scroll)
</script>

<template>
  <CustomerLayout :area="area">
    <PageHead title="Messages" sub="Conversations stay on Banyira. Phone numbers, emails and links are removed automatically." />
    <div class="grid overflow-hidden rounded-lg border border-line bg-white md:grid-cols-[260px_1fr]" style="min-height: 460px">
      <nav aria-label="Conversations" class="border-b border-line md:border-b-0 md:border-r" :class="active && 'hidden md:block'">
        <p v-if="!conversations.length" class="px-4 py-8 text-sm text-muted">No conversations yet.<template v-if="area === 'investor'"> Open a business in the marketplace and ask a question.</template></p>
        <Link v-for="c in conversations" :key="c.id" :href="`${base}/${c.id}`" class="block border-b border-line px-4 py-3 last:border-0" :class="active?.id === c.id ? 'bg-mint' : 'hover:bg-[#fcfcfb]'">
          <span class="flex items-center justify-between gap-2"><span class="truncate text-sm" :class="c.unread && 'font-semibold'">{{ c.with }}</span><span v-if="c.unread" class="h-1.5 w-1.5 shrink-0 rounded-full bg-forest" aria-label="Unread" /></span>
          <span class="block truncate text-[13px] text-muted">{{ c.subject }}</span>
          <span class="block truncate text-xs text-muted">{{ c.last }}</span>
        </Link>
      </nav>

      <section v-if="active" class="flex min-h-[460px] flex-col">
        <header class="flex items-center gap-3 border-b border-line px-4 py-3">
          <Link :href="base" class="text-muted md:hidden" aria-label="All conversations"><Icon n="back" /></Link>
          <h2 class="text-[15px]">{{ active.subject }}</h2>
        </header>
        <div ref="thread" class="flex-1 space-y-3 overflow-y-auto px-4 py-4" style="max-height: 55vh" aria-live="polite">
          <div v-for="m in active.messages" :key="m.id" class="max-w-[80%]" :class="m.mine && 'ml-auto text-right'">
            <p class="text-xs text-muted">{{ m.from }} · {{ dateTime(m.at) }}</p>
            <div class="mt-1 inline-block rounded-lg px-3 py-2 text-left text-sm" :class="m.mine ? 'bg-forest text-white' : 'bg-[#f1f1ee]'">{{ m.body }}</div>
            <p v-for="f in m.attachments" :key="f.id" class="mt-1 text-xs"><a :href="`/${area}/attachments/${f.id}`" class="link">{{ f.original_name }}</a></p>
          </div>
          <p v-if="!active.messages.length" class="text-sm text-muted">No messages yet.</p>
        </div>
        <form class="flex items-end gap-2 border-t border-line p-3" @submit.prevent="send">
          <label class="sr-only" for="msg">Message</label>
          <textarea id="msg" v-model="form.body" rows="1" class="input min-h-10 flex-1 resize-y" placeholder="Write a message" @keydown.enter.exact.prevent="form.body.trim() && send()" />
          <label class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-line-2 text-muted hover:text-ink" :class="form.file && 'border-ink text-ink'" title="Attach a file">
            <Icon n="clip" /><span class="sr-only">Attach a file</span>
            <input ref="fileInput" type="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" @change="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null" />
          </label>
          <Btn type="submit" :disabled="!form.body.trim() || form.processing">Send</Btn>
        </form>
        <p v-if="form.errors.body || form.errors.file" class="px-3 pb-2 text-[13px] text-danger">{{ form.errors.body ?? form.errors.file }}</p>
      </section>
      <div v-else class="hidden items-center justify-center text-sm text-muted md:flex">Choose a conversation.</div>
    </div>
  </CustomerLayout>
</template>
