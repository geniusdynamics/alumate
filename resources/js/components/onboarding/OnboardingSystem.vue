<template>
    <div class="onboarding-system">
        <!-- Feature Discovery Modal -->
        <FeatureDiscoveryModal
            v-if="showFeatureDiscovery"
            :features="newFeatures"
            @close="closeFeatureDiscovery"
            @feature-explored="handleFeatureExplored"
        />

        <!-- Guided Tour -->
        <GuidedTour
            v-if="showGuidedTour"
            :tour-steps="currentTourSteps"
            :current-step="currentTourStep"
            @next-step="nextTourStep"
            @previous-step="previousTourStep"
            @complete-tour="completeTour"
            @skip-tour="skipTour"
        />

        <!-- Feature Spotlight -->
        <FeatureSpotlight
            v-if="showFeatureSpotlight"
            :feature="spotlightFeature"
            @close="closeFeatureSpotlight"
            @try-feature="tryFeature"
        />

        <!-- Profile Completion Prompt -->
        <ProfileCompletionPrompt
            v-if="showProfileCompletion"
            :completion-data="profileCompletionData"
            @close="closeProfileCompletion"
            @complete-section="completeProfileSection"
        />

        <!-- Contextual Help Tooltip -->
        <ContextualHelp
            v-if="showContextualHelp"
            :help-content="contextualHelpContent"
            :position="helpPosition"
            @close="closeContextualHelp"
        />

        <!-- What's New Notification -->
        <WhatsNewNotification
            v-if="showWhatsNew"
            :updates="whatsNewUpdates"
            @close="closeWhatsNew"
            @view-details="viewWhatsNewDetails"
        />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import FeatureDiscoveryModal from './FeatureDiscoveryModal.vue'
import GuidedTour from './GuidedTour.vue'
import FeatureSpotlight from './FeatureSpotlight.vue'
import ProfileCompletionPrompt from './ProfileCompletionPrompt.vue'
import ContextualHelp from './ContextualHelp.vue'
import WhatsNewNotification from './WhatsNewNotification.vue'
import { useOnboardingStore } from '@/stores/onboardingStore'
import { useUserPreferences } from '@/composables/useUserPreferences'

const page = usePage()
const onboardingStore = useOnboardingStore()
const userPreferences = useUserPreferences()

// Component visibility states
const showFeatureDiscovery = ref(false)
const showGuidedTour = ref(false)
const showFeatureSpotlight = ref(false)
const showProfileCompletion = ref(false)
const showContextualHelp = ref(false)
const showWhatsNew = ref(false)

// Data states
const newFeatures = reactive([])
const currentTourSteps = reactive([])
const currentTourStep = ref(0)
const spotlightFeature = reactive({})
const profileCompletionData = reactive({})
const contextualHelpContent = reactive({})
const helpPosition = reactive({ x: 0, y: 0 })
const whatsNewUpdates = reactive([])

// Computed properties
const isNewUser = computed(() => {
    return onboardingStore.isNewUser || !onboardingStore.hasCompletedOnboarding
})

const shouldShowOnboarding = computed(() => {
    const currentRoute = page.url
    const onboardingRoutes = ['/dashboard', '/social/timeline', '/alumni/directory']
    return onboardingRoutes.some(route => currentRoute.includes(route))
})

onMounted(async () => {
    await initializeOnboarding()
    setupEventListeners()
})

const initializeOnboarding = async () => {
    try {
        // Load user onboarding state
        await onboardingStore.loadUserOnboardingState()
        
        // Check if user needs onboarding
        if (isNewUser.value && shouldShowOnboarding.value) {
            await startOnboardingFlow()
        }
        
        // Check for new features
        await checkForNewFeatures()
        
        // Check profile completion
        await checkProfileCompletion()
        
        // Load what's new updates
        await loadWhatsNewUpdates()
        
    } catch (error) {
        console.error('Failed to initialize onboarding:', error)
    }
}

const startOnboardingFlow = async () => {
    const userRole = page.props.auth?.user?.roles?.[0]?.name || 'graduate'
    
    // Get role-specific onboarding steps
    const tourSteps = await onboardingStore.getRoleSpecificTour(userRole)
    currentTourSteps.splice(0, currentTourSteps.length, ...tourSteps)
    
    // Show welcome and start tour
    showGuidedTour.value = true
    currentTourStep.value = 0
}

