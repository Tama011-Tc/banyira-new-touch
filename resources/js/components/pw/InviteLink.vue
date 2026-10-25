<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Check, Copy } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const link = computed(() => usePage().props.invite_link as string | null);
const copied = ref(false);
async function copy() {
    if (!link.value) return;
    try {
        await navigator.clipboard.writeText(link.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        /* the link is selectable, so copying by hand still works */
    }
}
</script>

<template>
    <section v-if="link" class="mb-6 max-w-3xl rounded-lg border-2 border-gold/60 bg-gold/10 p-4 text-sm" role="status">
        <p class="font-semibold">Invitation link for the business owner</p>
        <div class="mt-2 flex items-center gap-2"><code class="min-w-0 flex-1 select-all break-all rounded bg-card px-3 py-2 text-xs">{{ link }}</code><button type="button" class="flex shrink-0 items-center gap-1 rounded-md border bg-card px-3 py-2 text-xs font-medium hover:bg-muted" @click="copy"><component :is="copied ? Check : Copy" class="size-3.5" aria-hidden="true" />{{ copied ? 'Copied' : 'Copy' }}</button></div>
        <p class="mt-2 text-muted-foreground">It was also emailed to them. The owner uses it to choose their own password, then signs in to follow the application. You never see or set their password. The link works once and expires in 3 days.</p>
    </section>
</template>
