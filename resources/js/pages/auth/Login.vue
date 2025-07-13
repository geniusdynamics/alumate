<script setup lang="ts">
import AuthForm from '@/components/auth/AuthForm.vue';
import FormField from '@/components/forms/FormField.vue';
import TextLink from '@/components/common/TextLink.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

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
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <AuthForm
            submit-text="Log in"
            :is-loading="form.processing"
            :disabled="form.processing"
            @submit="submit"
        >
            <template #fields>
                <FormField
                    id="email"
                    label="Email address"
                    type="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                    v-model="form.email"
                    :error="form.errors.email"
                />
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-medium">Password</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">
                            Forgot password?
                        </TextLink>
                    </div>
                    <FormField
                        id="password"
                        label=""
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                        v-model="form.password"
                        :error="form.errors.password"
                    />
                </div>

                <div class="flex items-center">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                        <span class="text-sm">Remember me</span>
                    </Label>
                </div>
            </template>
            
            <template #footer>
                <div class="text-center text-sm text-muted-foreground">
                    Don't have an account?
                    <TextLink :href="route('register')" :tabindex="5">Sign up</TextLink>
                    <br>
                    <TextLink :href="route('employer.register')" :tabindex="6">Register as an Employer</TextLink>
                </div>
            </template>
        </AuthForm>
    </AuthBase>
</template>
