<script setup lang="ts">
import type { Flash } from '@/types/banyira';
import { usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Toast {
    id: number;
    kind: 'success' | 'error';
    text: string;
}

const page = usePage();
const toasts = ref<Toast[]>([]);
let next = 0;

function push(kind: Toast['kind'], text: string) {
    const id = next++;
    toasts.value.push({ id, kind, text });
    setTimeout(() => dismiss(id), kind === 'error' ? 8000 : 5000);
}

const dismiss = (id: number) => (toasts.value = toasts.value.filter((t) => t.id !== id));

// Each Inertia response carries a fresh `flash` object, so this fires once per message.
watch(
    () => page.props.flash as Flash | undefined,
    (flash) => {
        if (flash?.success) push('success', flash.success);
        if (flash?.error) push('error', flash.error);
    },
    { immediate: true },
);
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6" aria-live="polite" aria-atomic="false">
        <TransitionGroup enter-active-class="transition duration-200" enter-from-class="translate-y-2 opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
            <div
                v-for="t in toasts"
                :key="t.id"
                :role="t.kind === 'error' ? 'alert' : 'status'"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-card p-4 shadow-lg"
                :class="t.kind === 'error' ? 'border-expense/40' : 'border-primary/30'"
            >
                <CircleAlert v-if="t.kind === 'error'" class="mt-0.5 size-5 shrink-0 text-expense" aria-hidden="true" />
                <CircleCheck v-else class="mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true" />
                <p class="flex-1 text-sm">{{ t.text }}</p>
                <button type="button" class="rounded p-0.5 text-muted-foreground hover:text-foreground" aria-label="Dismiss" @click="dismiss(t.id)">
                    <X class="size-4" aria-hidden="true" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
