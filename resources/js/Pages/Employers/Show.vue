<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
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
        pending: 'bg-yellow-100 text-yellow-800',
        under_review: 'bg-blue-100 text-blue-800',
        verified: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        suspended: 'bg-gray-100 text-gray-800',
        requires_resubmission: 'bg-orange-100 text-orange-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const getCompanySizeBadge = (size) => {
    const badges = {
        startup: 'bg-purple-100 text-purple-800',
        small: 'bg-blue-100 text-blue-800',
        medium: 'bg-green-100 text-green-800',
        large: 'bg-yellow-100 text-yellow-800',
        enterprise: 'bg-red-100 text-red-800',
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
    router.post(
        route('employers.verify', props.employer.id),
        {
            verification_notes: verificationNotes.value,
        },
        {
            onSuccess: () => {
                showVerificationModal.value = false;
                verificationNotes.value = '';
            },
        },
    );
};

const rejectEmployer = () => {
    router.post(
        route('employers.reject', props.employer.id),
        {
            rejection_reason: rejectionReason.value,
        },
        {
            onSuccess: () => {
                showRejectionModal.value = false;
                rejectionReason.value = '';
            },
        },
    );
};

const suspendEmployer = () => {
    router.post(
        route('employers.suspend', props.employer.id),
        {
            suspension_reason: suspensionReason.value,
        },
        {
            onSuccess: () => {
                showSuspensionModal.value = false;
                suspensionReason.value = '';
            },
        },
    );
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
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Employer: {{ employer.company_name }}</h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('employers.edit', employer.id)"
                        class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                    >
                        Edit Profile
                    </Link>
                    <Link :href="route('employers.index')" class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400">
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Company Overview -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ employer.company_name }}</h3>
                                <p class="text-lg text-gray-600">{{ employer.industry || 'Industry not specified' }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                            getVerificationStatusBadge(employer.verification_status),
                                        ]"
                                    >
                                        {{ employer.verification_status?.replace('_', ' ').toUpperCase() }}
                                    </span>
                                    <span
                                        v-if="employer.company_size"
                                        :class="[
                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                            getCompanySizeBadge(employer.company_size),
                                        ]"
                                    >
                                        {{ employer.company_size?.toUpperCase() }}
                                    </span>
                                    <span v-if="!employer.is_active" class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs text-red-800">
                                        Inactive
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Member Since</div>
                                <div class="text-lg font-semibold text-gray-900">{{ formatDate(employer.created_at) }}</div>
                                <div v-if="employer.employer_rating" class="mt-1 text-sm text-gray-600">
                                    Rating: {{ employer.employer_rating }}/5 ({{ employer.total_reviews }} reviews)
                                </div>
                            </div>
                        </div>

                        <div v-if="employer.company_description" class="mb-6">
                            <h4 class="mb-2 text-lg font-medium text-gray-900">About the Company</h4>
                            <p class="text-gray-700">{{ employer.company_description }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Company Details -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Company Information -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Company Information</h3>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                                            <a
                                                v-if="employer.company_website"
                                                :href="employer.company_website"
                                                target="_blank"
                                                class="text-indigo-600 hover:text-indigo-500"
                                            >
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
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Contact Person</h3>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                        <div
                            v-if="employer.verification_documents && employer.verification_documents.length > 0"
                            class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Verification Documents</h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="document in employer.verification_documents"
                                        :key="document.name"
                                        class="flex items-center justify-between rounded-lg bg-gray-50 p-3"
                                    >
                                        <div>
                                            <div class="font-medium text-gray-900">{{ document.name }}</div>
                                            <div class="text-sm text-gray-600">
                                                Uploaded: {{ formatDate(document.uploaded_at) }} • Size:
                                                {{ (document.size / 1024 / 1024).toFixed(2) }} MB
                                            </div>
                                        </div>
                                        <button @click="downloadDocument(document)" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                            Download
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Applications -->
                        <div
                            v-if="statistics.recent_applications && statistics.recent_applications.length > 0"
                            class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Recent Applications</h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="application in statistics.recent_applications"
                                        :key="application.id"
                                        class="flex items-center justify-between rounded-lg bg-gray-50 p-3"
                                    >
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
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Statistics</h3>
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
                        <div v-if="employer.verification_status !== 'verified'" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Verification Actions</h3>
                                <div class="space-y-2">
                                    <button
                                        v-if="employer.verification_status === 'under_review'"
                                        @click="showVerificationModal = true"
                                        class="block w-full rounded-md bg-green-600 px-4 py-2 text-center text-white hover:bg-green-700"
                                    >
                                        Verify Employer
                                    </button>
                                    <button
                                        v-if="employer.verification_status === 'under_review'"
                                        @click="showRejectionModal = true"
                                        class="block w-full rounded-md bg-red-600 px-4 py-2 text-center text-white hover:bg-red-700"
                                    >
                                        Reject Application
                                    </button>
                                    <button
                                        v-if="employer.verification_status === 'verified'"
                                        @click="showSuspensionModal = true"
                                        class="block w-full rounded-md bg-orange-600 px-4 py-2 text-center text-white hover:bg-orange-700"
                                    >
                                        Suspend Employer
                                    </button>
                                    <button
                                        v-if="employer.verification_status === 'suspended'"
                                        @click="reactivateEmployer"
                                        class="block w-full rounded-md bg-blue-600 px-4 py-2 text-center text-white hover:bg-blue-700"
                                    >
                                        Reactivate Employer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Subscription Info -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Subscription</h3>
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
                        <div
                            v-if="employer.verification_completed_at || employer.rejection_reason"
                            class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Verification History</h3>
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
        <div
            v-if="showVerificationModal"
            class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
            @click="showVerificationModal = false"
        >
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg" @click.stop>
                <div class="mt-3">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Verify Employer</h3>
                    <div class="mb-4">
                        <label for="verification_notes" class="mb-2 block text-sm font-medium text-gray-700">Verification Notes (Optional)</label>
                        <textarea
                            id="verification_notes"
                            v-model="verificationNotes"
                            rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Add any notes about the verification..."
                        ></textarea>
                    </div>
                    <div class="flex gap-4">
                        <button
                            @click="showVerificationModal = false"
                            class="flex-1 rounded-md bg-gray-300 px-4 py-2 text-base font-medium text-gray-800 shadow-sm hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            @click="verifyEmployer"
                            class="flex-1 rounded-md bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700"
                        >
                            Verify
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejection Modal -->
        <div
            v-if="showRejectionModal"
            class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
            @click="showRejectionModal = false"
        >
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg" @click.stop>
                <div class="mt-3">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Reject Employer</h3>
                    <div class="mb-4">
                        <label for="rejection_reason" class="mb-2 block text-sm font-medium text-gray-700">Rejection Reason *</label>
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
                        <button
                            @click="showRejectionModal = false"
                            class="flex-1 rounded-md bg-gray-300 px-4 py-2 text-base font-medium text-gray-800 shadow-sm hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            @click="rejectEmployer"
                            class="flex-1 rounded-md bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suspension Modal -->
        <div
            v-if="showSuspensionModal"
            class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
            @click="showSuspensionModal = false"
        >
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg" @click.stop>
                <div class="mt-3">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Suspend Employer</h3>
                    <div class="mb-4">
                        <label for="suspension_reason" class="mb-2 block text-sm font-medium text-gray-700">Suspension Reason *</label>
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
                        <button
                            @click="showSuspensionModal = false"
                            class="flex-1 rounded-md bg-gray-300 px-4 py-2 text-base font-medium text-gray-800 shadow-sm hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            @click="suspendEmployer"
                            class="flex-1 rounded-md bg-orange-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-orange-700"
                        >
                            Suspend
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>













