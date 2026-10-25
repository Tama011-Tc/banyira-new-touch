<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{ forced: boolean }>();
const form = useForm({ current_password: '', password: '', password_confirmation: '' });
</script>

<template>
    <AuthBase :title="forced ? 'Choose your own password' : 'Change password'" :description="forced ? 'Your account was set up with a temporary password. Replace it before continuing.' : 'Use at least 12 characters with upper and lower case letters and a number.'">
        <Head title="Change password" />
        <form class="flex flex-col gap-5" @submit.prevent="form.put(route('password.change.update'), { onFinish: () => form.reset() })">
            <div class="grid gap-2"><Label for="cur">{{ forced ? 'Temporary password' : 'Current password' }}</Label><Input id="cur" v-model="form.current_password" type="password" autocomplete="current-password" required /><InputError :message="form.errors.current_password" /></div>
            <div class="grid gap-2"><Label for="new">New password</Label><Input id="new" v-model="form.password" type="password" autocomplete="new-password" required /><InputError :message="form.errors.password" /></div>
            <div class="grid gap-2"><Label for="conf">Confirm new password</Label><Input id="conf" v-model="form.password_confirmation" type="password" autocomplete="new-password" required /></div>
            <Button type="submit" :disabled="form.processing">Save password</Button>
        </form>
    </AuthBase>
</template>
