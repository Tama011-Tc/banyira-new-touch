<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'

const props = defineProps<{ status: number }>()
const copy: Record<number, [string, string]> = {
  403: ['You do not have access to this page', 'If you think you should, contact your administrator or Banyira support.'],
  404: ['Page not found', 'The page may have moved, or the link may be incomplete.'],
  500: ['Something went wrong on our side', 'Your data is safe. Please try again in a moment.'],
  503: ['Banyira is briefly unavailable', 'We are carrying out maintenance. Please try again shortly.'],
}
const [title, body] = copy[props.status] ?? copy[500]
</script>

<template>
  <Head :title="title" />
  <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6">
    <Link href="/"><Logo /></Link>
    <h1 class="mt-10 text-[26px]">{{ title }}</h1>
    <p class="mt-2 text-muted">{{ body }}</p>
    <div class="mt-6 flex gap-4 text-sm"><Link href="/" class="link">Go to the home page</Link><a href="javascript:history.back()" class="link text-muted">Go back</a></div>
  </div>
</template>
