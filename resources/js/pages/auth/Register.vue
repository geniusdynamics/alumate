<script setup lang="ts">
import AuthForm from '@/components/auth/AuthForm.vue';
import FormField from '@/components/forms/FormField.vue';
import TextLink from '@/components/common/TextLink.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
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
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <AuthForm
            submit-text="Create account"
            :is-loading="form.processing"
            :disabled="form.processing"
            @submit="submit"
        >
            <template #fields>
                <FormField
                    id="name"
                    label="Name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    placeholder="Full name"
                    v-model="form.name"
                    :error="form.errors.name"
                />

                <FormField
                    id="email"
                    label="Email address"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    placeholder="email@example.com"
                    v-model="form.email"
                    :error="form.errors.email"
                />

                <FormField
                    id="password"
                    label="Password"
                    type="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    placeholder="Password"
                    v-model="form.password"
                    :error="form.errors.password"
                />

                <FormField
                    id="password_confirmation"
                    label="Confirm password"
                    type="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    v-model="form.password_confirmation"
                    :error="form.errors.password_confirmation"
                />
            </template>
            
            <template #footer>
                <div class="text-center text-sm text-muted-foreground">
                    Already have an account?
                    <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
                </div>
            </template>
        </AuthForm>
    </AuthBase>
</template>
