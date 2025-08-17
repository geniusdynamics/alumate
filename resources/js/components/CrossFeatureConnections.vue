<template>
    <div class="cross-feature-connections">
        <!-- Alumni Profile Integration -->
        <div v-if="showAlumniProfile" class="alumni-profile-integration">
            <AlumniProfile
                :alumni="selectedAlumni"
                :show-connection-actions="true"
                :show-mentorship-request="true"
                :show-job-referral="true"
                @connect-requested="handleConnectionRequest"
                @mentorship-requested="handleMentorshipRequest"
                @job-referral-requested="handleJobReferralRequest"
                @close="closeAlumniProfile"
            />
        </div>

        <!-- Job Recommendation Integration -->
        <div v-if="showJobRecommendations" class="job-recommendations-integration">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Recommended Jobs Based on Your Network
                </h3>
                <div class="space-y-4">
                    <JobCard
                        v-for="job in networkBasedJobs"
                        :key="job.id"
                        :job="job"
                        :show-network-connection="true"
                        @apply="handleJobApplication"
                        @request-introduction="handleIntroductionRequest"
                    />
                </div>
            </div>
        </div>

        <!-- Event Networking Integration -->
        <div v-if="showEventNetworking" class="event-networking-integration">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Connect with Event Attendees
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <AlumniCard
                        v-for="attendee in eventAttendees"
                        :key="attendee.id"
                        :alumnus="attendee"
                        :show-event-context="true"
                        @connect-requested="handleEventConnectionRequest"
                    />
                </div>
            </div>
        </div>

        <!-- Career Timeline Integration -->
        <div v-if="showCareerIntegration" class="career-timeline-integration">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Update Your Career Timeline
                </h3>
                <CareerEntryModal
                    :show="showCareerModal"
                    :prefilled-data="careerUpdateData"
                    @saved="handleCareerUpdate"
                    @close="closeCareerModal"
                />
            </div>
        </div>

        <!-- Skills Assessment Integration -->
        <div v-if="showSkillsAssessment" class="skills-assessment-integration">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Skills Development Tracking
                </h3>
                <SkillsProfile
                    :user-skills="userSkills"
                    :recommended-skills="recommendedSkills"
                    :show-development-path="true"
                    @skill-added="handleSkillAdded"
                    @development-goal-set="handleDevelopmentGoal"
                />
            </div>
        </div>

        <!-- Post Engagement Integration -->
        <div v-if="showPostEngagement" class="post-engagement-integration">
            <div class="space-y-4">
                <div
                    v-for="post in engagementPosts"
                    :key="post.id"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow p-6"
                >
                    <PostReactions
                        :post="post"
                        :show-detailed-engagement="true"
                        @reaction-updated="handleReactionUpdate"
                        @comment-added="handleCommentAdded"
                        @share-requested="handlePostShare"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AlumniProfile from '@/components/AlumniProfile.vue'
import AlumniCard from '@/components/AlumniCard.vue'
import JobCard from '@/components/JobCard.vue'
import CareerEntryModal from '@/components/CareerEntryModal.vue'
import SkillsProfile from '@/components/SkillsProfile.vue'
import PostReactions from '@/components/PostReactions.vue'
import userFlowIntegration from '@/services/UserFlowIntegration'

const props = defineProps({
    context: {
        type: String,
        required: true // 'alumni-directory', 'job-dashboard', 'social-timeline', 'events', 'career'
    },
    contextData: {
        type: Object,
        default: () => ({})
    }
})

const { props: pageProps } = usePage()

// Component visibility states
const showAlumniProfile = ref(false)
const showJobRecommendations = ref(false)
const showEventNetworking = ref(false)
const showCareerIntegration = ref(false)
const showSkillsAssessment = ref(false)
const showPostEngagement = ref(false)
const showCareerModal = ref(false)

