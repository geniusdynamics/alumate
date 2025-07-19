<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    employer: Object,
    statistics: Object,
});

const showVerificationModal = ref(false);
const showRejectionModal = ref(false);
const showSuspensionModal = ref(false);
const verificationNotes = ref('');
const rejectionReason = ref('');
const suspensionReason = ref('');

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

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString();
};

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(amount);
};

const verifyEmployer = () => {
    router.post(route('employers.verify', props.employer.id), {
        verification_notes: verificationNotes.value
    }, {
        onSuccess: () => {
            showVerificationModal.value = false;
            verificationNotes.value = '';
        }
    });
};

const rejectEmployer = () => {
    router.post(route('employers.reject', props.employer.id), {
        rejection_reason: rejectionReason.value
    }, {
        onSuccess: () => {
            showRejectionModal.value = false;
            rejectionReason.value = '';
        }
    });
};

const suspendEmployer = () => {
    router.post(route('employers.suspend', props.employer.id), {
        suspension_reason: suspensionReason.value
    }, {
        onSuccess: () => {
            showSuspensionModal.value = false;
            suspensionReason.value = '';
        }
    });
};

const reactivateEmployer = () => {
    if (confirm('Are you sure you want to reactivate this employer?')) {
        router.post(route('employers.reactivate', props.employer.id));
    }
};

const downloadDocument = (document) => {
    window.open(`/storage/${document.path}`, '_blank');
};
</script>

