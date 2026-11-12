<script setup lang="ts">
import { computed } from 'vue'
import Badge from './Badge.vue'
import { label } from '@/lib/format'

// Status label: maps any record status onto one of four tones.
const props = defineProps<{ s: string }>()
const tone = computed(() => {
  const v = props.s.toLowerCase().replace(/_/g, ' ')
  if (/(reject|fail|overdue|arrears|default|locked|declined|error|blocked|withdrawn|degraded|unmatched|reversed|lost|suspended|not configured|not verified)/.test(v)) return 'red'
  if (/(pending|review|draft|progress|awaiting|scheduled|partial|due|queued|proposed|submitted|required|open request|new|contacted|qualified|onboarding|not received)/.test(v)) return 'orange'
  if (/(active|approved|accepted|complete|paid|verified|healthy|open|listed|funded|running|posted|signed|confirmed|disbursed|answered|converted|sent|success|published)/.test(v)) return 'green'
  return 'gray'
})
</script>

<template>
  <Badge :tone="tone" dot>{{ label(s) }}</Badge>
</template>
