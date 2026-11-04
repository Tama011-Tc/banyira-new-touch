<script setup lang="ts">
import BusinessLayout from './BusinessLayout.vue'
import InvestorLayout from './InvestorLayout.vue'

defineProps<{ area: 'business' | 'investor' }>()
</script>

<template>
  <BusinessLayout v-if="area === 'business'"><slot /></BusinessLayout>
  <InvestorLayout v-else><slot /></InvestorLayout>
</template>
