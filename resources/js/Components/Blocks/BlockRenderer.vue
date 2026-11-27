<script setup lang="ts">
import type { Component } from 'vue'
import type { Block } from '@/lib/blocks'
import CallToActionBlock from './CallToActionBlock.vue'
import ColumnsBlock from './ColumnsBlock.vue'
import ContactFormBlock from './ContactFormBlock.vue'
import DocumentsBlock from './DocumentsBlock.vue'
import FaqBlock from './FaqBlock.vue'
import FeaturesBlock from './FeaturesBlock.vue'
import FeesBlock from './FeesBlock.vue'
import FundingExampleBlock from './FundingExampleBlock.vue'
import HeroBlock from './HeroBlock.vue'
import ImageBlock from './ImageBlock.vue'
import InvestmentLimitsBlock from './InvestmentLimitsBlock.vue'
import JourneyBlock from './JourneyBlock.vue'
import ListBlock from './ListBlock.vue'
import NoticeBlock from './NoticeBlock.vue'
import PageHeaderBlock from './PageHeaderBlock.vue'
import ProductsBlock from './ProductsBlock.vue'
import ReviewBlock from './ReviewBlock.vue'
import StepsBlock from './StepsBlock.vue'
import TextBlock from './TextBlock.vue'
import WarningBlock from './WarningBlock.vue'

// One component per type in app/Services/Content/BlockCatalogue.php.
const BLOCKS: Record<string, Component> = {
  hero: HeroBlock, page_header: PageHeaderBlock, text: TextBlock, columns: ColumnsBlock, steps: StepsBlock, list: ListBlock,
  features: FeaturesBlock, review: ReviewBlock, warning: WarningBlock, notice: NoticeBlock, journey: JourneyBlock, faq: FaqBlock,
  documents: DocumentsBlock, products: ProductsBlock, fees: FeesBlock, investment_limits: InvestmentLimitsBlock,
  funding_example: FundingExampleBlock, image: ImageBlock, contact_form: ContactFormBlock, call_to_action: CallToActionBlock,
}

defineProps<{ blocks: Block[] }>()
</script>

<template>
  <template v-for="b in blocks" :key="b.id">
    <component :is="BLOCKS[b.type]" v-if="BLOCKS[b.type]" :data="b.data" />
  </template>
</template>
