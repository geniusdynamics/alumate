<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    graduates: Array,
});

const form = useForm({
    primary_graduate_id: '',
    duplicate_graduate_id: '',
});

const submit = () => {
    form.post(route('merge.store'));
};
</script>

<template>
    <Head title="Merge Graduates" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Merge Graduates
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div>
                                <label for="primary_graduate_id">Primary Graduate</label>
                                <select id="primary_graduate_id" v-model="form.primary_graduate_id" required>
                                    <option v-for="graduate in graduates" :key="graduate.id" :value="graduate.id">
                                        {{ graduate.name }} ({{ graduate.tenant.name }})
                                    </option>
                                </select>
                            </div>
                            <div class="mt-4">
                                <label for="duplicate_graduate_id">Duplicate Graduate</label>
                                <select id="duplicate_graduate_id" v-model="form.duplicate_graduate_id" required>
                                    <option v-for="graduate in graduates" :key="graduate.id" :value="graduate.id">
                                        {{ graduate.name }} ({{ graduate.tenant.name }})
                                    </option>
                                </select>
                            </div>
                            <div class="flex items-center justify-end mt-4">
                                <button type="submit">Merge</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
