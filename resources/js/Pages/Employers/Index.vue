<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    employers: Object,
    verificationStatuses: Array,
    industries: Array,
    companySizes: Array,
    subscriptionPlans: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const verificationStatus = ref(props.filters.verification_status || '');
const industry = ref(props.filters.industry || '');
const companySize = ref(props.filters.company_size || '');
const subscriptionPlan = ref(props.filters.subscription_plan || '');
const isActive = ref(props.filters.is_active || '');
const canPostJobs = ref(props.filters.can_post_jobs || '');
const sortBy = ref(props.filters.sort_by || 'created_at');
const sortOrder = ref(props.filters.sort_order || 'desc');

const showAdvancedFilters = ref(false);

const applyFilters = () => {
    router.get(route('employers.index'), {
        search: search.value,
        verification_status: verificationStatus.value,
        industry: industry.value,
        company_size: companySize.value,
        subscription_plan: subscriptionPlan.value,
        is_active: isActive.value,
        can_post_jobs: canPostJobs.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    verificationStatus.value = '';
    industry.value = '';
    companySize.value = '';
    subscriptionPlan.value = '';
    isActive.value = '';
    canPostJobs.value = '';
    sortBy.value = 'created_at';
    sortOrder.value = 'desc';
    applyFilters();
};

const toggleSort = (field) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
    applyFilters();
};

const exportEmployers = () => {
    const params = new URLSearchParams();
    
    if (search.value) params.append('search', search.value);
    if (verificationStatus.value) params.append('verification_status', verificationStatus.value);
    if (industry.value) params.append('industry', industry.value);
    if (companySize.value) params.append('company_size', companySize.value);
    if (subscriptionPlan.value) params.append('subscription_plan', subscriptionPlan.value);
    if (isActive.value) params.append('is_active', isActive.value);
    if (canPostJobs.value) params.append('can_post_jobs', canPostJobs.value);
    
    params.append('sort_by', sortBy.value);
    params.append('sort_order', sortOrder.value);
    params.append('format', 'csv');
    
    window.open(route('employers.export') + '?' + params.toString());
};

const getVerificationStatusBadge = (status) => {
    const badges = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'under_review': 'bg-blue-100 text-blue-800',
        'verified': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'suspended': 'bg-gray-100 text-gray-800',
        'requires_resubmission': 'bg-orange-100 text-orange-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const getCompanySizeBadge = (size) => {
    const badges = {
        'startup': 'bg-purple-100 text-purple-800',
        'small': 'bg-blue-100 text-blue-800',
        'medium': 'bg-green-100 text-green-800',
        'large': 'bg-yellow-100 text-yellow-800',
        'enterprise': 'bg-red-100 text-red-800',
    };
    return badges[size] || 'bg-gray-100 text-gray-800';
};

const getSubscriptionBadge = (plan) => {
    const badges = {
        'free': 'bg-gray-100 text-gray-800',
        'basic': 'bg-blue-100 text-blue-800',
        'premium': 'bg-purple-100 text-purple-800',
        'enterprise': 'bg-indigo-100 text-indigo-800',
    };
    return badges[plan] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString();
};

const verifyEmployer = (employer) => {
    router.post(route('employers.verify', employer.id), {}, {
        preserveState: true,
        onSuccess: () => {
            router.reload({ only: ['employers'] });
        }
    });
};

const rejectEmployer = (employer) => {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        router.post(route('employers.reject', employer.id), {
            rejection_reason: reason
        }, {
            preserveState: true,
            onSuccess: () => {
                router.reload({ only: ['employers'] });
            }
        });
    }
};

const suspendEmployer = (employer) => {
    const reason = prompt('Please provide a reason for suspension:');
    if (reason) {
        router.post(route('employers.suspend', employer.id), {
            suspension_reason: reason
        }, {
            preserveState: true,
            onSuccess: () => {
                router.reload({ only: ['employers'] });
            }
        });
    }
};

const reactivateEmployer = (employer) => {
    if (confirm('Are you sure you want to reactivate this employer?')) {
        router.post(route('employers.reactivate', employer.id), {}, {
            preserveState: true,
            onSuccess: () => {
                router.reload({ only: ['employers'] });
            }
        });
    }
};
</script>

