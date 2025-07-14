<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/components/AuthenticationCardLogo.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    company_name: '',
    company_address: '',
    company_phone: '',
});

const submit = () => {
    form.post(route('employer.register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Employer Registration" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4">
                <InputLabel for="company_name" value="Company Name" />
                <TextInput
                    id="company_name"
                    v-model="form.company_name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.company_name" />
            </div>

            <div class="mt-4">
                <InputLabel for="company_address" value="Company Address" />
                <TextInput
                    id="company_address"
                    v-model="form.company_address"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError class="mt-2" :message="form.errors.company_address" />
            </div>

            <div class="mt-4">
                <InputLabel for="company_phone" value="Company Phone" />
                <TextInput
                    id="company_phone"
                    v-model="form.company_phone"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError class="mt-2" :message="form.errors.company_phone" />
            </div>


            <div class="flex items-center justify-end mt-4">
                <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
