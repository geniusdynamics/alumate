<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    graduate: Object,
    careerHistory: Array,
    skillsProgress: Object,
    achievements: Array,
});

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'Not provided';
};

const getEmploymentStatusClass = (status) => {
    const classes = {
        employed: 'bg-green-100 text-green-800',
        unemployed: 'bg-red-100 text-red-800',
        self_employed: 'bg-blue-100 text-blue-800',
        student: 'bg-purple-100 text-purple-800',
        other: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getAchievementIcon = (type) => {
    const icons = {
        profile: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        application: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        skill: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
        employment: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6.5',
    };
    return icons[type] || icons['profile'];
};

const getAchievementColor = (type) => {
    const colors = {
        profile: 'bg-blue-100 text-blue-800',
        application: 'bg-green-100 text-green-800',
        skill: 'bg-purple-100 text-purple-800',
        employment: 'bg-yellow-100 text-yellow-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Career Progress" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Career Progress</h2>
                <Link
                    :href="route('graduates.edit', graduate.id)"
                    class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                >
                    Update Profile
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <!-- Current Status Overview -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Current Status</h3>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Employment Status</span>
                                    <span
                                        v-if="graduate.employment_status?.status"
                                        :class="[
                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                            getEmploymentStatusClass(graduate.employment_status.status),
                                        ]"
                                    >
                                        {{ graduate.employment_status.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>

                                <div v-if="graduate.employment_status?.company" class="space-y-2">
                                    <div>
                                        <p class="text-sm text-gray-600">Company</p>
                                        <p class="text-sm font-medium text-gray-900">{{ graduate.employment_status.company }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Position</p>
                                        <p class="text-sm font-medium text-gray-900">{{ graduate.employment_status.job_title }}</p>
                                    </div>
                                    <div v-if="graduate.employment_status.start_date">
                                        <p class="text-sm text-gray-600">Start Date</p>
                                        <p class="text-sm font-medium text-gray-900">{{ formatDate(graduate.employment_status.start_date) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Course</p>
                                        <p class="text-sm font-medium text-gray-900">{{ graduate.course?.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Graduation Year</p>
                                        <p class="text-sm font-medium text-gray-900">{{ graduate.graduation_year }}</p>
                                    </div>
                                    <div v-if="graduate.academic_records?.gpa">
                                        <p class="text-sm text-gray-600">GPA</p>
                                        <p class="text-sm font-medium text-gray-900">{{ graduate.academic_records.gpa }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills Progress -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Skills & Certifications</h3>

                        <!-- Current Skills -->
                        <div v-if="skillsProgress.current_skills && skillsProgress.current_skills.length > 0" class="mb-6">
                            <h4 class="text-md mb-3 font-medium text-gray-800">Current Skills</h4>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="skill in skillsProgress.current_skills"
                                    :key="skill"
                                    class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                        </div>

                        <!-- Certifications -->
                        <div v-if="skillsProgress.certifications && skillsProgress.certifications.length > 0" class="mb-6">
                            <h4 class="text-md mb-3 font-medium text-gray-800">Certifications</h4>
                            <div class="space-y-3">
                                <div v-for="cert in skillsProgress.certifications" :key="cert.name" class="rounded-lg bg-gray-50 p-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ cert.name }}</p>
                                            <p class="text-sm text-gray-600">{{ cert.issuer }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-500">{{ formatDate(cert.date_obtained) }}</p>
                                            <p v-if="cert.expiry_date" class="text-sm text-gray-500">Expires: {{ formatDate(cert.expiry_date) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                (!skillsProgress.current_skills || skillsProgress.current_skills.length === 0) &&
                                (!skillsProgress.certifications || skillsProgress.certifications.length === 0)
                            "
                            class="py-8 text-center"
                        >
                            <p class="mb-4 text-gray-500">No skills or certifications added yet</p>
                            <Link
                                :href="route('graduates.edit', graduate.id)"
                                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Add Skills & Certifications
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Career History -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Career Timeline</h3>

                        <div v-if="careerHistory.length > 0" class="space-y-4">
                            <div v-for="(event, index) in careerHistory" :key="index" class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600">
                                        <div class="h-2 w-2 rounded-full bg-white"></div>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">{{ event.event }}</p>
                                        <p class="text-sm text-gray-500">{{ formatDate(event.date) }}</p>
                                    </div>
                                    <div v-if="event.details" class="mt-2">
                                        <div v-if="event.details.status" class="text-sm text-gray-600">
                                            Status: {{ event.details.status.replace('_', ' ').toUpperCase() }}
                                        </div>
                                        <div v-if="event.details.company" class="text-sm text-gray-600">Company: {{ event.details.company }}</div>
                                        <div v-if="event.details.job_title" class="text-sm text-gray-600">
                                            Position: {{ event.details.job_title }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center">
                            <p class="mb-4 text-gray-500">No career history available</p>
                            <p class="text-sm text-gray-400">Career events will appear here as you update your employment status</p>
                        </div>
                    </div>
                </div>

                <!-- Achievements -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Achievements</h3>

                        <div v-if="achievements.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div v-for="achievement in achievements" :key="achievement.title" class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div
                                            :class="['flex h-10 w-10 items-center justify-center rounded-lg', getAchievementColor(achievement.type)]"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    :d="getAchievementIcon(achievement.type)"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ achievement.title }}</p>
                                        <p class="text-sm text-gray-600">{{ achievement.description }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ formatDate(achievement.date) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-8 text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                                    />
                                </svg>
                            </div>
                            <p class="mb-2 text-gray-500">No achievements yet</p>
                            <p class="text-sm text-gray-400">Complete your profile and apply for jobs to earn achievements</p>
                        </div>
                    </div>
                </div>

                <!-- Career Goals -->
                <div class="rounded-md border border-blue-200 bg-blue-50 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Career Development Tips</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-inside list-disc space-y-1">
                                    <li>Set clear short-term and long-term career goals</li>
                                    <li>Continuously update your skills and certifications</li>
                                    <li>Network with professionals in your field</li>
                                    <li>Seek feedback and mentorship opportunities</li>
                                    <li>Track your achievements and career milestones</li>
                                    <li>Stay updated with industry trends and developments</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