<template>
    <Head title="Employer Management" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Employer Management
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Action Buttons -->
                <div class="flex justify-between mb-6">
                    <div class="flex gap-2">
                        <button @click="exportEmployers" 
                                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md">
                            Export Employers
                        </button>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Search & Filter Employers</h3>
                        <button @click="showAdvancedFilters = !showAdvancedFilters" 
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            {{ showAdvancedFilters ? 'Hide Advanced' : 'Show Advanced' }}
                        </button>
                    </div>
                    
                    <!-- Basic Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input id="search" type="text" v-model="search" placeholder="Company name, email, industry..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="verification_status" class="block text-sm font-medium text-gray-700 mb-1">Verification Status</label>
                            <select id="verification_status" v-model="verificationStatus"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option v-for="status in verificationStatuses" :key="status" :value="status">
                                    {{ status.replace('_', ' ').toUpperCase() }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="industry" class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                            <select id="industry" v-model="industry"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Industries</option>
                                <option v-for="ind in industries" :key="ind" :value="ind">
                                    {{ ind }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="company_size" class="block text-sm font-medium text-gray-700 mb-1">Company Size</label>
                            <select id="company_size" v-model="companySize"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sizes</option>
                                <option v-for="size in companySizes" :key="size" :value="size">
                                    {{ size.toUpperCase() }}
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Advanced Filters -->
                    <div v-if="showAdvancedFilters" class="border-t pt-4 mt-4">
                        <h4 class="text-md font-medium text-gray-800 mb-3">Advanced Filters</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label for="subscription_plan" class="block text-sm font-medium text-gray-700 mb-1">Subscription Plan</label>
                                <select id="subscription_plan" v-model="subscriptionPlan"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Plans</option>
                                    <option v-for="plan in subscriptionPlans" :key="plan" :value="plan">
                                        {{ plan.toUpperCase() }}
                                    </option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
                                <select id="is_active" v-model="isActive"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All</option>
                                    <option value="true">Active</option>
                                    <option value="false">Inactive</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="can_post_jobs" class="block text-sm font-medium text-gray-700 mb-1">Job Posting Permission</label>
                                <select id="can_post_jobs" v-model="canPostJobs"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All</option>
                                    <option value="true">Can Post Jobs</option>
                                    <option value="false">Cannot Post Jobs</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="applyFilters" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                            Apply Filters
                        </button>
                        <button @click="clearFilters" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Employers List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('company_name')" class="flex items-center hover:text-gray-700">
                                            Company
                                            <svg v-if="sortBy === 'company_name'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('verification_status')" class="flex items-center hover:text-gray-700">
                                            Verification Status
                                            <svg v-if="sortBy === 'verification_status'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Company Details
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button @click="toggleSort('total_jobs_posted')" class="flex items-center hover:text-gray-700">
                                            Job Statistics
                                            <svg v-if="sortBy === 'total_jobs_posted'" class="ml-1 h-4 w-4" :class="sortOrder === 'asc' ? 'transform rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="employer in employers.data" :key="employer.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ employer.company_name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ employer.user?.email }}
                                                </div>
                                                <div v-if="employer.contact_person_name" class="text-xs text-gray-400">
                                                    Contact: {{ employer.contact_person_name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getVerificationStatusBadge(employer.verification_status)]">
                                            {{ employer.verification_status?.replace('_', ' ').toUpperCase() }}
                                        </span>
                                        <div v-if="employer.verification_completed_at" class="text-xs text-gray-500 mt-1">
                                            {{ formatDate(employer.verification_completed_at) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ employer.industry || 'N/A' }}</div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span v-if="employer.company_size" :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getCompanySizeBadge(employer.company_size)]">
                                                {{ employer.company_size?.toUpperCase() }}
                                            </span>
                                            <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getSubscriptionBadge(employer.subscription_plan)]">
                                                {{ employer.subscription_plan?.toUpperCase() }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="space-y-1">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Total Jobs:</span>
                                                <span class="font-medium">{{ employer.total_jobs_posted || 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Active:</span>
                                                <span class="font-medium">{{ employer.active_jobs_count || 0 }}</span>
                                            </div>
                                            <div v-if="employer.employer_rating" class="flex justify-between">
                                                <span class="text-gray-600">Rating:</span>
                                                <span class="font-medium">{{ employer.employer_rating }}/5</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            <Link :href="route('employers.show', employer.id)" 
                                                  class="text-indigo-600 hover:text-indigo-900">
                                                View
                                            </Link>
                                            <Link :href="route('employers.edit', employer.id)" 
                                                  class="text-green-600 hover:text-green-900">
                                                Edit
                                            </Link>
                                            
                                            <!-- Verification Actions -->
                                            <button v-if="employer.verification_status === 'under_review'" 
                                                    @click="verifyEmployer(employer)"
                                                    class="text-green-600 hover:text-green-900">
                                                Verify
                                            </button>
                                            <button v-if="employer.verification_status === 'under_review'" 
                                                    @click="rejectEmployer(employer)"
                                                    class="text-red-600 hover:text-red-900">
                                                Reject
                                            </button>
                                            <button v-if="employer.verification_status === 'verified'" 
                                                    @click="suspendEmployer(employer)"
                                                    class="text-orange-600 hover:text-orange-900">
                                                Suspend
                                            </button>
                                            <button v-if="employer.verification_status === 'suspended'" 
                                                    @click="reactivateEmployer(employer)"
                                                    class="text-blue-600 hover:text-blue-900">
                                                Reactivate
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="employers.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link v-if="employers.prev_page_url" :href="employers.prev_page_url" 
                                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </Link>
                                <Link v-if="employers.next_page_url" :href="employers.next_page_url" 
                                      class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ employers.from }} to {{ employers.to }} of {{ employers.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link v-for="link in employers.links" :key="link.label" 
                                              :href="link.url" 
                                              :class="[
                                                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                  link.active 
                                                      ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                                                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                              ]"
                                              v-html="link.label">
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="employers.data.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No employers found</div>
                        <p class="text-gray-400 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your search filters.' : 'No employers have registered yet.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>