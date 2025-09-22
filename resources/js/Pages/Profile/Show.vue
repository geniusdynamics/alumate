<script setup>
import MobileHamburgerMenu from '@/components/MobileHamburgerMenu.vue';
import PullToRefresh from '@/components/PullToRefresh.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { LoadingPresets, useSpecificLoading } from '@/composables/useLoadingStates';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    AcademicCapIcon,
    BriefcaseIcon,
    CalendarIcon,
    EllipsisHorizontalIcon,
    EnvelopeIcon,
    GlobeAltIcon,
    MapPinIcon,
    PencilIcon,
    PhoneIcon,
    ShareIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    { id: 'projects', label: 'Projects', icon: GlobeAltIcon },
];

const refreshProfile = async () => {
    await profileLoading.withLoading(async () => {
        // Simulate refresh delay
        await new Promise((resolve) => setTimeout(resolve, 1000));
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
        <PullToRefresh @refresh="refreshProfile" class="theme-bg-secondary min-h-screen">
            <!-- Mobile Header -->
            <div class="safe-area-top border-b border-gray-200 bg-white shadow-sm lg:hidden dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">My Profile</h1>
                    <div class="flex items-center space-x-2">
                        <ThemeToggle variant="simple" />
                        <button class="touch-target p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <ShareIcon class="h-5 w-5" />
                        </button>
                        <button class="touch-target p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <EllipsisHorizontalIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop Header -->
            <template #header>
                <div class="hidden items-center justify-between lg:flex">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">My Profile</h2>
                    <div class="flex items-center space-x-3">
                        <button class="btn-mobile-secondary">
                            <ShareIcon class="mr-2 h-4 w-4" />
                            Share Profile
                        </button>
                        <button class="btn-mobile-primary">
                            <PencilIcon class="mr-2 h-4 w-4" />
                            Edit Profile
                        </button>
                    </div>
                </div>
            </template>

            <!-- Main Content -->
            <div class="mobile-container lg:py-12">
                <div class="mx-auto max-w-4xl lg:px-6">
                    <!-- Profile Header Card -->
                    <div class="card-mobile mb-6 lg:rounded-lg lg:bg-white lg:shadow-sm">
                        <div class="lg:p-6">
                            <!-- Profile Header -->
                            <div class="mb-6 flex flex-col items-start space-y-4 sm:flex-row sm:items-center sm:space-x-6 sm:space-y-0">
                                <!-- Avatar -->
                                <div class="relative">
                                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-blue-600 sm:h-32 sm:w-32">
                                        <span class="text-2xl font-bold text-white sm:text-3xl">
                                            {{ graduate?.name?.charAt(0) || 'U' }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="hired"
                                        class="absolute -bottom-2 -right-2 rounded-full bg-green-500 px-2 py-1 text-xs font-bold text-white"
                                    >
                                        Hired
                                    </div>
                                </div>

                                <!-- Profile Info -->
                                <div class="min-w-0 flex-1">
                                    <h1 class="mb-2 text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white">
                                        {{ graduate?.name || 'Alumni Name' }}
                                    </h1>
                                    <p class="mb-3 text-lg text-gray-600 dark:text-gray-400">
                                        {{ profile?.current_position || 'Position' }} at {{ profile?.current_company || 'Company' }}
                                    </p>

                                    <!-- Quick Info -->
                                    <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center">
                                            <AcademicCapIcon class="mr-1 h-4 w-4" />
                                            {{ institution?.name }}
                                        </div>
                                        <div v-if="profile?.location" class="flex items-center">
                                            <MapPinIcon class="mr-1 h-4 w-4" />
                                            {{ profile.location }}
                                        </div>
                                        <div v-if="graduate?.graduation_year" class="flex items-center">
                                            <CalendarIcon class="mr-1 h-4 w-4" />
                                            Class of {{ graduate.graduation_year }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Mobile Action Buttons -->
                                <div class="flex w-full space-x-2 sm:w-auto lg:hidden">
                                    <button class="btn-mobile-secondary flex-1 sm:flex-none">
                                        <ShareIcon class="mr-2 h-4 w-4" />
                                        Share
                                    </button>
                                    <button class="btn-mobile-primary flex-1 sm:flex-none">
                                        <PencilIcon class="mr-2 h-4 w-4" />
                                        Edit
                                    </button>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div
                                v-if="profile?.email || profile?.phone || profile?.website"
                                class="border-t border-gray-200 pt-4 dark:border-gray-700"
                            >
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div v-if="profile.email" class="flex items-center">
                                        <EnvelopeIcon class="mr-2 h-4 w-4 text-gray-400" />
                                        <a :href="`mailto:${profile.email}`" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                            {{ profile.email }}
                                        </a>
                                    </div>
                                    <div v-if="profile.phone" class="flex items-center">
                                        <PhoneIcon class="mr-2 h-4 w-4 text-gray-400" />
                                        <a :href="`tel:${profile.phone}`" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                            {{ profile.phone }}
                                        </a>
                                    </div>
                                    <div v-if="profile.website" class="flex items-center">
                                        <GlobeAltIcon class="mr-2 h-4 w-4 text-gray-400" />
                                        <a :href="profile.website" target="_blank" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                            Website
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Tabs -->
                    <div class="tabs-mobile mb-6 lg:hidden">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            class="tab-mobile"
                            :class="{ active: activeTab === tab.id }"
                        >
                            <component :is="tab.icon" class="mb-1 h-4 w-4" />
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Content Sections -->
                    <div class="space-y-6">
                        <!-- Overview Section -->
                        <div v-if="activeTab === 'overview' || window.innerWidth >= 1024" class="card-mobile lg:rounded-lg lg:bg-white lg:shadow-sm">
                            <div class="lg:p-6">
                                <h2 class="card-mobile-title mb-4">About</h2>
                                <p class="leading-relaxed text-gray-700 dark:text-gray-300">
                                    {{ profile?.bio || 'No bio available yet. Click edit to add your professional summary.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Education Section -->
                        <div v-if="activeTab === 'education' || window.innerWidth >= 1024" class="card-mobile lg:rounded-lg lg:bg-white lg:shadow-sm">
                            <div class="lg:p-6">
                                <h2 class="card-mobile-title mb-4">Education</h2>
                                <div class="space-y-4">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                                                <AcademicCapIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ institution?.name }}</h3>
                                            <p class="text-gray-600 dark:text-gray-400">{{ graduate?.course || 'Course' }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Class of {{ graduate?.graduation_year }}</p>
                                        </div>
                                    </div>

                                    <div v-if="graduate?.previous_institution" class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
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
                        <div
                            v-if="activeTab === 'experience' || window.innerWidth >= 1024"
                            class="card-mobile lg:rounded-lg lg:bg-white lg:shadow-sm"
                        >
                            <div class="lg:p-6">
                                <h2 class="card-mobile-title mb-4">Experience</h2>
                                <div v-if="profile?.current_company" class="space-y-4">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
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
                                <div v-else class="py-8 text-center">
                                    <BriefcaseIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                    <p class="text-gray-500 dark:text-gray-400">No work experience added yet.</p>
                                    <button class="btn-mobile-primary mt-4">Add Experience</button>
                                </div>
                            </div>
                        </div>

                        <!-- Projects Section -->
                        <div
                            v-if="(activeTab === 'projects' || window.innerWidth >= 1024) && profile?.project_gallery"
                            class="card-mobile lg:rounded-lg lg:bg-white lg:shadow-sm"
                        >
                            <div class="lg:p-6">
                                <h2 class="card-mobile-title mb-4">Project Gallery</h2>
                                <div class="mobile-grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="project in profile.project_gallery"
                                        :key="project.title"
                                        @click="openProjectModal(project)"
                                        class="group cursor-pointer"
                                    >
                                        <div class="aspect-w-16 aspect-h-9 mb-3">
                                            <img
                                                :src="project.image_url"
                                                :alt="project.title"
                                                class="h-48 w-full rounded-lg object-cover transition-opacity group-hover:opacity-90"
                                            />
                                        </div>
                                        <h3
                                            class="font-semibold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400"
                                        >
                                            {{ project.title }}
                                        </h3>
                                        <p v-if="project.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
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
        <div v-if="showProjectModal && selectedProject" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="backdrop-mobile" @click="closeProjectModal"></div>
            <div class="modal-mobile-content w-full max-w-2xl">
                <div class="modal-mobile-header">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedProject.title }}</h3>
                    <button @click="closeProjectModal" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>
                <div class="modal-mobile-body">
                    <img :src="selectedProject.image_url" :alt="selectedProject.title" class="mb-4 h-64 w-full rounded-lg object-cover" />
                    <p v-if="selectedProject.description" class="text-gray-700 dark:text-gray-300">
                        {{ selectedProject.description }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

