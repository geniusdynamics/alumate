<template>
    <div class="fixed bottom-4 right-4 z-50 max-w-sm">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="p-4 bg-gradient-to-r from-blue-500 to-purple-600">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <UserCircleIcon class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">Complete Your Profile</h3>
                            <p class="text-blue-100 text-sm">{{ completionData.completion_percentage }}% complete</p>
                        </div>
                    </div>
                    <button
                        @click="$emit('close')"
                        class="text-white hover:text-blue-100"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700">
                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                    <span>Profile Strength</span>
                    <span>{{ getProfileStrength() }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                    <div
                        class="h-2 rounded-full transition-all duration-500"
                        :class="getProgressBarColor()"
                        :style="{ width: completionData.completion_percentage + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-4">
                <p class="text-gray-700 dark:text-gray-300 text-sm mb-4">
                    A complete profile helps you connect better with alumni and discover more opportunities.
                </p>

                <!-- Missing Sections -->
                <div class="space-y-3 mb-4">
                    <div
                        v-for="section in completionData.missing_sections"
                        :key="section.key"
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                                <component :is="getSectionIcon(section.icon)" class="w-4 h-4 text-orange-600 dark:text-orange-400" />
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
                        <button
                            @click="completeSection(section.key)"
                            class="text-blue-600 hover:text-blue-500 text-sm font-medium"
                        >
                            Add
                        </button>
                    </div>
                </div>

                <!-- Benefits -->
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        🎯 Complete your profile to:
                    </h4>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                        <li class="flex items-center space-x-2">
                            <CheckCircleIcon class="w-3 h-3 text-green-500" />
                            <span>Get better job recommendations</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <CheckCircleIcon class="w-3 h-3 text-green-500" />
                            <span>Connect with relevant alumni</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <CheckCircleIcon class="w-3 h-3 text-green-500" />
                            <span>Appear in more searches</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <CheckCircleIcon class="w-3 h-3 text-green-500" />
                            <span>Unlock premium features</span>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-2">
                    <button
                        @click="$emit('close')"
                        class="flex-1 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                    >
                        Later
                    </button>
                    <button
                        @click="completeProfile"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                    >
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
                        class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="dont-show-completion" class="text-xs text-gray-500 dark:text-gray-400">
                        Don't remind me again
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
    XMarkIcon,
    UserCircleIcon,
    CheckCircleIcon,
    BriefcaseIcon,
    AcademicCapIcon,
    MapPinIcon,
    PhotoIcon,
    DocumentTextIcon,
    LinkIcon,
    PhoneIcon,
    EnvelopeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    completionData: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'complete-section'])

const dontShowAgain = ref(false)

const getProfileStrength = () => {
    const percentage = props.completionData.completion_percentage
    if (percentage >= 90) return 'Excellent'
    if (percentage >= 70) return 'Good'
    if (percentage >= 50) return 'Fair'
    return 'Needs Work'
}

const getProgressBarColor = () => {
    const percentage = props.completionData.completion_percentage
    if (percentage >= 90) return 'bg-green-500'
    if (percentage >= 70) return 'bg-blue-500'
    if (percentage >= 50) return 'bg-yellow-500'
    return 'bg-red-500'
}

const getSectionIcon = (iconName) => {
    const icons = {
        'work': BriefcaseIcon,
        'education': AcademicCapIcon,
        'location': MapPinIcon,
        'photo': PhotoIcon,
        'bio': DocumentTextIcon,
        'social': LinkIcon,
        'contact': PhoneIcon,
        'email': EnvelopeIcon
    }
    return icons[iconName] || DocumentTextIcon
}

const completeSection = (sectionKey) => {
    if (dontShowAgain.value) {
        localStorage.setItem('hideProfileCompletion', 'true')
    }
    
    emit('complete-section', sectionKey)
}

const completeProfile = () => {
    if (dontShowAgain.value) {
        localStorage.setItem('hideProfileCompletion', 'true')
    }
    
    // Navigate to profile edit page
    window.location.href = '/profile/edit'
}
</script>