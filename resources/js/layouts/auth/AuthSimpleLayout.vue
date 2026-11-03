<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <!-- Sign in, register and password pages sit inside the public site so the nav stays the same. -->
    <SiteLayout>
        <div class="bg-[#e6efee] px-4 py-12 sm:py-20">
            <div class="mx-auto w-full max-w-md rounded-md border-t-[3px] border-[#14212b] bg-white p-6 shadow-[0_20px_50px_-30px_rgba(20,33,43,0.4)] sm:p-10">
                <div class="mb-8 space-y-2">
                    <h1 class="site-heading text-balance text-3xl">{{ title }}</h1>
                    <p class="text-[15px] text-[#14212b]/75">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </SiteLayout>
</template>
