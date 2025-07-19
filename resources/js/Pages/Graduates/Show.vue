<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import UpdateEmploymentForm from '@/Pages/Graduates/Partials/UpdateEmploymentForm.vue';
import UpdatePrivacyForm from '@/Pages/Graduates/Partials/UpdatePrivacyForm.vue';
import ProfileCompletionProgress from '@/Pages/Graduates/Partials/ProfileCompletionProgress.vue';
import GraduateActions from '@/Pages/Graduates/Partials/GraduateActions.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    graduate: Object,
    profileCompletion: Number,
    employmentHistory: Array,
    auditTrail: Array,
});

const showUpdateEmployment = ref(false);
const showUpdatePrivacy = ref(false);
const showAuditTrail = ref(false);

const handleEmploymentUpdate = () => {
    showUpdateEmployment.value = false;
    // The page will be refreshed by Inertia after the update
};

const handlePrivacyUpdate = () => {
    showUpdatePrivacy.value = false;
    // The page will be refreshed by Inertia after the update
};

const getEmploymentStatusBadge = (status) => {
    const badges = {
        'unemployed': 'bg-red-100 text-red-800',
        'employed': 'bg-green-100 text-green-800',
        'self_employed': 'bg-blue-100 text-blue-800',
        'further_studies': 'bg-purple-100 text-purple-800',
        'other': 'bg-gray-100 text-gray-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const getAcademicStandingBadge = (standing) => {
    const badges = {
        'excellent': 'bg-green-100 text-green-800',
        'very_good': 'bg-blue-100 text-blue-800',
        'good': 'bg-yellow-100 text-yellow-800',
        'satisfactory': 'bg-orange-100 text-orange-800',
        'pass': 'bg-gray-100 text-gray-800',
    };
    return badges[standing] || 'bg-gray-100 text-gray-800';
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
</script>

<template>
    <Head :title="`${graduate.name} - Graduate Profile`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Graduate Profile: {{ graduate.name }}
                </h2>
                <div class="flex gap-2">
                    <GraduateActions :graduate="graduate" :show-all="true" />
                    <Link :href="route('graduates.index')" 
                          class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Profile Completion Status -->
                <ProfileCompletionProgress :graduate="graduate" />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Personal Information -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Basic Information -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Email</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Phone</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.phone || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Student ID</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.student_id || 'Not provided' }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-500">Address</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.address || 'Not provided' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Information -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Academic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Course</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.course?.name || 'Not specified' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Graduation Year</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.graduation_year }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">GPA</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.gpa || 'Not provided' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Academic Standing</label>
                                        <span v-if="graduate.academic_standing" 
                                              :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getAcademicStandingBadge(graduate.academic_standing)]">
                                            {{ graduate.academic_standing.replace('_', ' ').toUpperCase() }}
                                        </span>
                                        <p v-else class="mt-1 text-sm text-gray-900">Not provided</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Information -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Employment Information</h3>
                                    <button @click="showUpdateEmployment = !showUpdateEmployment" 
                                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        {{ showUpdateEmployment ? 'Cancel' : 'Update Employment' }}
                                    </button>
                                </div>
                                
                                <div v-if="!showUpdateEmployment" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Employment Status</label>
                                        <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1', getEmploymentStatusBadge(graduate.employment_status)]">
                                            {{ graduate.employment_status?.replace('_', ' ').toUpperCase() }}
                                        </span>
                                    </div>
                                    <div v-if="graduate.current_job_title">
                                        <label class="block text-sm font-medium text-gray-500">Job Title</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.current_job_title }}</p>
                                    </div>
                                    <div v-if="graduate.current_company">
                                        <label class="block text-sm font-medium text-gray-500">Company</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ graduate.current_company }}</p>
                                    </div>
                                    <div v-if="graduate.current_salary">
                                        <label class="block text-sm font-medium text-gray-500">Annual Salary</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatCurrency(graduate.current_salary) }}</p>
                                    </div>
                                    <div v-if="graduate.employment_start_date">
                                        <label class="block text-sm font-medium text-gray-500">Employment Start Date</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(graduate.employment_start_date) }}</p>
                                    </div>
                                </div>
                                
                                <div v-if="showUpdateEmployment">
                                    <UpdateEmploymentForm :graduate="graduate" @updated="handleEmploymentUpdate" />
                                </div>
                            </div>
                        </div>

                        <!-- Skills -->
                        <div v-if="graduate.skills && graduate.skills.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Skills</h3>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="skill in graduate.skills" :key="skill"
                                          class="inline-flex px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                                        {{ skill }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Certifications -->
                        <div v-if="graduate.certifications && graduate.certifications.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Certifications</h3>
                                <div class="space-y-3">
                                    <div v-for="cert in graduate.certifications" :key="cert.name" 
                                         class="border border-gray-200 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900">{{ cert.name }}</h4>
                                        <div class="mt-1 text-sm text-gray-600">
                                            <span v-if="cert.issuer">Issued by: {{ cert.issuer }}</span>
                                            <span v-if="cert.issuer && cert.date_obtained"> • </span>
                                            <span v-if="cert.date_obtained">{{ formatDate(cert.date_obtained) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Applications -->
                        <div v-if="graduate.applications && graduate.applications.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Job Applications</h3>
                                <div class="space-y-3">
                                    <div v-for="application in graduate.applications" :key="application.id" 
                                         class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-medium text-gray-900">{{ application.job?.title }}</h4>
                                                <p class="text-sm text-gray-600">{{ application.job?.employer?.company_name }}</p>
                                                <p class="text-xs text-gray-500">Applied: {{ formatDate(application.created_at) }}</p>
                                            </div>
                                            <span :class="[
                                                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                                application.status === 'hired' ? 'bg-green-100 text-green-800' :
                                                application.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                                application.status === 'interviewed' ? 'bg-blue-100 text-blue-800' :
                                                'bg-yellow-100 text-yellow-800'
                                            ]">
                                                {{ application.status?.toUpperCase() }}
                                            </span>
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
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Profile Completion</span>
                                        <span class="text-sm font-medium text-gray-900">{{ Math.round(profileCompletion) }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Skills Count</span>
                                        <span class="text-sm font-medium text-gray-900">{{ (graduate.skills || []).length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Certifications</span>
                                        <span class="text-sm font-medium text-gray-900">{{ (graduate.certifications || []).length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Job Applications</span>
                                        <span class="text-sm font-medium text-gray-900">{{ (graduate.applications || []).length }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy Settings -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Privacy Settings</h3>
                                    <button @click="showUpdatePrivacy = !showUpdatePrivacy" 
                                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        {{ showUpdatePrivacy ? 'Cancel' : 'Update Privacy' }}
                                    </button>
                                </div>
                                
                                <div v-if="!showUpdatePrivacy" class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Profile Visible</span>
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                            graduate.privacy_settings?.profile_visible ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]">
                                            {{ graduate.privacy_settings?.profile_visible ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Contact Visible</span>
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                            graduate.privacy_settings?.contact_visible ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]">
                                            {{ graduate.privacy_settings?.contact_visible ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Employment Visible</span>
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                            graduate.privacy_settings?.employment_visible ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]">
                                            {{ graduate.privacy_settings?.employment_visible ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Employer Contact</span>
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                            graduate.allow_employer_contact ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        ]">
                                            {{ graduate.allow_employer_contact ? 'Allowed' : 'Blocked' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Job Search Active</span>
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                            graduate.job_search_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                        ]">
                                            {{ graduate.job_search_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div v-if="showUpdatePrivacy">
                                    <UpdatePrivacyForm :graduate="graduate" @updated="handlePrivacyUpdate" />
                                </div>
                            </div>
                        </div>

                        <!-- Audit Trail -->
                        <div v-if="auditTrail && auditTrail.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Change History</h3>
                                    <button @click="showAuditTrail = !showAuditTrail" 
                                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        {{ showAuditTrail ? 'Hide' : 'Show History' }}
                                    </button>
                                </div>
                                
                                <div v-if="showAuditTrail" class="space-y-3 max-h-64 overflow-y-auto">
                                    <div v-for="entry in auditTrail" :key="entry.id" 
                                         class="border border-gray-200 rounded-lg p-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="text-sm font-medium text-gray-900">{{ entry.action }}</span>
                                            <span class="text-xs text-gray-500">{{ formatDate(entry.created_at) }}</span>
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <p>{{ entry.description }}</p>
                                            <p v-if="entry.user_name" class="text-xs text-gray-500 mt-1">
                                                Changed by: {{ entry.user_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Last Updates -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Last Updates</h3>
                                <div class="space-y-3">
                                    <div v-if="graduate.last_profile_update">
                                        <span class="text-sm text-gray-600">Profile Updated</span>
                                        <p class="text-xs text-gray-500">{{ formatDate(graduate.last_profile_update) }}</p>
                                    </div>
                                    <div v-if="graduate.last_employment_update">
                                        <span class="text-sm text-gray-600">Employment Updated</span>
                                        <p class="text-xs text-gray-500">{{ formatDate(graduate.last_employment_update) }}</p>
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