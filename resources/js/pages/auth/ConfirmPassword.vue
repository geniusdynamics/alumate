<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/" class="text-2xl font-bold text-gray-900">
                {{ $page.props.app?.name || 'Alumate' }}
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
            <Head title="Confirm Password" />

            <div class="mb-4 text-sm text-gray-600">This is a secure area of the application. Please confirm your password before continuing.</div>

            <form @submit.prevent="submit">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                        autocomplete="current-password"
                        autofocus
                    />
                    <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                        {{ form.errors.password }}
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900"
                    >
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>
