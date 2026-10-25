<script setup lang="ts">
import FormField from '@/components/pw/FormField.vue';
import PageHeader from '@/components/pw/PageHeader.vue';
import SelectField from '@/components/pw/SelectField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ articles: any[]; categories: Record<string, string> }>();
const editing = ref<number | null>(null);
const blank = () => ({ category: 'cash_flow', title_en: '', body_en: '', title_ny: '', body_ny: '', published: false, position: 0 });
const form = useForm(blank());
function edit(a: any) {
    editing.value = a.id;
    Object.assign(form, { category: a.category, title_en: a.title_en, body_en: a.body_en, title_ny: a.title_ny ?? '', body_ny: a.body_ny ?? '', published: a.published, position: a.position });
}
function reset() { editing.value = null; Object.assign(form, blank()); form.clearErrors(); }
const save = () => (editing.value ? form.put(route('learn.update', editing.value), { onSuccess: reset }) : form.post(route('learn.store'), { onSuccess: reset }));
const remove = (a: any) => confirm(`Remove “${a.title_en}”?`) && router.delete(route('learn.destroy', a.id));
</script>

<template>
    <Head title="Learning content" />
    <AppLayout>
        <PageHeader title="Learning content" description="Lessons for business owners and investors. Plain text: blank line = new paragraph, lines starting with “- ” = bullets. Markup is removed." />
        <div class="grid gap-6 lg:grid-cols-5">
            <section class="lg:col-span-2"><ul class="divide-y rounded-lg border bg-card"><li v-for="a in articles" :key="a.id" class="flex items-center justify-between gap-2 p-3 text-sm"><div class="min-w-0"><p class="truncate font-medium">{{ a.title_en }}</p><p class="text-xs text-muted-foreground">{{ categories[a.category] }} · {{ a.published ? 'Published' : 'Draft' }}{{ a.title_ny ? ' · Chichewa ✓' : '' }}</p></div><div class="flex shrink-0 gap-2"><Button size="sm" variant="outline" @click="edit(a)">Edit</Button><Button size="sm" variant="ghost" @click="remove(a)">Remove</Button></div></li></ul></section>
            <section class="lg:col-span-3 rounded-lg border bg-card p-5"><h2 class="font-semibold">{{ editing ? 'Edit lesson' : 'New lesson' }}</h2>
                <form class="mt-3 space-y-4" @submit.prevent="save">
                    <div class="grid gap-4 sm:grid-cols-2"><FormField label="Category" for="ec" :error="form.errors.category"><SelectField id="ec" v-model="form.category"><option v-for="(n, k) in categories" :key="k" :value="k">{{ n }}</option></SelectField></FormField><FormField label="Order" for="eo" :error="form.errors.position"><Input id="eo" v-model.number="form.position" type="number" min="0" /></FormField></div>
                    <FormField label="Title (English)" for="et" :error="form.errors.title_en"><Input id="et" v-model="form.title_en" /></FormField>
                    <FormField label="Lesson (English)" for="eb" :error="form.errors.body_en"><textarea id="eb" v-model="form.body_en" rows="8" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
                    <FormField label="Title (Chichewa)" for="et2" :error="form.errors.title_ny" hint="Optional. Shown to readers who choose Chichewa."><Input id="et2" v-model="form.title_ny" /></FormField>
                    <FormField label="Lesson (Chichewa)" for="eb2" :error="form.errors.body_ny"><textarea id="eb2" v-model="form.body_ny" rows="6" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></FormField>
                    <label class="flex items-center gap-2 text-sm"><input v-model="form.published" type="checkbox" class="size-4" /> Published</label>
                    <div class="flex gap-2"><Button type="submit" :disabled="form.processing">Save</Button><Button v-if="editing" type="button" variant="ghost" @click="reset">Cancel</Button></div>
                </form></section>
        </div>
    </AppLayout>
</template>