// Data states
const selectedAlumni = ref(null)
const networkBasedJobs = reactive([])
const eventAttendees = reactive([])
const userSkills = reactive([])
const recommendedSkills = reactive([])
const engagementPosts = reactive([])
const careerUpdateData = ref({})

// Computed properties for context-based visibility
const contextualIntegrations = computed(() => {
    switch (props.context) {
        case 'alumni-directory':
            return {
                showJobRecommendations: true,
                showEventNetworking: true,
                showSkillsAssessment: true
            }
        case 'job-dashboard':
            return {
                showAlumniProfile: true,
                showCareerIntegration: true,
                showSkillsAssessment: true
            }
        case 'social-timeline':
            return {
                showPostEngagement: true,
                showAlumniProfile: true,
                showEventNetworking: true
            }
        case 'events':
            return {
                showEventNetworking: true,
                showAlumniProfile: true,
                showPostEngagement: true
            }
        case 'career':
            return {
                showJobRecommendations: true,
                showSkillsAssessment: true,
                showAlumniProfile: true
            }
        default:
            return {}
    }
})

onMounted(() => {
    // Apply contextual integrations
    Object.assign({
        showAlumniProfile,
        showJobRecommendations,
        showEventNetworking,
        showCareerIntegration,
        showSkillsAssessment,
        showPostEngagement
    }, contextualIntegrations.value)

    // Load contextual data
    loadContextualData()
})

const loadContextualData = async () => {
    try {
        switch (props.context) {
            case 'alumni-directory':
                await loadNetworkBasedJobs()
                await loadEventAttendees()
                await loadUserSkills()
                break
            case 'job-dashboard':
                await loadRecommendedConnections()
                await loadCareerInsights()
                break
            case 'social-timeline':
                await loadEngagementPosts()
                await loadEventAttendees()
                break
            case 'events':
                await loadEventAttendees()
                await loadEngagementPosts()
                break
            case 'career':
                await loadNetworkBasedJobs()
                await loadUserSkills()
                await loadRecommendedConnections()
                break
        }
    } catch (error) {
        console.error('Failed to load contextual data:', error)
    }
}

const loadNetworkBasedJobs = async () => {
    const response = await fetch('/api/jobs/network-recommendations', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    const data = await response.json()
    if (data.success) {
        networkBasedJobs.splice(0, networkBasedJobs.length, ...data.data.jobs)
    }
}

const loadEventAttendees = async () => {
    if (props.contextData.eventId) {
        const response = await fetch(`/api/events/${props.contextData.eventId}/attendees`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        const data = await response.json()
        if (data.success) {
            eventAttendees.splice(0, eventAttendees.length, ...data.data.attendees)
        }
    }
}

const loadUserSkills = async () => {
    const response = await fetch('/api/skills/profile', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    const data = await response.json()
    if (data.success) {
        userSkills.splice(0, userSkills.length, ...data.data.skills)
        recommendedSkills.splice(0, recommendedSkills.length, ...data.data.recommended)
    }
}

const loadEngagementPosts = async () => {
    const response = await fetch('/api/posts/engagement-opportunities', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    const data = await response.json()
    if (data.success) {
        engagementPosts.splice(0, engagementPosts.length, ...data.data.posts)
    }
}

const loadRecommendedConnections = async () => {
    const response = await fetch('/api/connections/recommendations', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    const data = await response.json()
    if (data.success) {
        // Handle recommended connections
    }
}

const loadCareerInsights = async () => {
    const response = await fetch('/api/career/insights', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    const data = await response.json()
    if (data.success) {
        // Handle career insights
    }
}

// Event handlers
const handleConnectionRequest = async (alumni) => {
    try {
        await userFlowIntegration.sendConnectionRequestAndUpdate(alumni.id)
        userFlowIntegration.showNotification(`Connection request sent to ${alumni.name}!`, 'success')
    } catch (error) {
        userFlowIntegration.showNotification('Failed to send connection request', 'error')
    }
}

const handleMentorshipRequest = async (alumni, requestData) => {
    try {
        await userFlowIntegration.requestMentorshipAndUpdate(alumni.id, requestData)
        userFlowIntegration.showNotification(`Mentorship request sent to ${alumni.name}!`, 'success')
    } catch (error) {
        userFlowIntegration.showNotification('Failed to send mentorship request', 'error')
    }
}

const handleJobReferralRequest = async (alumni, jobId) => {
    try {
        const response = await fetch('/api/job-referrals/request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                referrer_id: alumni.id,
                job_id: jobId
            })
        })
        
        const result = await response.json()
        if (result.success) {
            userFlowIntegration.showNotification('Job referral request sent!', 'success')
        }
    } catch (error) {
        userFlowIntegration.showNotification('Failed to request job referral', 'error')
    }
}

const handleJobApplication = async (job, applicationData) => {
    try {
        await userFlowIntegration.applyToJobAndTrack(job.id, applicationData)
    } catch (error) {
        console.error('Failed to apply to job:', error)
    }
}

const handleIntroductionRequest = async (job, connectionId) => {
    try {
        const response = await fetch('/api/introductions/request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                connection_id: connectionId,
                job_id: job.id
            })
        })
        
        const result = await response.json()
        if (result.success) {
            userFlowIntegration.showNotification('Introduction request sent!', 'success')
        }
    } catch (error) {
        userFlowIntegration.showNotification('Failed to request introduction', 'error')
    }
}

