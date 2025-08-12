<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import MobileHamburgerMenu from '@/components/MobileHamburgerMenu.vue';
import PullToRefresh from '@/components/PullToRefresh.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import SmartLoader from '@/components/ui/SmartLoader.vue';
import SkeletonCard from '@/components/ui/SkeletonCard.vue';
import { useSpecificLoading, LoadingPresets } from '@/composables/useLoadingStates';
import {
    UserIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    MapPinIcon,
    CalendarIcon,
    EnvelopeIcon,
    PhoneIcon,
    GlobeAltIcon,
    PencilIcon,
    ShareIcon,
    EllipsisHorizontalIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    graduate: Object,
    profile: Object,
    institution: Object,
    hired: Boolean,
});

const showProjectModal = ref(false);
const selectedProject = ref(null);
const activeTab = ref('overview');

// Loading states
const profileLoading = useSpecificLoading('profile', 'fetchingProfile');
const projectsLoading = useSpecificLoading('projects');

const tabs = [
    { id: 'overview', label: 'Overview', icon: UserIcon },
    { id: 'education', label: 'Education', icon: AcademicCapIcon },
    { id: 'experience', label: 'Experience', icon: BriefcaseIcon },
    { id: 'projects', label: 'Projects', icon: GlobeAltIcon }
];

const refreshProfile = async () => {
    await profileLoading.withLoading(async () => {
        // Simulate refresh delay
        await new Promise(resolve => setTimeout(resolve, 1000));
        window.location.reload();
    }, LoadingPresets.fetchingProfile);
};

const openProjectModal = (project) => {
    selectedProject.value = project;
    showProjectModal.value = true;
};

const closeProjectModal = () => {
    showProjectModal.value = false;
    selectedProject.value = null;
};
</script>

