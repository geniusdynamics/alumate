<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    employer: Object,
});

const form = useForm({
    company_name: props.employer.company_name || '',
    company_address: props.employer.company_address || '',
    company_phone: props.employer.company_phone || '',
    company_registration_number: props.employer.company_registration_number || '',
    company_tax_number: props.employer.company_tax_number || '',
    company_website: props.employer.company_website || '',
    industry: props.employer.industry || '',
    company_size: props.employer.company_size || '',
    company_description: props.employer.company_description || '',
    contact_person_name: props.employer.contact_person_name || '',
    contact_person_title: props.employer.contact_person_title || '',
    contact_person_email: props.employer.contact_person_email || '',
    contact_person_phone: props.employer.contact_person_phone || '',
    established_year: props.employer.established_year || '',
    employee_count: props.employer.employee_count || '',
    business_locations: props.employer.business_locations || [],
    services_products: props.employer.services_products || [],
    employer_benefits: props.employer.employer_benefits || [],
    notification_preferences: props.employer.notification_preferences || {},
});

const newLocation = ref('');
const newService = ref('');
const newBenefit = ref('');

const addLocation = () => {
    if (newLocation.value.trim()) {
        form.business_locations.push(newLocation.value.trim());
        newLocation.value = '';
    }
};

const removeLocation = (index) => {
    form.business_locations.splice(index, 1);
};

const addService = () => {
    if (newService.value.trim()) {
        form.services_products.push(newService.value.trim());
        newService.value = '';
    }
};

const removeService = (index) => {
    form.services_products.splice(index, 1);
};

const addBenefit = () => {
    if (newBenefit.value.trim()) {
        form.employer_benefits.push(newBenefit.value.trim());
        newBenefit.value = '';
    }
};

const removeBenefit = (index) => {
    form.employer_benefits.splice(index, 1);
};

const submit = () => {
    form.put(route('employers.update', props.employer.id));
};

