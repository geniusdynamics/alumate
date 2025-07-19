<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticationCard from '@/components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/components/AuthenticationCardLogo.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import { ref } from 'vue';

const currentStep = ref(1);
const totalSteps = 3;

const form = useForm({
    // Personal Information
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    
    // Company Information
    company_name: '',
    company_address: '',
    company_phone: '',
    company_registration_number: '',
    company_website: '',
    industry: '',
    company_size: '',
    company_description: '',
    established_year: '',
    employee_count: '',
    
    // Contact Person Information
    contact_person_name: '',
    contact_person_title: '',
    contact_person_email: '',
    contact_person_phone: '',
    
    // Legal Agreements
    terms_accepted: false,
    privacy_policy_accepted: false,
});

const industries = [
    'Technology', 'Healthcare', 'Finance', 'Education', 'Manufacturing',
    'Retail', 'Construction', 'Transportation', 'Hospitality', 'Agriculture',
    'Energy', 'Media', 'Government', 'Non-profit', 'Other'
];

const companySizes = [
    { value: 'startup', label: 'Startup (1-10 employees)' },
    { value: 'small', label: 'Small (11-50 employees)' },
    { value: 'medium', label: 'Medium (51-200 employees)' },
    { value: 'large', label: 'Large (201-1000 employees)' },
    { value: 'enterprise', label: 'Enterprise (1000+ employees)' },
];

const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submit = () => {
    form.post(route('employer.register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const getStepTitle = (step) => {
    const titles = {
        1: 'Account Information',
        2: 'Company Details',
        3: 'Contact & Legal'
    };
    return titles[step];
};
</script>

<template>
    <Head title="Employer Registration" />

    <div class="min-h-screen bg-gray-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-2xl">
            <div class="text-center">
                <AuthenticationCardLogo class="mx-auto" />
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Employer Registration
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Join our platform to find qualified graduates
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                
                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div v-for="step in totalSteps" :key="step" class="flex items-center">
                            <div :class="[
                                'flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium',
                                step <= currentStep ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-500'
                            ]">
                                {{ step }}
                            </div>
                            <div v-if="step < totalSteps" :class="[
                                'flex-1 h-1 mx-4',
                                step < currentStep ? 'bg-indigo-600' : 'bg-gray-300'
                            ]"></div>
                        </div>
                    </div>
                    <div class="mt-2 text-center">
                        <span class="text-sm font-medium text-gray-900">
                            Step {{ currentStep }} of {{ totalSteps }}: {{ getStepTitle(currentStep) }}
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    
                    <!-- Step 1: Account Information -->
                    <div v-if="currentStep === 1" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Full Name *" />
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

                            <div>
                                <InputLabel for="email" value="Email Address *" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    required
                                    autocomplete="email"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="password" value="Password *" />
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

                            <div>
                                <InputLabel for="password_confirmation" value="Confirm Password *" />
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
                        </div>

                        <div>
                            <InputLabel for="company_name" value="Company Name *" />
                            <TextInput
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.company_name" />
                        </div>
                    </div>

                    <!-- Step 2: Company Details -->
                    <div v-if="currentStep === 2" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="company_address" value="Company Address" />
                                <TextInput
                                    id="company_address"
                                    v-model="form.company_address"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.company_address" />
                            </div>

                            <div>
                                <InputLabel for="company_phone" value="Company Phone" />
                                <TextInput
                                    id="company_phone"
                                    v-model="form.company_phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.company_phone" />
                            </div>

                            <div>
                                <InputLabel for="company_registration_number" value="Registration Number" />
                                <TextInput
                                    id="company_registration_number"
                                    v-model="form.company_registration_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.company_registration_number" />
                            </div>

                            <div>
                                <InputLabel for="company_website" value="Company Website" />
                                <TextInput
                                    id="company_website"
                                    v-model="form.company_website"
                                    type="url"
                                    class="mt-1 block w-full"
                                    placeholder="https://example.com"
                                />
                                <InputError class="mt-2" :message="form.errors.company_website" />
                            </div>

                            <div>
                                <InputLabel for="industry" value="Industry" />
                                <select
                                    id="industry"
                                    v-model="form.industry"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select an industry</option>
                                    <option v-for="industry in industries" :key="industry" :value="industry">
                                        {{ industry }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.industry" />
                            </div>

                            <div>
                                <InputLabel for="company_size" value="Company Size" />
                                <select
                                    id="company_size"
                                    v-model="form.company_size"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select company size</option>
                                    <option v-for="size in companySizes" :key="size.value" :value="size.value">
                                        {{ size.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.company_size" />
                            </div>

                            <div>
                                <InputLabel for="established_year" value="Established Year" />
                                <TextInput
                                    id="established_year"
                                    v-model="form.established_year"
                                    type="number"
                                    :min="1800"
                                    :max="new Date().getFullYear()"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.established_year" />
                            </div>

                            <div>
                                <InputLabel for="employee_count" value="Number of Employees" />
                                <TextInput
                                    id="employee_count"
                                    v-model="form.employee_count"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.employee_count" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="company_description" value="Company Description" />
                            <textarea
                                id="company_description"
                                v-model="form.company_description"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Tell us about your company..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.company_description" />
                        </div>
                    </div>

                    <!-- Step 3: Contact & Legal -->
                    <div v-if="currentStep === 3" class="space-y-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Person Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="contact_person_name" value="Contact Person Name *" />
                                    <TextInput
                                        id="contact_person_name"
                                        v-model="form.contact_person_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.contact_person_name" />
                                </div>

                                <div>
                                    <InputLabel for="contact_person_title" value="Job Title" />
                                    <TextInput
                                        id="contact_person_title"
                                        v-model="form.contact_person_title"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.contact_person_title" />
                                </div>

                                <div>
                                    <InputLabel for="contact_person_email" value="Contact Email" />
                                    <TextInput
                                        id="contact_person_email"
                                        v-model="form.contact_person_email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        placeholder="Leave blank to use account email"
                                    />
                                    <InputError class="mt-2" :message="form.errors.contact_person_email" />
                                </div>

                                <div>
                                    <InputLabel for="contact_person_phone" value="Contact Phone" />
                                    <TextInput
                                        id="contact_person_phone"
                                        v-model="form.contact_person_phone"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.contact_person_phone" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Legal Agreements</h3>
                            <div class="space-y-4">
                                <label class="flex items-start">
                                    <input
                                        type="checkbox"
                                        v-model="form.terms_accepted"
                                        class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                    <span class="ml-3 text-sm text-gray-700">
                                        I agree to the <Link href="/terms" class="text-indigo-600 hover:text-indigo-500">Terms of Service</Link> *
                                    </span>
                                </label>
                                <InputError class="mt-2" :message="form.errors.terms_accepted" />

                                <label class="flex items-start">
                                    <input
                                        type="checkbox"
                                        v-model="form.privacy_policy_accepted"
                                        class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                    <span class="ml-3 text-sm text-gray-700">
                                        I agree to the <Link href="/privacy" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</Link> *
                                    </span>
                                </label>
                                <InputError class="mt-2" :message="form.errors.privacy_policy_accepted" />
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Verification Required</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Your account will be pending verification after registration. You'll be able to post jobs once your company is verified by our team.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between pt-6">
                        <div>
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="prevStep"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Previous
                            </button>
                        </div>

                        <div class="flex items-center space-x-4">
                            <Link
                                :href="route('login')"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Already have an account?
                            </Link>

                            <button
                                v-if="currentStep < totalSteps"
                                type="button"
                                @click="nextStep"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Next
                            </button>

                            <PrimaryButton
                                v-if="currentStep === totalSteps"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Register Company
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
