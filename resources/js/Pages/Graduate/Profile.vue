<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    graduate: Object,
    profileCompletion: Number,
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

const profileCompletionColor = computed(() => {
    if (props.profileCompletion >= 80) return 'text-green-600';
    if (props.profileCompletion >= 60) return 'text-yellow-600';
    return 'text-red-600';
});

const completionSections = computed(() => {
    const sections = [
        { name: 'Basic Information', completed: !!(props.graduate.user?.name && props.graduate.user?.email) },
        { name: 'Academic Details', completed: !!(props.graduate.course && props.graduate.graduation_year) },
        { name: 'Contact Information', completed: !!(props.graduate.personal_information?.phone || props.graduate.personal_information?.address) },
        { name: 'Employment Status', completed: !!props.graduate.employment_status?.status },
        { name: 'Skills & Certifications', completed: !!(props.graduate.skills?.length > 0 || props.graduate.certifications?.length > 0) },
        { name: 'Privacy Settings', completed: !!props.graduate.profile_visibility },
    ];
    return sections;
});
</script>

<template>
    <Head title="My Profile" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">My Profile</h2>
                <Link
                    :href="route('graduates.edit', graduate.id)"
                    class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                >
                    Edit Profile
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <!-- Profile Completion -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Profile Completion</h3>
                            <span :class="['text-lg font-bold', profileCompletionColor]"> {{ Math.round(profileCompletion) }}% </span>
                        </div>

                        <div class="mb-4 h-3 w-full rounded-full bg-gray-200">
                            <div
                                :class="[
                                    'h-3 rounded-full transition-all duration-300',
                                    profileCompletion >= 80 ? 'bg-green-500' : profileCompletion >= 60 ? 'bg-yellow-500' : 'bg-red-500',
                                ]"
                                :style="`width: ${profileCompletion}%`"
                            ></div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div
                                v-for="section in completionSections"
                                :key="section.name"
                                class="flex items-center justify-between rounded-lg bg-gray-50 p-3"
                            >
                                <span class="text-sm text-gray-700">{{ section.name }}</span>
                                <div class="flex items-center">
                                    <svg v-if="section.completed" class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <svg v-else class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div v-if="profileCompletion < 100" class="mt-4 rounded-md border border-yellow-200 bg-yellow-50 p-3">
                            <p class="text-sm text-yellow-800">
                                Complete your profile to improve your visibility to employers and get better job recommendations.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Basic Information</h3>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.user?.name || 'Not provided' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.user?.email || 'Not provided' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Student ID</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.student_id || 'Not provided' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Date of Birth</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(graduate.personal_information?.date_of_birth) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Information -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Academic Information</h3>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Course</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.course?.name || 'Not provided' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Graduation Year</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.graduation_year || 'Not provided' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">GPA</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.academic_records?.gpa || 'Not provided' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Academic Standing</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.academic_records?.academic_standing || 'Not provided' }}</p>
                            </div>
                        </div>

                        <div v-if="graduate.academic_records?.honors" class="mt-6">
                            <label class="block text-sm font-medium text-gray-500">Honors & Awards</label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="honor in graduate.academic_records.honors"
                                    :key="honor"
                                    class="inline-flex rounded bg-purple-100 px-2 py-1 text-xs text-purple-800"
                                >
                                    {{ honor }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Contact Information</h3>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.personal_information?.phone || 'Not provided' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Alternative Phone</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.personal_information?.alternative_phone || 'Not provided' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-500">Address</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.personal_information?.address || 'Not provided' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Status -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Employment Status</h3>
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

                        <div v-if="graduate.employment_status" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div v-if="graduate.employment_status.company">
                                <label class="block text-sm font-medium text-gray-500">Company</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.employment_status.company }}</p>
                            </div>
                            <div v-if="graduate.employment_status.job_title">
                                <label class="block text-sm font-medium text-gray-500">Job Title</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.employment_status.job_title }}</p>
                            </div>
                            <div v-if="graduate.employment_status.start_date">
                                <label class="block text-sm font-medium text-gray-500">Start Date</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDate(graduate.employment_status.start_date) }}</p>
                            </div>
                            <div v-if="graduate.employment_status.salary">
                                <label class="block text-sm font-medium text-gray-500">Salary</label>
                                <p class="mt-1 text-sm text-gray-900">{{ graduate.employment_status.salary }}</p>
                            </div>
                        </div>

                        <div v-else class="py-6 text-center">
                            <p class="text-gray-500">Employment status not provided</p>
                        </div>
                    </div>
                </div>

                <!-- Skills & Certifications -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Skills & Certifications</h3>

                        <div v-if="graduate.skills && graduate.skills.length > 0" class="mb-6">
                            <label class="mb-2 block text-sm font-medium text-gray-500">Skills</label>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="skill in graduate.skills"
                                    :key="skill"
                                    class="inline-flex rounded bg-blue-100 px-2 py-1 text-xs text-blue-800"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                        </div>

                        <div v-if="graduate.certifications && graduate.certifications.length > 0">
                            <label class="mb-2 block text-sm font-medium text-gray-500">Certifications</label>
                            <div class="space-y-2">
                                <div v-for="cert in graduate.certifications" :key="cert.name" class="rounded-lg bg-gray-50 p-3">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ cert.name }}</p>
                                            <p class="text-xs text-gray-600">{{ cert.issuer }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500">{{ formatDate(cert.date_obtained) }}</p>
                                            <p v-if="cert.expiry_date" class="text-xs text-gray-500">Expires: {{ formatDate(cert.expiry_date) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                (!graduate.skills || graduate.skills.length === 0) &&
                                (!graduate.certifications || graduate.certifications.length === 0)
                            "
                            class="py-6 text-center"
                        >
                            <p class="text-gray-500">No skills or certifications added yet</p>
                        </div>
                    </div>
                </div>

                <!-- Privacy Settings -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Privacy Settings</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Profile Visibility</p>
                                    <p class="text-xs text-gray-500">Control who can see your profile</p>
                                </div>
                                <span class="inline-flex rounded bg-gray-100 px-2 py-1 text-xs text-gray-800">
                                    {{ graduate.profile_visibility?.toUpperCase() || 'NOT SET' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Allow Employer Contact</p>
                                    <p class="text-xs text-gray-500">Allow employers to contact you directly</p>
                                </div>
                                <span
                                    :class="[
                                        'inline-flex rounded px-2 py-1 text-xs',
                                        graduate.allow_employer_contact ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                    ]"
                                >
                                    {{ graduate.allow_employer_contact ? 'ENABLED' : 'DISABLED' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Show Employment Status</p>
                                    <p class="text-xs text-gray-500">Display your current employment status</p>
                                </div>
                                <span
                                    :class="[
                                        'inline-flex rounded px-2 py-1 text-xs',
                                        graduate.show_employment_status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                    ]"
                                >
                                    {{ graduate.show_employment_status ? 'VISIBLE' : 'HIDDEN' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Actions -->
                <div class="flex justify-center">
                    <Link
                        :href="route('graduates.edit', graduate.id)"
                        class="rounded-md bg-indigo-600 px-6 py-3 font-medium text-white hover:bg-indigo-700"
                    >
                        Edit Profile
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
