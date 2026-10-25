<script setup lang="ts">
import { formatMoney, percent } from '@/lib/money';
import type { SpendingSlice } from '@/types/banyira';
import { ArcElement, Chart, DoughnutController, Tooltip } from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(ArcElement, DoughnutController, Tooltip);

const props = defineProps<{ slices: SpendingSlice[]; currency: string }>();

const canvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;
let observer: MutationObserver | null = null;

const total = computed(() => props.slices.reduce((sum, s) => sum + s.total, 0));
const top = computed(() => props.slices.slice(0, 7));
const summary = computed(() => props.slices.map((s) => `${s.name} ${formatMoney(s.total, props.currency)}`).join(', '));

function build() {
    if (!canvas.value) return;
    chart?.destroy();

    chart = new Chart(canvas.value, {
        type: 'doughnut',
        data: {
            labels: props.slices.map((s) => s.name),
            datasets: [
                {
                    data: props.slices.map((s) => s.total / 100),
                    backgroundColor: props.slices.map((s) => s.color),
                    borderColor: `hsl(${getComputedStyle(document.documentElement).getPropertyValue('--card').trim()})`,
                    borderWidth: 3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (c) => ` ${c.label}: ${formatMoney(Math.round(Number(c.parsed) * 100), props.currency)}` } },
            },
        },
    });
}

onMounted(() => {
    build();
    observer = new MutationObserver(build);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onBeforeUnmount(() => {
    observer?.disconnect();
    chart?.destroy();
});
watch(() => [props.slices, props.currency], build, { deep: true });
</script>

<template>
    <div class="flex flex-col items-center gap-6 sm:flex-row">
        <div class="relative size-44 shrink-0">
            <canvas ref="canvas" role="img" :aria-label="`Spending by category. ${summary}`" />
            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-xs text-muted-foreground">Total</span>
                <span class="tabular text-base font-semibold">{{ formatMoney(total, currency) }}</span>
            </div>
        </div>
        <ul class="w-full space-y-2 text-sm">
            <li v-for="s in top" :key="s.category_id ?? 'none'" class="flex items-center gap-2">
                <span class="size-2.5 shrink-0 rounded-full" :style="{ backgroundColor: s.color }" aria-hidden="true" />
                <span class="min-w-0 flex-1 truncate">{{ s.name }}</span>
                <span class="tabular text-muted-foreground">{{ percent(s.total, total) }}%</span>
                <span class="tabular w-24 text-right font-medium">{{ formatMoney(s.total, currency) }}</span>
            </li>
            <li v-if="slices.length > top.length" class="text-xs text-muted-foreground">+ {{ slices.length - top.length }} more categories</li>
        </ul>
    </div>
</template>
