<template>
    <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
        <!-- Match Score Badge -->
        <div v-if="showMatchScore && job.match_score" class="flex justify-between items-start mb-3">
            <div class="flex items-center space-x-2">
                <span :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    getMatchScoreColor(job.match_score)
                ]">
                    {{ Math.round(job.match_score) }}% Match
                </span>
                <span v-if="isRecommendation" class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                    Recommended
                </span>
            </div>
            <button
                @click="showMatchDetails = !showMatchDetails"
                class="text-gray-400 hover:text-gray-600"
                title="View match details"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </div>

        <!-- Job Header -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">
                <a :href="route('jobs.public.show', job.id)" class="hover:text-indigo-600">
                    {{ job.title }}
                </a>
            </h3>
            <p class="text-sm text-gray-600">
                {{ job.employer?.company_name }}
                <span v-if="job.employer?.verification_status === 'verified'" class="ml-1">
                    <svg class="w-4 h-4 inline text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </span>
            </p>
        </div>

        <!-- Job Details -->
        <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ job.location }}
            </div>

            <div v-if="job.salary_range" class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                {{ job.salary_range }}
            </div>

            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                </svg>
                {{ formatJobType(job.job_type) }}
                <span v-if="job.work_arrangement" class="ml-2">
                    • {{ formatWorkArrangement(job.work_arrangement) }}
                </span>
            </div>

            <div v-if="job.application_deadline" class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Apply by {{ formatDate(job.application_deadline) }}
            </div>
        </div>

        <!-- Skills -->
        <div v-if="job.required_skills && job.required_skills.length > 0" class="mb-4">
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="skill in job.required_skills.slice(0, 4)"
                    :key="skill"
                    class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs"
                >
                    {{ skill }}
                </span>
                <span
                    v-if="job.required_skills.length > 4"
                    class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs"
                >
                    +{{ job.required_skills.length - 4 }} more
                </span>
            </div>
        </div>

        <!-- Match Details -->
        <div v-if="showMatchDetails && job.match_factors" class="mb-4 p-3 bg-gray-50 rounded-md">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Match Details</h4>
            <div class="space-y-1 text-xs text-gray-600">
                <div v-if="job.match_factors.course_match">
                    ✓ Perfect course match
                </div>
                <div v-if="job.match_factors.skills_match">
                    ✓ {{ job.match_factors.skills_match.exact_matches?.length || 0 }} matching skills
                </div>
                <div v-if="job.match_factors.gpa">
                    ✓ GPA: {{ job.match_factors.gpa }}
                </div>
                <div v-if="job.match_factors.profile_completion">
                    ✓ Profile {{ job.match_factors.profile_completion }}% complete
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-100">
            <div class="flex items-center space-x-4 text-xs text-gray-500">
                <span>{{ job.total_applications || 0 }} applications</span>
                <span>{{ formatDate(job.created_at) }}</span>
            </div>

            <div class="flex space-x-2">
                <a
                    :href="route('jobs.public.show', job.id)"
                    class="px-3 py-1 text-sm text-indigo-600 hover:text-indigo-500"
                >
                    View Details
                </a>
                <button
                    v-if="canApply"
                    @click="applyToJob"
                    class="px-3 py-1 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700"
                >
                    Apply Now
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        job: Object,
        showMatchScore: {
            type: Boolean,
            default: false
        },
        isRecommendation: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            showMatchDetails: false
        }
    },

    computed: {
        canApply() {
            return this.$page.props.auth.user?.roles?.includes('graduate') && 
                   this.job.status === 'active'
        }
    },

    methods: {
        getMatchScoreColor(score) {
            if (score >= 80) return 'bg-green-100 text-green-800'
            if (score >= 60) return 'bg-yellow-100 text-yellow-800'
            return 'bg-red-100 text-red-800'
        },

        formatJobType(type) {
            return type?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Not specified'
        },

        formatWorkArrangement(arrangement) {
            return arrangement?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Not specified'
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString()
        },

        async applyToJob() {
            try {
                await this.$inertia.post(route('jobs.apply', this.job.id))
                this.$toast.success('Application submitted successfully!')
            } catch (error) {
                this.$toast.error('Failed to submit application.')
            }
        }
    }
}
</script>