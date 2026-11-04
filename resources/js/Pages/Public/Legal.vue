<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageTop from '@/Components/PageTop.vue'
import Notice from '@/Components/Notice.vue'
</script>

<template>
  <PublicLayout>
    <PageTop title="Terms, privacy, risk and complaints." />
    <div class="mx-auto max-w-3xl space-y-12 px-4 pb-20 sm:px-6">
      <Notice tone="warn" title="Draft for legal review">These summaries are placeholders. Final terms, the privacy notice and the regulatory position of the marketplace must be reviewed by Malawian legal counsel before Banyira handles real money.</Notice>
      <section id="terms"><h2 class="text-[19px]">Terms of use</h2><p class="mt-2 text-muted">Accounts are for verified businesses and investors only. You must give accurate information, keep your password private and use the platform only for its intended purpose.</p></section>
      <section id="privacy"><h2 class="text-[19px]">Privacy</h2><p class="mt-2 text-muted">We collect what we need to verify you, assess applications and manage financing. Documents are stored privately and only staff who need them can see them. Investors never see a business's documents or contact details.</p></section>
      <section id="risk"><h2 class="text-[19px]">Investment risk</h2><p class="mt-2 text-muted">Investing through Banyira involves financial risk. Investors may experience delayed repayments or lose some or all of their invested capital. Banyira's credit assessments and risk classifications do not guarantee repayment or returns. Investments are not guaranteed, insured or risk-free.</p></section>
      <section id="complaints"><h2 class="text-[19px]">Complaints</h2><p class="mt-2 text-muted">Raise a complaint through Help and Support in your dashboard or the contact form. We acknowledge it within two working days and aim to resolve it within fifteen.</p></section>
    </div>
  </PublicLayout>
</template>
