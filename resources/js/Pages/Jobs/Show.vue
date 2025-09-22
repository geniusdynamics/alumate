<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    job: Object,
    matching_graduates: Array,
    application_stats: Object,
});

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

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'N/A';
};

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        pending_approval: 'bg-yellow-100 text-yellow-800',
        paused: 'bg-gray-100 text-gray-800',
        filled: 'bg-blue-100 text-blue-800',
        expired: 'bg-red-100 text-red-800',
        cancelled: 'bg-red-100 text-red-800',
        draft: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        active: 'Active',
        pending_approval: 'Pending Approval',
        paused: 'Paused',
        filled: 'Filled',
        expired: 'Expired',
        cancelled: 'Cancelled',
        draft: 'Draft',
    };
    return texts[status] || status;
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
</script>

<template>
    <Head :title="job.title" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ job.title }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">{{ job.employer.company_name }} • {{ job.location }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span :class="getStatusColor(job.status)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                        {{ getStatusText(job.status) }}
                    </span>
                    <Link
                        :href="route('jobs.edit', job.id)"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Edit Job
                    </Link>
                </div>
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

                        <!-- Matching Graduates -->
                        <div v-if="matching_graduates.length > 0" class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Recommended Graduates</h3>
                            <div class="space-y-4">
                                <div v-for="match in matching_graduates" :key="match.graduate.id" class="rounded-lg border border-gray-200 p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h4 class="font-medium text-gray-900">{{ match.graduate.first_name }} {{ match.graduate.last_name }}</h4>
                                        <span class="text-sm font-medium text-indigo-600">{{ match.match_score }}% match</span>
                                    </div>
                                    <div class="space-y-1 text-sm text-gray-600">
                                        <div>Course: {{ match.graduate.course?.name }}</div>
                                        <div v-if="match.graduate.gpa">GPA: {{ match.graduate.gpa }}</div>
                                        <div>Profile Completion: {{ match.graduate.profile_completion_percentage }}%</div>
                                    </div>
                                    <div class="mt-3 flex items-center space-x-3">
                                        <Link
                                            :href="route('graduates.show', match.graduate.id)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            View Profile
                                        </Link>
                                        <button
                                            @click="$inertia.post(route('jobs.recommend', job.id), { graduate_ids: [match.graduate.id] })"
                                            class="text-sm font-medium text-green-600 hover:text-green-800"
                                        >
                                            Recommend Job
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Application Statistics -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Application Statistics</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Total Applications</span>
                                    <span class="text-sm font-medium text-gray-900">{{ job.total_applications || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Viewed Applications</span>
                                    <span class="text-sm font-medium text-gray-900">{{ job.viewed_applications || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Shortlisted</span>
                                    <span class="text-sm font-medium text-gray-900">{{ job.shortlisted_applications || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Total Views</span>
                                    <span class="text-sm font-medium text-gray-900">{{ job.view_count || 0 }}</span>
                                </div>
                            </div>
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <Link
                                    :href="route('jobs.applications.index', job.id)"
                                    class="block w-full rounded-md bg-indigo-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    View All Applications
                                </Link>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Quick Actions</h3>
                            <div class="space-y-3">
                                <button
                                    v-if="job.status === 'active'"
                                    @click="$inertia.post(route('jobs.pause', job.id))"
                                    class="w-full rounded-md bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700"
                                >
                                    Pause Job
                                </button>
                                <button
                                    v-if="job.status === 'paused'"
                                    @click="$inertia.post(route('jobs.resume', job.id))"
                                    class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                >
                                    Resume Job
                                </button>
                                <button
                                    v-if="['active', 'paused'].includes(job.status)"
                                    @click="$inertia.post(route('jobs.mark-filled', job.id))"
                                    class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                >
                                    Mark as Filled
                                </button>
                                <Link
                                    :href="route('jobs.analytics', job.id)"
                                    class="block w-full rounded-md bg-gray-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-gray-700"
                                >
                                    View Analytics
                                </Link>
                            </div>
                        </div>

                        <!-- Job Performance -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Job Performance</h3>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Application Rate</span>
                                        <span class="font-medium">{{ job.application_rate || 0 }}%</span>
                                    </div>
                                    <div class="mt-1 h-2 rounded-full bg-gray-200">
                                        <div class="h-2 rounded-full bg-indigo-600" :style="{ width: (job.application_rate || 0) + '%' }"></div>
                                    </div>
                                </div>
                                <div v-if="job.application_deadline">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Days Remaining</span>
                                        <span class="font-medium">{{
                                            Math.max(0, Math.ceil((new Date(job.application_deadline) - new Date()) / (1000 * 60 * 60 * 24)))
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