const checkForNewFeatures = async () => {
    try {
        const response = await fetch('/api/onboarding/new-features', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const data = await response.json()
        
        if (data.success && data.features.length > 0) {
            newFeatures.splice(0, newFeatures.length, ...data.features)
            
            // Show feature discovery for returning users
            if (!isNewUser.value) {
                showFeatureDiscovery.value = true
            }
        }
    } catch (error) {
        console.error('Failed to check for new features:', error)
    }
}

const checkProfileCompletion = async () => {
    try {
        const response = await fetch('/api/onboarding/profile-completion', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const data = await response.json()
        
        if (data.success) {
            Object.assign(profileCompletionData, data.completion_data)
            
            // Show profile completion prompt if completion is low
            if (data.completion_data.completion_percentage < 70) {
                showProfileCompletion.value = true
            }
        }
    } catch (error) {
        console.error('Failed to check profile completion:', error)
    }
}

const loadWhatsNewUpdates = async () => {
    try {
        const response = await fetch('/api/onboarding/whats-new', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const data = await response.json()
        
        if (data.success && data.updates.length > 0) {
            whatsNewUpdates.splice(0, whatsNewUpdates.length, ...data.updates)
            
            // Show what's new if there are unread updates
            const hasUnreadUpdates = data.updates.some(update => !update.read)
            if (hasUnreadUpdates) {
                showWhatsNew.value = true
            }
        }
    } catch (error) {
        console.error('Failed to load what\'s new updates:', error)
    }
}

const setupEventListeners = () => {
    // Listen for feature spotlight triggers
    window.addEventListener('show-feature-spotlight', (event) => {
        Object.assign(spotlightFeature, event.detail.feature)
        showFeatureSpotlight.value = true
    })
    
    // Listen for contextual help requests
    window.addEventListener('show-contextual-help', (event) => {
        Object.assign(contextualHelpContent, event.detail.content)
        Object.assign(helpPosition, event.detail.position)
        showContextualHelp.value = true
    })
    
    // Listen for tour restart requests
    window.addEventListener('restart-onboarding-tour', async () => {
        await startOnboardingFlow()
    })
}

// Event handlers
const closeFeatureDiscovery = () => {
    showFeatureDiscovery.value = false
    onboardingStore.markFeatureDiscoveryViewed()
}

const handleFeatureExplored = (feature) => {
    onboardingStore.markFeatureExplored(feature.id)
    
    // Trigger feature spotlight for detailed explanation
    Object.assign(spotlightFeature, feature)
    showFeatureSpotlight.value = true
}

const nextTourStep = () => {
    if (currentTourStep.value < currentTourSteps.length - 1) {
        currentTourStep.value++
    } else {
        completeTour()
    }
}

const previousTourStep = () => {
    if (currentTourStep.value > 0) {
        currentTourStep.value--
    }
}

const completeTour = async () => {
    showGuidedTour.value = false
    await onboardingStore.markOnboardingCompleted()
    
    // Show profile completion prompt after tour
    if (profileCompletionData.completion_percentage < 70) {
        setTimeout(() => {
            showProfileCompletion.value = true
        }, 1000)
    }
}

const skipTour = async () => {
    showGuidedTour.value = false
    await onboardingStore.markOnboardingSkipped()
}

const closeFeatureSpotlight = () => {
    showFeatureSpotlight.value = false
}

const tryFeature = (feature) => {
    showFeatureSpotlight.value = false
    
    // Navigate to feature or trigger feature action
    if (feature.route) {
        window.location.href = feature.route
    } else if (feature.action) {
        window.dispatchEvent(new CustomEvent(feature.action, { detail: feature }))
    }
}

const closeProfileCompletion = () => {
    showProfileCompletion.value = false
    onboardingStore.markProfileCompletionPromptDismissed()
}

const completeProfileSection = (section) => {
    // Navigate to profile section
    window.location.href = `/profile/edit?section=${section}`
}

const closeContextualHelp = () => {
    showContextualHelp.value = false
}

const closeWhatsNew = () => {
    showWhatsNew.value = false
    onboardingStore.markWhatsNewViewed()
}

const viewWhatsNewDetails = (update) => {
    if (update.route) {
        window.location.href = update.route
    }
}

// Expose methods for external use
defineExpose({
    showFeatureSpotlight: (feature) => {
        Object.assign(spotlightFeature, feature)
        showFeatureSpotlight.value = true
    },
    showContextualHelp: (content, position) => {
        Object.assign(contextualHelpContent, content)
        Object.assign(helpPosition, position)
        showContextualHelp.value = true
    },
    restartTour: startOnboardingFlow
})
</script>

<style scoped>
.onboarding-system {
    position: relative;
    z-index: 9999;
}
</style>