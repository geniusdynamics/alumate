<template>
    <div class="welcome-tour-container">
        <!-- Welcome Modal -->
        <BaseModal
            :show="showWelcomeModal"
            max-width="2xl"
            :closeable="false"
        >
            <div class="p-8 text-center">
                <!-- Welcome Animation -->
                <div class="mb-6">
                    <div class="w-24 h-24 mx-auto bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mb-4">
                        <RocketLaunchIcon class="w-12 h-12 text-white" />
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Welcome to Your Alumni Platform! 🎉
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Let's get you started with a quick tour of all the amazing features
                    </p>
                </div>

                <!-- Tour Benefits -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-3">
                            <UsersIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Connect</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Find and connect with fellow alumni</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-3">
                            <BriefcaseIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Advance</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Discover career opportunities</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mb-3">
                            <HeartIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Engage</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Join events and give back</p>
                    </div>
                </div>

                <!-- Tour Options -->
                <div class="space-y-4">
                    <div class="flex items-center justify-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                        <ClockIcon class="w-4 h-4" />
                        <span>Takes about 2 minutes</span>
                    </div>
                    
                    <div class="flex space-x-4 justify-center">
                        <button
                            @click="skipTour"
                            class="px-6 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 font-medium"
                        >
                            Skip Tour
                        </button>
                        <button
                            @click="startTour"
                            class="px-8 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center space-x-2"
                        >
                            <span>Start Tour</span>
                            <ArrowRightIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- User Type Selection -->
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        I'm primarily interested in:
                    </p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <button
                            v-for="interest in userInterests"
                            :key="interest.id"
                            @click="selectInterest(interest.id)"
                            :class="[
                                'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                                selectedInterests.includes(interest.id)
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                            ]"
                        >
                            {{ interest.label }}
                        </button>
                    </div>
                </div>
            </div>
        </BaseModal>

        <!-- Guided Tour Component -->
        <GuidedTour
            v-if="showGuidedTour"
            :tour-steps="tourSteps"
            :current-step="currentStep"
            @next-step="nextStep"
            @previous-step="previousStep"
            @complete-tour="completeTour"
            @skip-tour="skipTour"
        />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BaseModal from '@/Components/ui/BaseModal.vue'
import GuidedTour from './GuidedTour.vue'
import OnboardingService from '@/Services/OnboardingService.js'
import {
    RocketLaunchIcon,
    UsersIcon,
    BriefcaseIcon,
    HeartIcon,
    ClockIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    autoStart: {
        type: Boolean,
        default: false
    },
    userRole: {
        type: String,
        default: 'graduate'
    }
})

const emit = defineEmits(['tour-completed', 'tour-skipped'])

const page = usePage()
const showWelcomeModal = ref(false)
const showGuidedTour = ref(false)
const currentStep = ref(0)
const selectedInterests = reactive([])

const userInterests = [
    { id: 'networking', label: 'Networking' },
    { id: 'career', label: 'Career Growth' },
    { id: 'mentoring', label: 'Mentoring' },
    { id: 'events', label: 'Events' },
    { id: 'giving', label: 'Giving Back' },
    { id: 'learning', label: 'Learning' }
]

const tourSteps = computed(() => {
    return OnboardingService.getRoleSpecificTour(props.userRole)
})

onMounted(() => {
    if (props.autoStart && OnboardingService.shouldShowOnboarding()) {
        showWelcomeModal.value = true
    }
})

const selectInterest = (interestId) => {
    const index = selectedInterests.indexOf(interestId)
    if (index > -1) {
        selectedInterests.splice(index, 1)
    } else {
        selectedInterests.push(interestId)
    }
}

const startTour = async () => {
    showWelcomeModal.value = false
    
    // Save user interests
    if (selectedInterests.length > 0) {
        await saveUserInterests()
    }
    
    // Start the guided tour
    showGuidedTour.value = true
    currentStep.value = 0
    
    // Send analytics event
    OnboardingService.sendOnboardingEvent('tour_started', {
        user_role: props.userRole,
        selected_interests: selectedInterests
    })
}

const skipTour = async () => {
    showWelcomeModal.value = false
    showGuidedTour.value = false
    
    // Mark as skipped
    OnboardingService.markOnboardingSkipped()
    
    // Send analytics event
    OnboardingService.sendOnboardingEvent('tour_skipped', {
        user_role: props.userRole,
        step_reached: currentStep.value
    })
    
    emit('tour-skipped')
}

const nextStep = () => {
    if (currentStep.value < tourSteps.value.length - 1) {
        currentStep.value++
        OnboardingService.markStepCompleted(tourSteps.value[currentStep.value - 1].id)
    } else {
        completeTour()
    }
}

const previousStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--
    }
}

const completeTour = async () => {
    showGuidedTour.value = false
    
    // Mark as completed
    OnboardingService.markOnboardingCompleted()
    
    // Send analytics event
    OnboardingService.sendOnboardingEvent('tour_completed', {
        user_role: props.userRole,
        total_steps: tourSteps.value.length,
        selected_interests: selectedInterests
    })
    
    emit('tour-completed')
    
    // Show success message
    showCompletionMessage()
}

const saveUserInterests = async () => {
    try {
        await fetch('/api/user/interests', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                interests: selectedInterests
            })
        })
    } catch (error) {
        console.error('Failed to save user interests:', error)
    }
}

const showCompletionMessage = () => {
    // Show a brief success notification
    window.dispatchEvent(new CustomEvent('show-notification', {
        detail: {
            type: 'success',
            title: 'Welcome Tour Complete! 🎉',
            message: 'You\'re all set to explore your alumni platform. Happy networking!',
            duration: 5000
        }
    }))
}

// Expose methods for external use
defineExpose({
    startTour: () => {
        showWelcomeModal.value = true
    },
    skipTour,
    restartTour: () => {
        currentStep.value = 0
        showWelcomeModal.value = true
    }
})
</script>

<style scoped>
.welcome-tour-container {
    position: relative;
    z-index: 9999;
}

/* Animation for welcome modal */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.welcome-tour-container .modal-content {
    animation: fadeInUp 0.5s ease-out;
}
</style>