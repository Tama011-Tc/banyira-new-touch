<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { useCopy } from '@/lib/site-i18n';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const c = useCopy({
    en: { head: 'Sign in', title: 'Sign in to your account', description: 'Enter your email and password to sign in', email: 'Email address', password: 'Password', forgot: 'Forgot password?', remember: 'Remember me', submit: 'Sign in', noAccount: "Don't have an account?", signUp: 'Sign up' },
    ny: { head: 'Lowani', title: 'Lowani mu akaunti yanu', description: 'Lembani imelo ndi mawu achinsinsi kuti mulowe', email: 'Imelo', password: 'Mawu achinsinsi', forgot: 'Mwayiwala mawu achinsinsi?', remember: 'Ndikumbukireni', submit: 'Lowani', noAccount: 'Mulibe akaunti?', signUp: 'Lembetsani' },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase :title="c.title" :description="c.description">
        <Head :title="c.head" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ c.email }}</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ c.password }}</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" tabindex="5">{{ c.forgot }}</TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        :placeholder="c.password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between" tabindex="3">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model:checked="form.remember" tabindex="4" />
                        <span>{{ c.remember }}</span>
                    </Label>
                </div>

                <Button type="submit" class="mt-4 w-full" tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ c.submit }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ c.noAccount }}
                <TextLink :href="route('register')" :tabindex="5">{{ c.signUp }}</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
