<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    job: Object,
    employer_jobs: Array,
});

const approveForm = useForm({
    notes: '',
});

const rejectForm = useForm({
    reason: '',
});

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'N/A';
};

const formatSalary = (job) => {
    if (!job.salary_min && !job.salary_max) return 'Negotiable';

    const min = job.salary_min ? Number(job.salary_min).toLocaleString() : '';
    const max = job.salary_max ? Number(job.salary_max).toLocaleString() : '';

    if (min && max) {
        return `${min} - ${max} (${job.salary_type})`;
    } else if (min) {
        return `From ${min} (${job.salary_type})`;
    } else if (max) {
        return `Up to ${max} (${job.salary_type})`;
    }

    return 'Negotiable';
};

const getJobTypeText = (type) => {
    const texts = {
        full_time: 'Full Time',
        part_time: 'Part Time',
        contract: 'Contract',
        internship: 'Internship',
        temporary: 'Temporary',
    };
    return texts[type] || type;
};

const getWorkArrangementText = (arrangement) => {
    const texts = {
        on_site: 'On Site',
        remote: 'Remote',
        hybrid: 'Hybrid',
    };
    return texts[arrangement] || arrangement;
};

const getExperienceLevelText = (level) => {
    const texts = {
        entry: 'Entry Level',
        junior: 'Junior',
        mid: 'Mid Level',
        senior: 'Senior',
        executive: 'Executive',
    };
    return texts[level] || level;
};

