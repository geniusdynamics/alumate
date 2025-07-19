<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    graduate: Object,
});

const form = useForm({
    privacy_settings: props.graduate.privacy_settings || {
        profile_visible: true,
        contact_visible: true,
        employment_visible: true,
    },
    allow_employer_contact: props.graduate.allow_employer_contact ?? true,
    job_search_active: props.graduate.job_search_active ?? true,
});

const submit = () => {
    form.patch(route('graduates.privacy.update', props.graduate.id));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Privacy & Contact Settings</h2>
            <p class="mt-1 text-sm text-gray-600">
                Control how your information is shared with employers and other users.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800 mb-4">Profile Visibility</h3>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.privacy_settings.profile_visible" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-3">
                            <span class="text-sm font-medium text-gray-700">Make profile visible to employers</span>
                            <p class="text-xs text-gray-500">Employers will be able to find and view your profile in search results</p>
                        </span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.privacy_settings.contact_visible" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-3">
                            <span class="text-sm font-medium text-gray-700">Show contact information to employers</span>
                            <p class="text-xs text-gray-500">Your email and phone number will be visible to employers</p>
                        </span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.privacy_settings.employment_visible" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-3">
                            <span class="text-sm font-medium text-gray-700">Show employment status to employers</span>
                            <p class="text-xs text-gray-500">Your current employment status and job details will be visible</p>
                        </span>
                    </label>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800 mb-4">Contact Preferences</h3>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.allow_employer_contact" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-3">
                            <span class="text-sm font-medium text-gray-700">Allow employers to contact me</span>
                            <p class="text-xs text-gray-500">Employers can send you messages about job opportunities</p>
                        </span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.job_search_active" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-3">
                            <span class="text-sm font-medium text-gray-700">Currently looking for job opportunities</span>
                            <p class="text-xs text-gray-500">Show that you're actively seeking employment</p>
                        </span>
                    </label>
                </div>
            </div>

            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Privacy Notice</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Your privacy settings control how your information is shared. You can change these settings at any time. Your institution administrators will always have access to your profile for academic and career support purposes.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" :disabled="form.processing" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                    {{ form.processing ? 'Updating...' : 'Update Privacy Settings' }}
                </button>
            </div>
        </form>
    </section>
</template>