<template>
    <Head :title="`${employer.company_name} - Employer Profile`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Employer: {{ employer.company_name }}
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('employers.edit', employer.id)" 
                          class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md">
                        Edit Profile
                    </Link>
                    <Link :href="route('employers.index')" 
                          class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Company Overview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ employer.company_name }}</h3>
                                <p class="text-lg text-gray-600">{{ employer.industry || 'Industry not specified' }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getVerificationStatusBadge(employer.verification_status)]">
                                        {{ employer.verification_status?.replace('_', ' ').toUpperCase() }}
                                    </span>
                                    <span v-if="employer.company_size" :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getCompanySizeBadge(employer.company_size)]">
                                        {{ employer.company_size?.toUpperCase() }}
                                    </span>
                                    <span v-if="!employer.is_active" 
                                          class="inline-flex px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                        Inactive
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Member Since</div>
                                <div class="text-lg font-semibold text-gray-900">{{ formatDate(employer.created_at) }}</div>
                                <div v-if="employer.employer_rating" class="text-sm text-gray-600 mt-1">
                                    Rating: {{ employer.employer_rating }}/5 ({{ employer.total_reviews }} reviews)
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="employer.company_description" class="mb-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-2">About the Company</h4>
                            <p class="text-gray-700">{{ employer.company_description }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Company Details -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Company Information -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Company Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Company Address</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.company_address || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Phone</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.company_phone || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Website</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <a v-if="employer.company_website" :href="employer.company_website" target="_blank" 
                                               class="text-indigo-600 hover:text-indigo-500">
                                                {{ employer.company_website }}
                                            </a>
                                            <span v-else>Not provided</span>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Registration Number</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.company_registration_number || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Tax Number</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.company_tax_number || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Established Year</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.established_year || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Employee Count</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.employee_count || 'Not provided' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Person -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Person</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Name</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.contact_person_name || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Title</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.contact_person_title || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Email</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.contact_person_email || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Phone</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ employer.contact_person_phone || 'Not provided' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Documents -->
                        <div v-if="employer.verification_documents && employer.verification_documents.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Verification Documents</h3>
                                <div class="space-y-3">
                                    <div v-for="document in employer.verification_documents" :key="document.name" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ document.name }}</div>
                                            <div class="text-sm text-gray-600">
                                                Uploaded: {{ formatDate(document.uploaded_at) }} • 
                                                Size: {{ (document.size / 1024 / 1024).toFixed(2) }} MB
                                            </div>
                                        </div>
                                        <button @click="downloadDocument(document)" 
                                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            Download
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Applications -->
                        <div v-if="statistics.recent_applications && statistics.recent_applications.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Applications</h3>
                                <div class="space-y-3">
                                    <div v-for="application in statistics.recent_applications" :key="application.id" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ application.graduate?.name }}</div>
                                            <div class="text-sm text-gray-600">{{ application.job?.title }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium text-gray-900">{{ application.status?.toUpperCase() }}</div>
                                            <div class="text-xs text-gray-600">{{ formatDate(application.created_at) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        
                        <!-- Quick Stats -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Statistics</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Profile Completion</span>
                                        <span class="text-sm font-medium text-gray-900">{{ Math.round(statistics.profile_completion) }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Total Jobs Posted</span>
                                        <span class="text-sm font-medium text-gray-900">{{ statistics.job_stats.total_jobs }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Active Jobs</span>
                                        <span class="text-sm font-medium text-gray-900">{{ statistics.job_stats.active_jobs }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Total Hires</span>
                                        <span class="text-sm font-medium text-gray-900">{{ statistics.job_stats.total_hires }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Remaining Job Posts</span>
                                        <span class="text-sm font-medium text-gray-900">{{ statistics.remaining_job_posts }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Actions -->
                        <div v-if="employer.verification_status !== 'verified'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Verification Actions</h3>
                                <div class="space-y-2">
                                    <button v-if="employer.verification_status === 'under_review'" 
                                            @click="showVerificationModal = true"
                                            class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                        Verify Employer
                                    </button>
                                    <button v-if="employer.verification_status === 'under_review'" 
                                            @click="showRejectionModal = true"
                                            class="block w-full text-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                        Reject Application
                                    </button>
                                    <button v-if="employer.verification_status === 'verified'" 
                                            @click="showSuspensionModal = true"
                                            class="block w-full text-center px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                                        Suspend Employer
                                    </button>
                                    <button v-if="employer.verification_status === 'suspended'" 
                                            @click="reactivateEmployer"
                                            class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        Reactivate Employer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Subscription Info -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Subscription</h3>
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-sm text-gray-600">Plan</span>
                                        <p class="text-sm font-medium text-gray-900">{{ employer.subscription_plan?.toUpperCase() }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Job Posting Limit</span>
                                        <p class="text-sm font-medium text-gray-900">{{ employer.job_posting_limit }} per month</p>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Jobs Posted This Month</span>
                                        <p class="text-sm font-medium text-gray-900">{{ employer.jobs_posted_this_month }}</p>
                                    </div>
                                    <div v-if="employer.subscription_expires_at">
                                        <span class="text-sm text-gray-600">Expires</span>
                                        <p class="text-sm font-medium text-gray-900">{{ formatDate(employer.subscription_expires_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Verification History -->
                        <div v-if="employer.verification_completed_at || employer.rejection_reason" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Verification History</h3>
                                <div class="space-y-3">
                                    <div v-if="employer.verification_submitted_at">
                                        <span class="text-sm text-gray-600">Submitted</span>
                                        <p class="text-sm font-medium text-gray-900">{{ formatDate(employer.verification_submitted_at) }}</p>
                                    </div>
                                    <div v-if="employer.verification_completed_at">
                                        <span class="text-sm text-gray-600">Completed</span>
                                        <p class="text-sm font-medium text-gray-900">{{ formatDate(employer.verification_completed_at) }}</p>
                                    </div>
                                    <div v-if="employer.verifier">
                                        <span class="text-sm text-gray-600">Verified By</span>
                                        <p class="text-sm font-medium text-gray-900">{{ employer.verifier.name }}</p>
                                    </div>
                                    <div v-if="employer.verification_notes">
                                        <span class="text-sm text-gray-600">Notes</span>
                                        <p class="text-sm text-gray-700">{{ employer.verification_notes }}</p>
                                    </div>
                                    <div v-if="employer.rejection_reason">
                                        <span class="text-sm text-gray-600">Rejection Reason</span>
                                        <p class="text-sm text-red-700">{{ employer.rejection_reason }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verification Modal -->
        <div v-if="showVerificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showVerificationModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Verify Employer</h3>
                    <div class="mb-4">
                        <label for="verification_notes" class="block text-sm font-medium text-gray-700 mb-2">Verification Notes (Optional)</label>
                        <textarea
                            id="verification_notes"
                            v-model="verificationNotes"
                            rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Add any notes about the verification..."
                        ></textarea>
                    </div>
                    <div class="flex gap-4">
                        <button @click="showVerificationModal = false" 
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400">
                            Cancel
                        </button>
                        <button @click="verifyEmployer" 
                                class="flex-1 px-4 py-2 bg-green-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-700">
                            Verify
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejection Modal -->
        <div v-if="showRejectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showRejectionModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Employer</h3>
                    <div class="mb-4">
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                        <textarea
                            id="rejection_reason"
                            v-model="rejectionReason"
                            rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Please provide a reason for rejection..."
                            required
                        ></textarea>
                    </div>
                    <div class="flex gap-4">
                        <button @click="showRejectionModal = false" 
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400">
                            Cancel
                        </button>
                        <button @click="rejectEmployer" 
                                class="flex-1 px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700">
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suspension Modal -->
        <div v-if="showSuspensionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showSuspensionModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Suspend Employer</h3>
                    <div class="mb-4">
                        <label for="suspension_reason" class="block text-sm font-medium text-gray-700 mb-2">Suspension Reason *</label>
                        <textarea
                            id="suspension_reason"
                            v-model="suspensionReason"
                            rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Please provide a reason for suspension..."
                            required
                        ></textarea>
                    </div>
                    <div class="flex gap-4">
                        <button @click="showSuspensionModal = false" 
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400">
                            Cancel
                        </button>
                        <button @click="suspendEmployer" 
                                class="flex-1 px-4 py-2 bg-orange-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-orange-700">
                            Suspend
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>