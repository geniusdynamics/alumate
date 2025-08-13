<template>
    <div v-if="shouldShowOnboarding" class="onboarding-integration">
        <!-- Welcome Tour Component -->
        <WelcomeTour 
            v-if="currentStep === 'welcome'" 
            :auto-start="true"
            :user-role="userRole"
            @tour-completed="handleWelcomeComplete" 
            @tour-skipped="handleSkip" 
        />

        <!-- Feature Introduction Modal -->
        <FeatureIntroModal 
            v-if="currentStep === 'features'" 
            :show="true"
            :feature="currentFeatureData" 
            @close="handleFeatureNext"
            @try-feature="handleFeatureNext"
            @show-related-feature="handleFeatureNext"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import WelcomeTour from './WelcomeTour.vue'
import FeatureIntroModal from './FeatureIntroModal.vue'
import OnboardingService from '@/services/OnboardingService.js'

interface Props {
    autoStart?: boolean
}

interface User {
    id: number
    name: string
    email: string
    roles?: Array<{ name: string }>
}

interface PageProps {
    auth?: {
        user?: User
    }
}

const props = withDefaults(defineProps<Props>(), {
    autoStart: true
})

const emit = defineEmits<{
    complete: []
    skip: []
}>()

const page = usePage<PageProps>()
const user = computed(() => page.props.auth?.user)
const userRole = computed(() => user.value?.roles?.[0]?.name || 'graduate')

const currentStep = ref<'welcome' | 'features' | null>(null)
const currentFeature = ref<string>('')
const onboardingState = ref<any>(null)

const features = [
    {
        id: 'dashboard',
        title: 'Your Dashboard',
        description: 'Your personalized hub for all alumni activities and updates.',
        category: 'social',
        icon: 'chart',
        benefits: [
            'See recent activity from your network',
            'Get personalized recommendations',
            'Quick access to all platform features'
        ],
        actionText: 'Explore Dashboard'
    },
    {
        id: 'networking',
        title: 'Alumni Network',
        description: 'Connect with fellow alumni and expand your professional network.',
        category: 'networking',
        icon: 'users',
        benefits: [
            'Find alumni in your industry',
            'Connect with classmates',
            'Get warm introductions'
        ],
        actionText: 'Start Networking'
    },
    {
        id: 'events',
        title: 'Events & Reunions',
        description: 'Join alumni events and stay connected with your community.',
        category: 'events',
        icon: 'calendar',
        benefits: [
            'Attend virtual and in-person events',
            'Network with other attendees',
            'Stay updated on reunions'
        ],
        actionText: 'Browse Events'
    },
    {
        id: 'jobs',
        title: 'Career Opportunities',
        description: 'Discover job opportunities through your alumni network.',
        category: 'career',
        icon: 'briefcase',
        benefits: [
            'Get job recommendations',
            'See alumni connections at companies',
            'Request referrals'
        ],
        actionText: 'Find Jobs'
    }
]

let currentFeatureIndex = 0

const currentFeatureData = computed(() => {
    return features.find(f => f.id === currentFeature.value) || features[0]
})

const shouldShowOnboarding = computed(() => {
    return currentStep.value !== null && user.value && !onboardingState.value?.has_completed_onboarding
})

const handleWelcomeComplete = () => {
    currentStep.value = 'features'
    currentFeature.value = features[0].id
    OnboardingService.markStepCompleted('welcome')
    OnboardingService.sendOnboardingEvent('welcome_completed')
}

const handleFeatureNext = () => {
    currentFeatureIndex++
    if (currentFeatureIndex < features.length) {
        currentFeature.value = features[currentFeatureIndex].id
    } else {
        handleOnboardingComplete()
    }
}

const handleOnboardingComplete = async () => {
    OnboardingService.markOnboardingCompleted()
    currentStep.value = null
    emit('complete')
}

const handleSkip = async () => {
    OnboardingService.markOnboardingSkipped()
    currentStep.value = null
    emit('skip')
}

const initializeOnboarding = () => {
    if (!user.value || !props.autoStart) return

    try {
        onboardingState.value = OnboardingService.getOnboardingState()

        if (OnboardingService.shouldShowOnboarding()) {
            currentStep.value = 'welcome'
        }
    } catch (error) {
        console.error('Failed to initialize onboarding:', error)
    }
}

onMounted(() => {
    initializeOnboarding()
})

// Expose methods for manual control
defineExpose({
    start: () => {
        currentStep.value = 'welcome'
        currentFeatureIndex = 0
    },
    reset: () => {
        OnboardingService.restartTour()
        initializeOnboarding()
    }
})
</script>

<style scoped>
.onboarding-integration {
    position: relative;
    z-index: 9999;
}
</style>