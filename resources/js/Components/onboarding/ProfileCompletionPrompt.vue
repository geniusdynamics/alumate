<template>
    <div class="fixed bottom-4 right-4 z-50 max-w-sm">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white bg-opacity-20">
                            <UserCircleIcon class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-white">Complete Your Profile</h3>
                            <p class="text-sm text-blue-100">{{ completionData.completion_percentage }}% complete</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="text-white hover:text-blue-100">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="bg-gray-50 px-4 py-2 dark:bg-gray-700">
                <div class="mb-1 flex justify-between text-xs text-gray-600 dark:text-gray-400">
                    <span>Profile Strength</span>
                    <span>{{ getProfileStrength() }}</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-600">
                    <div
                        class="h-2 rounded-full transition-all duration-500"
                        :class="getProgressBarColor()"
                        :style="{ width: completionData.completion_percentage + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-4">
                <p class="mb-4 text-sm text-gray-700 dark:text-gray-300">
                    A complete profile helps you connect better with alumni and discover more opportunities.
                </p>

                <!-- Missing Sections -->
                <div class="mb-4 space-y-3">
                    <div
                        v-for="section in completionData.missing_sections"
                        :key="section.key"
                        class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900">
                                <component :is="getSectionIcon(section.icon)" class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ section.title }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ section.description }}
                                </p>
                            </div>
                        </div>
                        <button @click="completeSection(section.key)" class="text-sm font-medium text-blue-600 hover:text-blue-500">Add</button>
                    </div>
                </div>

                <!-- Benefits -->
                <div class="mb-4">
                    <h4 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">🎯 Complete your profile to:</h4>
                    <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                        <li class="flex items-center space-x-2">
                            <CheckCircleIcon class="h-3 w-3 text-green-500" />
                            <span>Get better job recommendations</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <CheckCircleIcon class="h-3 w-3 text-green-500" />
                            <span>Connect with relevant alumni</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <CheckCircleIcon class="h-3 w-3 text-green-500" />
                            <span>Appear in more searches</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <CheckCircleIcon class="h-3 w-3 text-green-500" />
                            <span>Unlock premium features</span>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-2">
                    <button
                        @click="$emit('close')"
                        class="flex-1 px-3 py-2 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        Later
                    </button>
                    <button @click="completeProfile" class="flex-1 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Complete Now
                    </button>
                </div>
            </div>

            <!-- Dismiss Options -->
            <div class="px-4 pb-4">
                <div class="flex items-center space-x-2">
                    <input
                        id="dont-show-completion"
                        v-model="dontShowAgain"
                        type="checkbox"
                        class="h-3 w-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <label for="dont-show-completion" class="text-xs text-gray-500 dark:text-gray-400"> Don't remind me again </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    AcademicCapIcon,
    BriefcaseIcon,
    CheckCircleIcon,
    DocumentTextIcon,
    EnvelopeIcon,
    LinkIcon,
    MapPinIcon,
    PhoneIcon,
    PhotoIcon,
    UserCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    completionData: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'complete-section']);

const dontShowAgain = ref(false);

const getProfileStrength = () => {
    const percentage = props.completionData.completion_percentage;
    if (percentage >= 90) return 'Excellent';
    if (percentage >= 70) return 'Good';
    if (percentage >= 50) return 'Fair';
    return 'Needs Work';
};

const getProgressBarColor = () => {
    const percentage = props.completionData.completion_percentage;
    if (percentage >= 90) return 'bg-green-500';
    if (percentage >= 70) return 'bg-blue-500';
    if (percentage >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getSectionIcon = (iconName) => {
    const icons = {
        work: BriefcaseIcon,
        education: AcademicCapIcon,
        location: MapPinIcon,
        photo: PhotoIcon,
        bio: DocumentTextIcon,
        social: LinkIcon,
        contact: PhoneIcon,
        email: EnvelopeIcon,
    };
    return icons[iconName] || DocumentTextIcon;
};

const completeSection = (sectionKey) => {
    if (dontShowAgain.value) {
        localStorage.setItem('hideProfileCompletion', 'true');
    }

    emit('complete-section', sectionKey);
};

const completeProfile = () => {
    if (dontShowAgain.value) {
        localStorage.setItem('hideProfileCompletion', 'true');
    }

    // Navigate to profile edit page
    window.location.href = '/profile/edit';
};
</script>