const getVerificationStatusColor = (status) => {
    const colors = {
        verified: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        rejected: 'bg-red-100 text-red-800',
        suspended: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const approve = () => {
    approveForm.post(route('admin.job-approval.approve', props.job.id));
};

const reject = () => {
    rejectForm.post(route('admin.job-approval.reject', props.job.id));
};
</script>

<template>
    <Head :title="`Review Job - ${job.title}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Review Job Posting</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ job.title }} • {{ job.employer.company_name }}</p>
                </div>
                <Link
                    :href="route('admin.job-approval.index')"
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                >
                    Back to Queue
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Job Details -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Job Details</h3>

                            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Job Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getJobTypeText(job.job_type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Work Arrangement</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getWorkArrangementText(job.work_arrangement) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Experience Level</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getExperienceLevelText(job.experience_level) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Minimum Experience</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.min_experience_years }} years</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Salary Range</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatSalary(job) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Target Course</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.course?.name || 'N/A' }}</dd>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h4 class="mb-2 text-sm font-medium text-gray-500">Description</h4>
                                <div class="whitespace-pre-wrap text-sm text-gray-900">{{ job.description }}</div>
                            </div>

                            <!-- Required Skills -->
                            <div v-if="job.required_skills && job.required_skills.length > 0" class="mb-6">
                                <h4 class="mb-2 text-sm font-medium text-gray-500">Required Skills</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="skill in job.required_skills"
                                        :key="skill"
                                        class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800"
                                    >
                                        {{ skill }}
                                    </span>
                                </div>
                            </div>

                            <!-- Preferred Qualifications -->
                            <div v-if="job.preferred_qualifications && job.preferred_qualifications.length > 0" class="mb-6">
                                <h4 class="mb-2 text-sm font-medium text-gray-500">Preferred Qualifications</h4>
                                <ul class="list-inside list-disc space-y-1 text-sm text-gray-900">
                                    <li v-for="qualification in job.preferred_qualifications" :key="qualification">
                                        {{ qualification }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Benefits -->
                            <div v-if="job.benefits && job.benefits.length > 0" class="mb-6">
                                <h4 class="mb-2 text-sm font-medium text-gray-500">Benefits & Perks</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="benefit in job.benefits"
                                        :key="benefit"
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                    >
                                        {{ benefit }}
                                    </span>
                                </div>
                            </div>

                            <!-- Company Culture -->
                            <div v-if="job.company_culture" class="mb-6">
                                <h4 class="mb-2 text-sm font-medium text-gray-500">Company Culture</h4>
                                <div class="whitespace-pre-wrap text-sm text-gray-900">{{ job.company_culture }}</div>
                            </div>

                            <!-- Contact Information -->
                            <div v-if="job.contact_person || job.contact_email || job.contact_phone" class="mb-6">
                                <h4 class="mb-2 text-sm font-medium text-gray-500">Contact Information</h4>
                                <div class="space-y-1 text-sm text-gray-900">
                                    <div v-if="job.contact_person">Contact Person: {{ job.contact_person }}</div>
                                    <div v-if="job.contact_email">Email: {{ job.contact_email }}</div>
                                    <div v-if="job.contact_phone">Phone: {{ job.contact_phone }}</div>
                                </div>
                            </div>

                            <!-- Important Dates -->
                            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                                <div>
                                    <dt class="font-medium text-gray-500">Posted Date</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(job.created_at) }}</dd>
                                </div>
                                <div v-if="job.application_deadline">
                                    <dt class="font-medium text-gray-500">Application Deadline</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(job.application_deadline) }}</dd>
                                </div>
                                <div v-if="job.job_start_date">
                                    <dt class="font-medium text-gray-500">Expected Start Date</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(job.job_start_date) }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Employer's Other Jobs -->
                        <div v-if="employer_jobs.length > 0" class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Other Jobs by This Employer</h3>
                            <div class="space-y-4">
                                <div v-for="otherJob in employer_jobs" :key="otherJob.id" class="rounded-lg border border-gray-200 p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h4 class="font-medium text-gray-900">{{ otherJob.title }}</h4>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                otherJob.status === 'active'
                                                    ? 'bg-green-100 text-green-800'
                                                    : otherJob.status === 'filled'
                                                      ? 'bg-blue-100 text-blue-800'
                                                      : 'bg-gray-100 text-gray-800',
                                            ]"
                                        >
                                            {{ otherJob.status }}
                                        </span>
                                    </div>
                                    <div class="space-y-1 text-sm text-gray-600">
                                        <div>{{ otherJob.location }} • {{ formatSalary(otherJob) }}</div>
                                        <div>Posted {{ formatDate(otherJob.created_at) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Employer Information -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Employer Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.employer.company_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Verification Status</dt>
                                    <dd class="mt-1">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                getVerificationStatusColor(job.employer.verification_status),
                                            ]"
                                        >
                                            {{ job.employer.verification_status }}
                                        </span>
                                    </dd>
                                </div>
                                <div v-if="job.employer.industry">
                                    <dt class="text-sm font-medium text-gray-500">Industry</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.employer.industry }}</dd>
                                </div>
                                <div v-if="job.employer.company_size">
                                    <dt class="text-sm font-medium text-gray-500">Company Size</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.employer.company_size }}</dd>
                                </div>
                                <div v-if="job.employer.website">
                                    <dt class="text-sm font-medium text-gray-500">Website</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a :href="job.employer.website" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                            {{ job.employer.website }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Registered</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(job.employer.created_at) }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Actions -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Review Actions</h3>

                            <!-- Approve Form -->
                            <form @submit.prevent="approve" class="mb-6 space-y-4">
                                <div>
                                    <label for="approval_notes" class="block text-sm font-medium text-gray-700"> Approval Notes (Optional) </label>
                                    <textarea
                                        id="approval_notes"
                                        v-model="approveForm.notes"
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                        placeholder="Add any notes for the employer..."
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="approveForm.processing"
                                    class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                                >
                                    <span v-if="approveForm.processing">Approving...</span>
                                    <span v-else>Approve Job</span>
                                </button>
                            </form>

                            <!-- Reject Form -->
                            <form @submit.prevent="reject" class="space-y-4">
                                <div>
                                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700"> Rejection Reason * </label>
                                    <textarea
                                        id="rejection_reason"
                                        v-model="rejectForm.reason"
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                        placeholder="Explain why this job is being rejected..."
                                        required
                                    ></textarea>
                                    <div v-if="rejectForm.errors.reason" class="mt-1 text-sm text-red-600">
                                        {{ rejectForm.errors.reason }}
                                    </div>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="rejectForm.processing"
                                    class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                >
                                    <span v-if="rejectForm.processing">Rejecting...</span>
                                    <span v-else>Reject Job</span>
                                </button>
                            </form>
                        </div>

                        <!-- Review Guidelines -->
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-6">
                            <h3 class="mb-4 text-lg font-medium text-blue-900">Review Guidelines</h3>
                            <div class="space-y-3 text-sm text-blue-800">
                                <div class="flex items-start space-x-2">
                                    <svg class="mt-0.5 h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                    <span>Check if job description is clear and professional</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <svg class="mt-0.5 h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                    <span>Verify salary range is reasonable for the role</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <svg class="mt-0.5 h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                    <span>Ensure requirements match the target course</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <svg class="mt-0.5 h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                    <span>Review employer's verification status</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <svg class="mt-0.5 h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                    <span>Check for any discriminatory language</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
