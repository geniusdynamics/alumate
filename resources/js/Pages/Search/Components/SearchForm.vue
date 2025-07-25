<template>
    <form @submit.prevent="performSearch" class="space-y-6">
        <!-- Basic Search -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Keywords</label>
                <div class="relative">
                    <input
                        v-model="form.keywords"
                        type="text"
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        :placeholder="getKeywordsPlaceholder()"
                        @input="getSuggestions"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    
                    <!-- Search Suggestions -->
                    <div v-if="suggestions.length > 0" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1">
                        <div
                            v-for="suggestion in suggestions"
                            :key="suggestion.text"
                            @click="selectSuggestion(suggestion)"
                            class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center"
                        >
                            <span class="text-xs text-gray-500 mr-2">{{ suggestion.type }}</span>
                            <span>{{ suggestion.text }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <input
                    v-model="form.location"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="City, State, or Remote"
                />
            </div>

            <div v-if="searchType !== 'courses'">
                <label class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                <select
                    v-model="form.course_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">All Courses</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">
                        {{ course.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Advanced Filters -->
        <div v-if="showAdvanced" class="border-t pt-6">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Advanced Filters</h4>
            
            <!-- Job-specific filters -->
            <div v-if="searchType === 'jobs'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Job Type</label>
                    <select
                        v-model="form.job_type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Any Type</option>
                        <option value="full_time">Full Time</option>
                        <option value="part_time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="internship">Internship</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Experience Level</label>
                    <select
                        v-model="form.experience_level"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Any Level</option>
                        <option value="entry">Entry Level</option>
                        <option value="junior">Junior</option>
                        <option value="mid">Mid Level</option>
                        <option value="senior">Senior</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Min Salary</label>
                    <input
                        v-model.number="form.salary_min"
                        type="number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="0"
                        min="0"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Salary</label>
                    <input
                        v-model.number="form.salary_max"
                        type="number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="No limit"
                        min="0"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Work Arrangement</label>
                    <select
                        v-model="form.work_arrangement"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Any Arrangement</option>
                        <option value="onsite">On-site</option>
                        <option value="remote">Remote</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>

                <div class="flex items-center">
                    <input
                        v-model="form.employer_verified"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <label class="ml-2 block text-sm text-gray-900">Verified Employers Only</label>
                </div>
            </div>

            <!-- Graduate-specific filters -->
            <div v-else-if="searchType === 'graduates'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Graduation Year</label>
                    <div class="flex space-x-2">
                        <input
                            v-model.number="form.graduation_year_from"
                            type="number"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="From"
                            :min="2000"
                            :max="new Date().getFullYear() + 5"
                        />
                        <input
                            v-model.number="form.graduation_year_to"
                            type="number"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="To"
                            :min="2000"
                            :max="new Date().getFullYear() + 5"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Employment Status</label>
                    <select
                        v-model="form.employment_status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Any Status</option>
                        <option value="employed">Employed</option>
                        <option value="unemployed">Unemployed</option>
                        <option value="self_employed">Self Employed</option>
                        <option value="student">Student</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Min GPA</label>
                    <input
                        v-model.number="form.min_gpa"
                        type="number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="0.0"
                        min="0"
                        max="4"
                        step="0.1"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Min Profile Completion</label>
                    <input
                        v-model.number="form.profile_completion_min"
                        type="number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="0"
                        min="0"
                        max="100"
                    />
                </div>
            </div>

            <!-- Course-specific filters -->
            <div v-else-if="searchType === 'courses'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Level</label>
                    <select
                        v-model="form.level"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Any Level</option>
                        <option value="certificate">Certificate</option>
                        <option value="diploma">Diploma</option>
                        <option value="degree">Degree</option>
                        <option value="postgraduate">Postgraduate</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Duration (months)</label>
                    <div class="flex space-x-2">
                        <input
                            v-model.number="form.duration_min"
                            type="number"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Min"
                            min="1"
                        />
                        <input
                            v-model.number="form.duration_max"
                            type="number"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Max"
                            min="1"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Min Employment Rate</label>
                    <input
                        v-model.number="form.min_employment_rate"
                        type="number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="0"
                        min="0"
                        max="100"
                    />
                </div>

                <div class="flex items-center">
                    <input
                        v-model="form.featured_only"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <label class="ml-2 block text-sm text-gray-900">Featured Courses Only</label>
                </div>
            </div>

            <!-- Skills Filter (common to all types) -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Skills</label>
                <SkillsInput
                    v-model="form.skills"
                    :placeholder="getSkillsPlaceholder()"
                />
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center">
            <button
                type="button"
                @click="showAdvanced = !showAdvanced"
                class="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
            >
                {{ showAdvanced ? 'Hide' : 'Show' }} Advanced Filters
            </button>

            <div class="flex space-x-3">
                <button
                    type="button"
                    @click="clearForm"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Clear
                </button>
                <button
                    type="button"
                    @click="$emit('save-search')"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Save Search
                </button>
                <button
                    type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
                >
                    Search
                </button>
            </div>
        </div>
    </form>
</template>

<script>
import SkillsInput from '@/Components/SkillsInput.vue'

export default {
    components: {
        SkillsInput,
    },

    props: {
        searchType: String,
        filters: Object,
        courses: Array,
    },

    emits: ['search', 'save-search'],

    data() {
        return {
            form: {
                keywords: '',
                location: '',
                course_id: '',
                skills: [],
                // Job-specific
                job_type: '',
                experience_level: '',
                salary_min: null,
                salary_max: null,
                work_arrangement: '',
                employer_verified: false,
                // Graduate-specific
                graduation_year_from: null,
                graduation_year_to: null,
                employment_status: '',
                min_gpa: null,
                profile_completion_min: null,
                // Course-specific
                level: '',
                duration_min: null,
                duration_max: null,
                min_employment_rate: null,
                featured_only: false,
            },
            showAdvanced: false,
            suggestions: [],
            suggestionTimeout: null,
        }
    },

    methods: {
        performSearch() {
            const criteria = { ...this.form }
            
            // Clean up empty values
            Object.keys(criteria).forEach(key => {
                if (criteria[key] === '' || criteria[key] === null || 
                    (Array.isArray(criteria[key]) && criteria[key].length === 0)) {
                    delete criteria[key]
                }
            })

            // Handle graduation year range
            if (criteria.graduation_year_from || criteria.graduation_year_to) {
                criteria.graduation_year = [
                    criteria.graduation_year_from || 2000,
                    criteria.graduation_year_to || new Date().getFullYear() + 5
                ]
                delete criteria.graduation_year_from
                delete criteria.graduation_year_to
            }

            this.$emit('search', criteria)
        },

        clearForm() {
            Object.keys(this.form).forEach(key => {
                if (Array.isArray(this.form[key])) {
                    this.form[key] = []
                } else if (typeof this.form[key] === 'boolean') {
                    this.form[key] = false
                } else {
                    this.form[key] = ''
                }
            })
        },

        async getSuggestions() {
            if (this.suggestionTimeout) {
                clearTimeout(this.suggestionTimeout)
            }

            this.suggestionTimeout = setTimeout(async () => {
                if (this.form.keywords && this.form.keywords.length >= 2) {
                    try {
                        const response = await axios.get('/api/search/suggestions', {
                            params: {
                                q: this.form.keywords,
                                type: this.searchType
                            }
                        })
                        this.suggestions = Object.values(response.data.suggestions).flat()
                    } catch (error) {
                        console.error('Failed to get suggestions:', error)
                    }
                } else {
                    this.suggestions = []
                }
            }, 300)
        },

        selectSuggestion(suggestion) {
            this.form.keywords = suggestion.text
            this.suggestions = []
        },

        getKeywordsPlaceholder() {
            const placeholders = {
                jobs: 'Job title, company, or keywords...',
                graduates: 'Graduate name or job title...',
                courses: 'Course name or description...',
            }
            return placeholders[this.searchType] || 'Search...'
        },

        getSkillsPlaceholder() {
            const placeholders = {
                jobs: 'Required skills (e.g., JavaScript, Project Management)',
                graduates: 'Skills possessed by graduates',
                courses: 'Skills gained from courses',
            }
            return placeholders[this.searchType] || 'Skills...'
        },
    },
}
</script>