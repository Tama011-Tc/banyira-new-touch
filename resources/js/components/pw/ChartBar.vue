<script setup lang="ts">
import { formatMoney } from '@/lib/money';
import type { TrendPoint } from '@/types/banyira';
import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, Tooltip } from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps<{ points: TrendPoint[]; currency: string; height?: number }>();

const canvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;
let observer: MutationObserver | null = null;

const css = (name: string) => `hsl(${getComputedStyle(document.documentElement).getPropertyValue(name).trim()})`;

const summary = computed(() => props.points.map((p) => `${p.label}: income ${formatMoney(p.income, props.currency)}, expenses ${formatMoney(p.expense, props.currency)}`).join('. '));

function build() {
    if (!canvas.value) return;
    chart?.destroy();

    const text = css('--muted-foreground');
    const grid = css('--border');

    chart = new Chart(canvas.value, {
        type: 'bar',
        data: {
            labels: props.points.map((p) => p.label),
            datasets: [
                { label: 'Income', data: props.points.map((p) => p.income / 100), backgroundColor: css('--chart-1'), borderRadius: 6, maxBarThickness: 28 },
                { label: 'Expenses', data: props.points.map((p) => p.expense / 100), backgroundColor: css('--chart-2'), borderRadius: 6, maxBarThickness: 28 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { color: text, usePointStyle: true, boxWidth: 8 } },
                tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${formatMoney(Math.round((c.parsed.y ?? 0) * 100), props.currency)}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: text } },
                y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { color: text, maxTicksLimit: 5, callback: (v) => formatMoney(Math.round(Number(v) * 100), props.currency).replace(/\.00$/, '') } },
            },
        },
    });
}

onMounted(() => {
    build();
    // Re-colour when the user switches between light and dark.
    observer = new MutationObserver(build);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onBeforeUnmount(() => {
    observer?.disconnect();
    chart?.destroy();
});
watch(() => [props.points, props.currency], build, { deep: true });
</script>

<template>
    <div :style="{ height: `${height ?? 280}px` }" class="relative w-full">
        <canvas ref="canvas" role="img" :aria-label="`Monthly income versus expenses. ${summary}`" />
    </div>
</template>