<template>
    <Head title="My Profile" />

    <AppLayout>
        <!-- Mobile Hamburger Menu -->
        <MobileHamburgerMenu class="lg:hidden" />
        
        <!-- Pull to Refresh -->
        <PullToRefresh @refresh="refreshProfile" class="min-h-screen theme-bg-secondary">
            <!-- Mobile Header -->
            <div class="lg:hidden bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 safe-area-top">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">My Profile</h1>
                    <div class="flex items-center space-x-2">
                        <ThemeToggle variant="simple" />
                        <button class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 touch-target">
                            <ShareIcon class="h-5 w-5" />
                        </button>
                        <button class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 touch-target">
                            <EllipsisHorizontalIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop Header -->
            <template #header>
                <div class="hidden lg:flex items-center justify-between">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        My Profile
                    </h2>
                    <div class="flex items-center space-x-3">
                        <button class="btn-mobile-secondary">
                            <ShareIcon class="h-4 w-4 mr-2" />
                            Share Profile
                        </button>
                        <button class="btn-mobile-primary">
                            <PencilIcon class="h-4 w-4 mr-2" />
                            Edit Profile
                        </button>
                    </div>
                </div>
            </template>

            <!-- Main Content -->
            <div class="mobile-container lg:py-12">
                <div class="max-w-4xl mx-auto lg:px-6">
                    <!-- Profile Header Card -->
                    <div class="card-mobile lg:bg-white lg:shadow-sm lg:rounded-lg mb-6">
                        <div class="lg:p-6">
                            <!-- Profile Header -->
                            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-6 mb-6">
                                <!-- Avatar -->
                                <div class="relative">
                                    <div class="h-24 w-24 sm:h-32 sm:w-32 bg-blue-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold text-2xl sm:text-3xl">
                                            {{ graduate?.name?.charAt(0) || 'U' }}
                                        </span>
                                    </div>
                                    <div v-if="hired" class="absolute -bottom-2 -right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                        Hired
                                    </div>
                                </div>

                                <!-- Profile Info -->
                                <div class="flex-1 min-w-0">
                                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                        {{ graduate?.name || 'Alumni Name' }}
                                    </h1>
                                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-3">
                                        {{ profile?.current_position || 'Position' }} at {{ profile?.current_company || 'Company' }}
                                    </p>
                                    
                                    <!-- Quick Info -->
                                    <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center">
                                            <AcademicCapIcon class="h-4 w-4 mr-1" />
                                            {{ institution?.name }}
                                        </div>
                                        <div v-if="profile?.location" class="flex items-center">
                                            <MapPinIcon class="h-4 w-4 mr-1" />
                                            {{ profile.location }}
                                        </div>
                                        <div v-if="graduate?.graduation_year" class="flex items-center">
                                            <CalendarIcon class="h-4 w-4 mr-1" />
                                            Class of {{ graduate.graduation_year }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Mobile Action Buttons -->
                                <div class="lg:hidden flex space-x-2 w-full sm:w-auto">
                                    <button class="btn-mobile-secondary flex-1 sm:flex-none">
                                        <ShareIcon class="h-4 w-4 mr-2" />
                                        Share
                                    </button>
                                    <button class="btn-mobile-primary flex-1 sm:flex-none">
                                        <PencilIcon class="h-4 w-4 mr-2" />
                                        Edit
                                    </button>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div v-if="profile?.email || profile?.phone || profile?.website" class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div v-if="profile.email" class="flex items-center">
                                        <EnvelopeIcon class="h-4 w-4 text-gray-400 mr-2" />
                                        <a :href="`mailto:${profile.email}`" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                            {{ profile.email }}
                                        </a>
                                    </div>
                                    <div v-if="profile.phone" class="flex items-center">
                                        <PhoneIcon class="h-4 w-4 text-gray-400 mr-2" />
                                        <a :href="`tel:${profile.phone}`" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                            {{ profile.phone }}
                                        </a>
                                    </div>
                                    <div v-if="profile.website" class="flex items-center">
                                        <GlobeAltIcon class="h-4 w-4 text-gray-400 mr-2" />
                                        <a :href="profile.website" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                            Website
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Tabs -->
                    <div class="lg:hidden tabs-mobile mb-6">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            class="tab-mobile"
                            :class="{ 'active': activeTab === tab.id }"
                        >
                            <component :is="tab.icon" class="h-4 w-4 mb-1" />
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Content Sections -->
                    <div class="space-y-6">
                        <!-- Overview Section -->
                        <div v-if="activeTab === 'overview' || window.innerWidth >= 1024" class="card-mobile lg:bg-white lg:shadow-sm lg:rounded-lg">
                            <div class="lg:p-6">
                                <h2 class="card-mobile-title mb-4">About</h2>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                    {{ profile?.bio || 'No bio available yet. Click edit to add your professional summary.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Education Section -->
                        <div v-if="activeTab === 'education' || window.innerWidth >= 1024" class="card-mobile lg:bg-white lg:shadow-sm lg:rounded-lg">
                            <div class="lg:p-6">
                                <h2 class="card-mobile-title mb-4">Education</h2>
                                <div class="space-y-4">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="h-12 w-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                                                <AcademicCapIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ institution?.name }}</h3>
                                            <p class="text-gray-600 dark:text-gray-400">{{ graduate?.course || 'Course' }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Class of {{ graduate?.graduation_year }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div v-if="graduate?.previous_institution" class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="h-12 w-12 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                                <AcademicCapIcon class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ graduate.previous_institution.name }}</h3>
                                            <p class="text-gray-600 dark:text-gray-400">Previous Institution</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Experience Section -->
                        <div v-if="activeTab === 'experience' || window.innerWidth >= 1024" class="card-mobile lg:bg-white lg:shadow-sm lg:rounded-lg">
                            <div class="lg:p-6">
                                <h2 class="card-mobile-title mb-4">Experience</h2>
                                <div v-if="profile?.current_company" class="space-y-4">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="h-12 w-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                                                <BriefcaseIcon class="h-6 w-6 text-green-600 dark:text-green-400" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ profile.current_position }}</h3>
                                            <p class="text-gray-600 dark:text-gray-400">{{ profile.current_company }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Current Position</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8">
                                    <BriefcaseIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                    <p class="text-gray-500 dark:text-gray-400">No work experience added yet.</p>
                                    <button class="btn-mobile-primary mt-4">Add Experience</button>
                                </div>
                            </div>
                        </div>

                        <!-- Projects Section -->
                        <div v-if="(activeTab === 'projects' || window.innerWidth >= 1024) && profile?.project_gallery" class="card-mobile lg:bg-white lg:shadow-sm lg:rounded-lg">
                            <div class="lg:p-6">
                                <h2 class="card-mobile-title mb-4">Project Gallery</h2>
                                <div class="mobile-grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div 
                                        v-for="project in profile.project_gallery" 
                                        :key="project.title"
                                        @click="openProjectModal(project)"
                                        class="cursor-pointer group"
                                    >
                                        <div class="aspect-w-16 aspect-h-9 mb-3">
                                            <img 
                                                :src="project.image_url" 
                                                :alt="project.title" 
                                                class="w-full h-48 object-cover rounded-lg group-hover:opacity-90 transition-opacity"
                                            />
                                        </div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                            {{ project.title }}
                                        </h3>
                                        <p v-if="project.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ project.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PullToRefresh>

        <!-- Project Modal -->
        <div
            v-if="showProjectModal && selectedProject"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="backdrop-mobile" @click="closeProjectModal"></div>
            <div class="modal-mobile-content max-w-2xl w-full">
                <div class="modal-mobile-header">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedProject.title }}</h3>
                    <button @click="closeProjectModal" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>
                <div class="modal-mobile-body">
                    <img 
                        :src="selectedProject.image_url" 
                        :alt="selectedProject.title" 
                        class="w-full h-64 object-cover rounded-lg mb-4"
                    />
                    <p v-if="selectedProject.description" class="text-gray-700 dark:text-gray-300">
                        {{ selectedProject.description }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