const getVerificationStatusClass = (status) => {
    const classes = {
        'verified': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'under_review': 'bg-blue-100 text-blue-800',
        'rejected': 'bg-red-100 text-red-800',
        'suspended': 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'N/A';
};
</script>

<template>
    <Head title="Company Profile" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Company Profile
                </h2>
                <div class="flex items-center gap-2">
                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getVerificationStatusClass(employer.verification_status)]">
                        {{ employer.verification_status?.replace('_', ' ').toUpperCase() }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Verification Status Alert -->
                <div v-if="employer.verification_status !== 'verified'" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Account Verification Required
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p v-if="employer.verification_status === 'pending'">
                                    Your account is pending verification. Complete your profile and submit verification documents to start posting jobs.
                                </p>
                                <p v-else-if="employer.verification_status === 'rejected'">
                                    Your verification was rejected. Please review the feedback and resubmit your documents.
                                </p>
                                <p v-else-if="employer.verification_status === 'under_review'">
                                    Your verification documents are under review. We'll notify you once the review is complete.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- Company Information -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Company Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name *</label>
                                    <input id="company_name" type="text" v-model="form.company_name" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">{{ form.errors.company_name }}</div>
                                </div>
                                
                                <div>
                                    <label for="industry" class="block text-sm font-medium text-gray-700">Industry</label>
                                    <input id="industry" type="text" v-model="form.industry"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.industry" class="mt-1 text-sm text-red-600">{{ form.errors.industry }}</div>
                                </div>
                                
                                <div>
                                    <label for="company_size" class="block text-sm font-medium text-gray-700">Company Size</label>
                                    <select id="company_size" v-model="form.company_size"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select size</option>
                                        <option value="startup">Startup (1-10 employees)</option>
                                        <option value="small">Small (11-50 employees)</option>
                                        <option value="medium">Medium (51-200 employees)</option>
                                        <option value="large">Large (201-1000 employees)</option>
                                        <option value="enterprise">Enterprise (1000+ employees)</option>
                                    </select>
                                    <div v-if="form.errors.company_size" class="mt-1 text-sm text-red-600">{{ form.errors.company_size }}</div>
                                </div>
                                
                                <div>
                                    <label for="established_year" class="block text-sm font-medium text-gray-700">Established Year</label>
                                    <input id="established_year" type="number" v-model="form.established_year" 
                                           :min="1800" :max="new Date().getFullYear()"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.established_year" class="mt-1 text-sm text-red-600">{{ form.errors.established_year }}</div>
                                </div>
                                
                                <div>
                                    <label for="employee_count" class="block text-sm font-medium text-gray-700">Employee Count</label>
                                    <input id="employee_count" type="number" v-model="form.employee_count" min="1"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.employee_count" class="mt-1 text-sm text-red-600">{{ form.errors.employee_count }}</div>
                                </div>
                                
                                <div>
                                    <label for="company_website" class="block text-sm font-medium text-gray-700">Website</label>
                                    <input id="company_website" type="url" v-model="form.company_website"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.company_website" class="mt-1 text-sm text-red-600">{{ form.errors.company_website }}</div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label for="company_description" class="block text-sm font-medium text-gray-700">Company Description</label>
                                <textarea id="company_description" v-model="form.company_description" rows="4"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                          placeholder="Describe your company, its mission, and what makes it unique..."></textarea>
                                <div v-if="form.errors.company_description" class="mt-1 text-sm text-red-600">{{ form.errors.company_description }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Contact Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="company_address" class="block text-sm font-medium text-gray-700">Company Address</label>
                                    <textarea id="company_address" v-model="form.company_address" rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                    <div v-if="form.errors.company_address" class="mt-1 text-sm text-red-600">{{ form.errors.company_address }}</div>
                                </div>
                                
                                <div>
                                    <label for="company_phone" class="block text-sm font-medium text-gray-700">Company Phone</label>
                                    <input id="company_phone" type="tel" v-model="form.company_phone"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.company_phone" class="mt-1 text-sm text-red-600">{{ form.errors.company_phone }}</div>
                                </div>
                                
                                <div>
                                    <label for="contact_person_name" class="block text-sm font-medium text-gray-700">Contact Person Name *</label>
                                    <input id="contact_person_name" type="text" v-model="form.contact_person_name" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.contact_person_name" class="mt-1 text-sm text-red-600">{{ form.errors.contact_person_name }}</div>
                                </div>
                                
                                <div>
                                    <label for="contact_person_title" class="block text-sm font-medium text-gray-700">Contact Person Title</label>
                                    <input id="contact_person_title" type="text" v-model="form.contact_person_title"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.contact_person_title" class="mt-1 text-sm text-red-600">{{ form.errors.contact_person_title }}</div>
                                </div>
                                
                                <div>
                                    <label for="contact_person_email" class="block text-sm font-medium text-gray-700">Contact Person Email</label>
                                    <input id="contact_person_email" type="email" v-model="form.contact_person_email"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.contact_person_email" class="mt-1 text-sm text-red-600">{{ form.errors.contact_person_email }}</div>
                                </div>
                                
                                <div>
                                    <label for="contact_person_phone" class="block text-sm font-medium text-gray-700">Contact Person Phone</label>
                                    <input id="contact_person_phone" type="tel" v-model="form.contact_person_phone"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.contact_person_phone" class="mt-1 text-sm text-red-600">{{ form.errors.contact_person_phone }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legal Information -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Legal Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="company_registration_number" class="block text-sm font-medium text-gray-700">Registration Number</label>
                                    <input id="company_registration_number" type="text" v-model="form.company_registration_number"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.company_registration_number" class="mt-1 text-sm text-red-600">{{ form.errors.company_registration_number }}</div>
                                </div>
                                
                                <div>
                                    <label for="company_tax_number" class="block text-sm font-medium text-gray-700">Tax Number</label>
                                    <input id="company_tax_number" type="text" v-model="form.company_tax_number"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <div v-if="form.errors.company_tax_number" class="mt-1 text-sm text-red-600">{{ form.errors.company_tax_number }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Locations -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Business Locations</h3>
                            
                            <div class="space-y-2 mb-4">
                                <div v-for="(location, index) in form.business_locations" :key="index" 
                                     class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span>{{ location }}</span>
                                    <button type="button" @click="removeLocation(index)" 
                                            class="text-red-600 hover:text-red-800">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <input v-model="newLocation" type="text" placeholder="Add business location..."
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <button type="button" @click="addLocation" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Services/Products -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Services & Products</h3>
                            
                            <div class="space-y-2 mb-4">
                                <div v-for="(service, index) in form.services_products" :key="index" 
                                     class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span>{{ service }}</span>
                                    <button type="button" @click="removeService(index)" 
                                            class="text-red-600 hover:text-red-800">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <input v-model="newService" type="text" placeholder="Add service or product..."
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <button type="button" @click="addService" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Benefits -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Employee Benefits</h3>
                            
                            <div class="space-y-2 mb-4">
                                <div v-for="(benefit, index) in form.employer_benefits" :key="index" 
                                     class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span>{{ benefit }}</span>
                                    <button type="button" @click="removeBenefit(index)" 
                                            class="text-red-600 hover:text-red-800">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <input v-model="newBenefit" type="text" placeholder="Add employee benefit..."
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <button type="button" @click="addBenefit" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing"
                                class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded-md">
                            {{ form.processing ? 'Saving...' : 'Save Profile' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>