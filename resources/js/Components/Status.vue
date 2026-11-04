<script setup lang="ts">
import { computed } from 'vue'
import { label } from '@/lib/format'

const props = defineProps<{ s: string }>()

const tone = computed(() => {
  const v = props.s.toLowerCase().replace(/_/g, ' ')
  if (/(reject|fail|overdue|arrears|default|locked|declined|error|blocked|withdrawn|degraded|unmatched|reversed|lost|suspended)/.test(v)) return 'bg-danger'
  if (/(pending|review|draft|progress|awaiting|scheduled|partial|due|queued|proposed|submitted|required|open request|new|contacted|qualified|onboarding)/.test(v)) return 'bg-maize'
  if (/(active|approved|accepted|complete|paid|verified|healthy|open|listed|funded|running|posted|signed|confirmed|disbursed|answered|converted|sent)/.test(v)) return 'bg-ok'
  return 'bg-[#9a9e9f]'
})
</script>

<template>
  <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-[13px]">
    <span class="h-1.5 w-1.5 rounded-full" :class="tone" aria-hidden="true" />{{ label(s) }}
  </span>
</template>
