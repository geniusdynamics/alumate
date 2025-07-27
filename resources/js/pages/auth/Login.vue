<template>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: #f3f4f6; padding: 20px;">
        <div style="width: 100%; max-width: 400px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <Head title="Log in" />
            
            <div style="text-align: center; margin-bottom: 30px;">
                <Link href="/" style="font-size: 24px; font-weight: bold; color: #1f2937; text-decoration: none;">
                    {{ $page.props.app?.name || 'Laravel' }}
                </Link>
            </div>

            <div v-if="status" style="margin-bottom: 16px; padding: 12px; background-color: #d1fae5; color: #065f46; border-radius: 4px; font-size: 14px;">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div style="margin-bottom: 20px;">
                    <label for="email" style="display: block; font-weight: 500; font-size: 14px; color: #374151; margin-bottom: 8px;">
                        Email
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 16px; background: white; color: #1f2937 !important;"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Enter your email"
                    />
                    <div v-if="form.errors.email" style="margin-top: 8px; font-size: 14px; color: #dc2626;">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="password" style="display: block; font-weight: 500; font-size: 14px; color: #374151; margin-bottom: 8px;">
                        Password
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 16px; background: white; color: #1f2937 !important;"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    />
                    <div v-if="form.errors.password" style="margin-top: 8px; font-size: 14px; color: #dc2626;">
                        {{ form.errors.password }}
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            style="margin-right: 8px;"
                        />
                        <span style="font-size: 14px; color: #6b7280;">Remember me</span>
                    </label>
                </div>

                <div style="margin-bottom: 20px;">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        style="width: 100%; padding: 12px; background-color: #1f2937; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; transition: background-color 0.15s;"
                        :style="{ opacity: form.processing ? 0.5 : 1 }"
                    >
                        {{ form.processing ? 'Logging in...' : 'Log in' }}
                    </button>
                </div>

                <div style="text-align: center; margin-bottom: 16px;">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        style="font-size: 14px; color: #6b7280; text-decoration: underline;"
                    >
                        Forgot your password?
                    </Link>
                </div>

                <div style="text-align: center;">
                    <Link
                        :href="route('register')"
                        style="font-size: 14px; color: #6b7280; text-decoration: underline;"
                    >
                        Don't have an account? Register
                    </Link>
                </div>
            </form>

            <!-- Debug Info -->
            <div style="margin-top: 30px; padding: 15px; background-color: #f9fafb; border-radius: 6px; font-size: 12px; color: #6b7280;">
                <strong>Debug Info:</strong><br>
                Email: {{ form.email || 'empty' }}<br>
                Password: {{ form.password ? '***' : 'empty' }}<br>
                Processing: {{ form.processing }}<br>
                Errors: {{ Object.keys(form.errors).length > 0 ? JSON.stringify(form.errors) : 'none' }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    console.log('Form submitted:', form.data());
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>