const handleEventConnectionRequest = async (attendee) => {
    try {
        await userFlowIntegration.sendConnectionRequestAndUpdate(
            attendee.id, 
            `Hi! I saw we're both attending the same event. Would love to connect!`
        )
    } catch (error) {
        console.error('Failed to send event connection request:', error)
    }
}

const handleCareerUpdate = async (careerData) => {
    try {
        await userFlowIntegration.updateCareerTimelineAndRefresh(careerData)
        showCareerModal.value = false
    } catch (error) {
        console.error('Failed to update career timeline:', error)
    }
}

const handleSkillAdded = async (skill) => {
    try {
        const response = await fetch('/api/skills/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ skill })
        })
        
        const result = await response.json()
        if (result.success) {
            userSkills.push(result.data.skill)
            userFlowIntegration.showNotification('Skill added to your profile!', 'success')
        }
    } catch (error) {
        userFlowIntegration.showNotification('Failed to add skill', 'error')
    }
}

const handleDevelopmentGoal = async (goal) => {
    try {
        const response = await fetch('/api/career/development-goals', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(goal)
        })
        
        const result = await response.json()
        if (result.success) {
            userFlowIntegration.showNotification('Development goal set!', 'success')
        }
    } catch (error) {
        userFlowIntegration.showNotification('Failed to set development goal', 'error')
    }
}

const handleReactionUpdate = (postId, reactions) => {
    const post = engagementPosts.find(p => p.id === postId)
    if (post) {
        post.engagements = reactions
    }
}

const handleCommentAdded = (postId, comment) => {
    const post = engagementPosts.find(p => p.id === postId)
    if (post) {
        post.comments.push(comment)
    }
}

const handlePostShare = async (post, shareData) => {
    try {
        const response = await fetch(`/api/posts/${post.id}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(shareData)
        })
        
        const result = await response.json()
        if (result.success) {
            userFlowIntegration.showNotification('Post shared successfully!', 'success')
        }
    } catch (error) {
        userFlowIntegration.showNotification('Failed to share post', 'error')
    }
}

const closeAlumniProfile = () => {
    showAlumniProfile.value = false
    selectedAlumni.value = null
}

const closeCareerModal = () => {
    showCareerModal.value = false
    careerUpdateData.value = {}
}
</script>

<style scoped>
.cross-feature-connections {
    position: relative;
}

.alumni-profile-integration,
.job-recommendations-integration,
.event-networking-integration,
.career-timeline-integration,
.skills-assessment-integration,
.post-engagement-integration {
    margin-bottom: 1.5rem;
}
</style>