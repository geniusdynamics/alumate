<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Employer Verification" />
        
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Employer Verification</h1>
                        <p class="mt-1 text-sm text-gray-600">Review and verify employer applications</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Verification Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <StatCard
                    title="Pending Review"
                    :value="verificationStats.pending"
                    icon="ClockIcon"
                    color="yellow"
                />
                <StatCard
                    title="Under Review"
                    :value="verificationStats.under_review"
                    icon="EyeIcon"
                    color="blue"
                />
                <StatCard
                    title="Verified"
                    :value="verificationStats.verified"
                    icon="CheckCircleIcon"
                    color="green"
                />
                <StatCard
                    title="Rejected"
                    :value="verificationStats.rejected"
                    icon="XCircleIcon"
                    color="red"
                />
            </div>

            <!-- Pending Employers -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Pending Verification ({{ pendingEmployers.length }})
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Employers awaiting verification review
                    </p>
                </div>
                
                <ul class="divide-y divide-gray-200">
                    <li v-for="employer in pendingEmployers" :key="employer.id" class="px-4 py-6 sm:px-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <BuildingOfficeIcon class="h-10 w-10 text-gray-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center">
                                        <p class="text-sm font-medium text-indigo-600 truncate">
                                            {{ employer.company_name }}
                                        </p>
                                        <span 
                                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getVerificationStatusClass(employer.verification_status)"
                                        >
                                            {{ employer.verification_status.replace('_', ' ') }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center text-sm text-gray-500">
                                        <EnvelopeIcon class="h-4 w-4 mr-1" />
                                        {{ employer.user?.email }}
                                    </div>
                                    <div class="mt-1 text-sm text-gray-500">
                                        <p>Industry: {{ employer.industry || 'Not specified' }}</p>
                                        <p>Location: {{ employer.location || 'Not specified' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <Link
                                    :href="route('employers.show', employer.id)"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                >
                                    View Details
                                </Link>
                                <button
                                    @click="approveEmployer(employer)"
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700"
                                >
                                    Approve
                                </button>
                                <button
                                    @click="rejectEmployer(employer)"
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                        
                        <!-- Additional Info -->
                        <div class="mt-3 text-sm text-gray-500">
                            <p>Submitted: {{ formatDate(employer.created_at) }}</p>
                            <p v-if="employer.verification_documents">Documents: {{ employer.verification_documents.length }} uploaded</p>
                        </div>
                    </li>
                </ul>
                
                <div v-if="pendingEmployers.length === 0" class="text-center py-12">
                    <BuildingOfficeIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No pending verifications</h3>
                    <p class="mt-1 text-sm text-gray-500">All employer applications have been processed.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BuildingOfficeIcon,
    ClockIcon,
    EyeIcon,
    CheckCircleIcon,
    XCircleIcon,
    EnvelopeIcon,
} from '@heroicons/vue/24/outline';
import StatCard from '@/components/StatCard.vue';
import { format } from 'date-fns';

const props = defineProps({
    pendingEmployers: Array,
    verificationStats: Object,
});

const getVerificationStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'under_review': 'bg-blue-100 text-blue-800',
        'verified': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy');
};

const approveEmployer = (employer) => {
    router.post(route('employers.verify', employer.id), {
        status: 'verified'
    });
};

const rejectEmployer = (employer) => {
    router.post(route('employers.verify', employer.id), {
        status: 'rejected'
    });
};
</script>
                              