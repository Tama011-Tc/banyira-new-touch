<script setup lang="ts">
import BlockButtons from './BlockButtons.vue'
import FundingSplit from '@/Components/FundingSplit.vue'
import RiskBadge from '@/Components/RiskBadge.vue'
import { EXAMPLE_PARTS, EXAMPLE_TOTAL } from '@/lib/example'

defineProps<{ data: Record<string, any> }>()
</script>

<template>
  <section class="mx-auto max-w-6xl px-5 pb-20 pt-16 sm:px-8 md:pb-32 md:pt-28">
    <h1 class="t-display">
      {{ data.headline }}
      <template v-if="data.headline_muted"><br /><span class="text-label-3">{{ data.headline_muted }}</span></template>
    </h1>
    <div class="mt-12 grid items-end gap-12 md:mt-20" :class="data.show_example && 'md:grid-cols-[1.25fr_1fr]'">
      <div>
        <p v-if="data.lead" class="t-lead max-w-lg">{{ data.lead }}</p>
        <BlockButtons :buttons="data.buttons" class="mt-9" />
      </div>

      <figure v-if="data.show_example" class="rounded-lg border border-separator bg-surface p-5">
        <div class="mb-1 flex items-center justify-between">
          <p class="text-sm font-medium">{{ data.example_title }}</p>
          <RiskBadge r="B" />
        </div>
        <FundingSplit :total="EXAMPLE_TOTAL" :parts="EXAMPLE_PARTS" />
        <figcaption v-if="data.example_caption" class="mt-5 border-t border-line pt-3 text-xs text-muted">{{ data.example_caption }}</figcaption>
      </figure>
    </div>
  </section>
</template>
