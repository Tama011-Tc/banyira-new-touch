<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { useCopy } from '@/lib/site-i18n';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const c = useCopy({
    en: {
        head: 'Register', title: 'Create an account', description: 'Choose how you will use the platform', type: 'Account type',
        investor: 'Investor', investorP: 'Lend to vetted businesses', owner: 'Business owner', ownerP: 'Apply for financing',
        name: 'Name', namePh: 'Full name', email: 'Email address', password: 'Password', confirm: 'Confirm password',
        submit: 'Create account', have: 'Already have an account?', signIn: 'Sign in',
    },
    ny: {
        head: 'Lembetsani', title: 'Tsegulani akaunti', description: 'Sankhani momwe mudzagwiritsire ntchito nsanjayi', type: 'Mtundu wa akaunti',
        investor: 'Wobwereketsa', investorP: 'Bwereketsani mabizinesi omwe aunikidwa', owner: 'Mwini bizinesi', ownerP: 'Funsirani ngongole',
        name: 'Dzina', namePh: 'Dzina lonse', email: 'Imelo', password: 'Mawu achinsinsi', confirm: 'Tsimikizani mawu achinsinsi',
        submit: 'Tsegulani akaunti', have: 'Muli kale ndi akaunti?', signIn: 'Lowani',
    },
});

const form = useForm({
    name: '',
    // "Apply Now" links here with ?account_type=business_owner.
    account_type: new URLSearchParams(window.location.search).get('account_type') === 'business_owner' ? 'business_owner' : 'investor',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase :title="c.title" :description="c.description">
        <Head :title="c.head" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <fieldset class="grid grid-cols-2 gap-2" :aria-label="c.type">
                    <label class="cursor-pointer rounded-lg border p-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent">
                        <input v-model="form.account_type" type="radio" value="investor" class="sr-only" /><span class="font-medium">{{ c.investor }}</span><span class="mt-0.5 block text-xs text-muted-foreground">{{ c.investorP }}</span>
                    </label>
                    <label class="cursor-pointer rounded-lg border p-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent">
                        <input v-model="form.account_type" type="radio" value="business_owner" class="sr-only" /><span class="font-medium">{{ c.owner }}</span><span class="mt-0.5 block text-xs text-muted-foreground">{{ c.ownerP }}</span>
                    </label>
                    <InputError class="col-span-2" :message="form.errors.account_type" />
                </fieldset>
                <div class="grid gap-2">
                    <Label for="name">{{ c.name }}</Label>
                    <Input id="name" type="text" required autofocus tabindex="1" autocomplete="name" v-model="form.name" :placeholder="c.namePh" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ c.email }}</Label>
                    <Input id="email" type="email" required tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ c.password }}</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        :placeholder="c.password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{ c.confirm }}</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        :placeholder="c.confirm"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ c.submit }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ c.have }}
                <TextLink :href="route('login')" class="underline underline-offset-4" tabindex="6">{{ c.signIn }}</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
