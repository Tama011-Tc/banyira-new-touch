<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageTop from '@/Components/PageTop.vue'
import Section from '@/Components/Section.vue'
</script>

<template>
  <PublicLayout>
    <PageTop title="Making business finance in Malawi clearer and fairer." sub="Banyira is a marketplace operator. We verify and assess businesses, list them for investors, and manage agreements, repayments and records. The capital comes from investors, not from Banyira." />
    <Section title="What we hold ourselves to.">
      <div class="grid gap-8 md:grid-cols-3">
        <div><h3 class="text-[15px]">Transparency</h3><p class="mt-2 text-muted">Fees, limits, risk classes and offers are shown in plain language before anyone commits.</p></div>
        <div><h3 class="text-[15px]">Careful assessment</h3><p class="mt-2 text-muted">A person reviews every business against published criteria, and a second person approves each decision.</p></div>
        <div><h3 class="text-[15px]">Separation of money</h3><p class="mt-2 text-muted">Investor capital and repayments are held and reported separately from Banyira's own revenue.</p></div>
      </div>
    </Section>
    <Section title="Five teams, each with a clear responsibility.">
      <ul class="max-w-2xl divide-y divide-line border-y border-line text-[15px]">
        <li class="py-3"><span class="font-medium">Credit assessment</span><span class="block text-sm text-muted">Verifies businesses, scores applications and recommends decisions.</span></li>
        <li class="py-3"><span class="font-medium">Finance</span><span class="block text-sm text-muted">Reconciles payments, releases funds with a second approver, and keeps the ledger.</span></li>
        <li class="py-3"><span class="font-medium">Sales and marketing</span><span class="block text-sm text-muted">Helps businesses and investors join and understand the platform.</span></li>
        <li class="py-3"><span class="font-medium">Technology</span><span class="block text-sm text-muted">Runs and secures the platform. Has no power to approve credit or payments.</span></li>
        <li class="py-3"><span class="font-medium">Executive management</span><span class="block text-sm text-muted">Oversight, final approvals and rule changes.</span></li>
      </ul>
    </Section>
  </PublicLayout>
</template>
