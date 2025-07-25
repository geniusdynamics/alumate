<template>
    <AppLayout title="Advanced Search">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Search Type Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <button
                                v-for="type in searchTypes"
                                :key="type.value"
                                @click="switchSearchType(type.value)"
                                :class="[
                                    'py-4 px-1 border-b-2 font-medium text-sm',
                                    currentSearchType === type.value
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                ]"
                            >
                                {{ type.label }}
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Search Form -->
                        <div class="mb-8">
                            <SearchForm
                                :search-type="currentSearchType"
                                :filters="filters"
                                :courses="courses"
                                @search="performSearch"
                                @save-search="showSaveSearchModal"
                            />
                        </div>

                        <!-- Saved Searches -->
                        <div v-if="savedSearches.length > 0" class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Saved Searches</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <SavedSearchCard
                                    v-for="search in savedSearches"
                                    :key="search.id"
                                    :search="search"
                                    @execute="executeSavedSearch"
                                    @edit="editSavedSearch"
                                    @delete="deleteSavedSearch"
                                    @toggle-alert="toggleSearchAlert"
                                />
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div v-if="searchResults">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Search Results ({{ searchResults.total }} found)
                                </h3>
                                <div class="flex space-x-4">
                                    <SortDropdown
                                        :options="sortOptions"
                                        :current="currentSort"
                                        @change="updateSort"
                                    />
                                    <ViewToggle
                                        :current="viewMode"
                                        @change="viewMode = $event"
                                    />
                                </div>
                            </div>

                            <!-- Results Grid/List -->
                            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <JobCard
                                    v-if="currentSearchType === 'jobs'"
                                    v-for="job in searchResults.data"
                                    :key="job.id"
                                    :job="job"
                                    :show-match-score="!!job.match_score"
                                />
                                <GraduateCard
                                    v-else-if="currentSearchType === 'graduates'"
                                    v-for="graduate in searchResults.data"
                                    :key="graduate.id"
                                    :graduate="graduate"
                                    :show-match-score="!!graduate.match_score"
                                />
                                <CourseCard
                                    v-else-if="currentSearchType === 'courses'"
                                    v-for="course in searchResults.data"
                                    :key="course.id"
                                    :course="course"
                                />
                            </div>

                            <div v-else class="space-y-4">
                                <JobListItem
                                    v-if="currentSearchType === 'jobs'"
                                    v-for="job in searchResults.data"
                                    :key="job.id"
                                    :job="job"
                                    :show-match-score="!!job.match_score"
                                />
                                <GraduateListItem
                                    v-else-if="currentSearchType === 'graduates'"
                                    v-for="graduate in searchResults.data"
                                    :key="graduate.id"
                                    :graduate="graduate"
                                    :show-match-score="!!graduate.match_score"
                                />
                                <CourseListItem
                                    v-else-if="currentSearchType === 'courses'"
                                    v-for="course in searchResults.data"
                                    :key="course.id"
                                    :course="course"
                                />
                            </div>

                            <!-- Pagination -->
                            <div v-if="searchResults.last_page > 1" class="mt-8">
                                <Pagination
                                    :links="searchResults.links"
                                    @navigate="navigateToPage"
                                />
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="hasSearched" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No results found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
                        </div>

                        <!-- Recommendations -->
                        <div v-if="recommendations.length > 0" class="mt-12">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Recommended for You</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <JobCard
                                    v-for="job in recommendations"
                                    :key="job.id"
                                    :job="job"
                                    :show-match-score="true"
                                    :is-recommendation="true"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Search Modal -->
        <SaveSearchModal
            v-if="showSaveModal"
            :search-type="currentSearchType"
            :search-criteria="currentSearchCriteria"
            @close="showSaveModal = false"
            @save="saveSearch"
        />
    </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchForm from './Components/SearchForm.vue'
import SavedSearchCard from './Components/SavedSearchCard.vue'
import JobCard from './Components/JobCard.vue'
import GraduateCard from './Components/GraduateCard.vue'
import CourseCard from './Components/CourseCard.vue'
import JobListItem from './Components/JobListItem.vue'
import GraduateListItem from './Components/GraduateListItem.vue'
import CourseListItem from './Components/CourseListItem.vue'
import SaveSearchModal from './Components/SaveSearchModal.vue'
import SortDropdown from './Components/SortDropdown.vue'
import ViewToggle from './Components/ViewToggle.vue'
import Pagination from '@/Components/Pagination.vue'

export default {
    components: {
        AppLayout,
        SearchForm,
        SavedSearchCard,
        JobCard,
        GraduateCard,
        CourseCard,
        JobListItem,
        GraduateListItem,
        CourseListItem,
        SaveSearchModal,
        SortDropdown,
        ViewToggle,
        Pagination,
    },

    props: {
        searchType: String,
        savedSearches: Array,
        courses: Array,
        filters: Object,
    },

    data() {
        return {
            currentSearchType: this.searchType,
            searchResults: null,
            recommendations: [],
            currentSearchCriteria: {},
            hasSearched: false,
            showSaveModal: false,
            viewMode: 'grid',
            currentSort: { field: 'created_at', order: 'desc' },
            searchTypes: [
                { value: 'jobs', label: 'Jobs' },
                { value: 'graduates', label: 'Graduates' },
                { value: 'courses', label: 'Courses' },
            ],
        }
    },

    computed: {
        sortOptions() {
            const options = {
                jobs: [
                    { value: 'created_at', label: 'Date Posted' },
                    { value: 'salary', label: 'Salary' },
                    { value: 'deadline', label: 'Application Deadline' },
                    { value: 'applications', label: 'Applications' },
                    { value: 'relevance', label: 'Relevance' },
                ],
                graduates: [
                    { value: 'profile_completion_percentage', label: 'Profile Completion' },
                    { value: 'graduation_year', label: 'Graduation Year' },
                    { value: 'gpa', label: 'GPA' },
                    { value: 'name', label: 'Name' },
                ],
                courses: [
                    { value: 'employment_rate', label: 'Employment Rate' },
                    { value: 'name', label: 'Name' },
                    { value: 'duration_months', label: 'Duration' },
                    { value: 'total_graduated', label: 'Graduates' },
                ],
            }
            return options[this.currentSearchType] || []
        },
    },

    mounted() {
        this.loadRecommendations()
    },

    methods: {
        switchSearchType(type) {
            this.currentSearchType = type
            this.searchResults = null
            this.hasSearched = false
            this.loadRecommendations()
        },

        async performSearch(criteria) {
            this.currentSearchCriteria = criteria
            this.hasSearched = true

            try {
                const endpoint = {
                    jobs: '/api/search/jobs',
                    graduates: '/api/search/graduates',
                    courses: '/api/search/courses',
                }[this.currentSearchType]

                const response = await axios.get(endpoint, { params: criteria })
                this.searchResults = response.data.results
            } catch (error) {
                console.error('Search error:', error)
                this.$toast.error('Search failed. Please try again.')
            }
        },

        async loadRecommendations() {
            if (this.currentSearchType === 'jobs' && this.$page.props.auth.user?.roles?.includes('graduate')) {
                try {
                    const response = await axios.get('/api/search/recommendations', {
                        params: { type: 'jobs', limit: 6 }
                    })
                    this.recommendations = response.data.recommendations
                } catch (error) {
                    console.error('Failed to load recommendations:', error)
                }
            }
        },

        showSaveSearchModal() {
            if (!this.currentSearchCriteria || Object.keys(this.currentSearchCriteria).length === 0) {
                this.$toast.error('Please perform a search first.')
                return
            }
            this.showSaveModal = true
        },

        async saveSearch(data) {
            try {
                await axios.post('/api/search/save', {
                    ...data,
                    search_type: this.currentSearchType,
                    search_criteria: this.currentSearchCriteria,
                })
                this.$toast.success('Search saved successfully!')
                this.showSaveModal = false
                // Reload saved searches
                this.loadSavedSearches()
            } catch (error) {
                console.error('Save search error:', error)
                this.$toast.error('Failed to save search.')
            }
        },

        async executeSavedSearch(search) {
            this.currentSearchCriteria = search.search_criteria
            await this.performSearch(search.search_criteria)
        },

        async editSavedSearch(search) {
            // Implementation for editing saved search
            console.log('Edit search:', search)
        },

        async deleteSavedSearch(search) {
            if (!confirm('Are you sure you want to delete this saved search?')) {
                return
            }

            try {
                await axios.delete(`/api/search/saved/${search.id}`)
                this.$toast.success('Search deleted successfully!')
                this.loadSavedSearches()
            } catch (error) {
                console.error('Delete search error:', error)
                this.$toast.error('Failed to delete search.')
            }
        },

        async toggleSearchAlert(search) {
            try {
                await axios.patch(`/api/search/saved/${search.id}`, {
                    alert_enabled: !search.alert_enabled
                })
                this.$toast.success('Alert settings updated!')
                this.loadSavedSearches()
            } catch (error) {
                console.error('Toggle alert error:', error)
                this.$toast.error('Failed to update alert settings.')
            }
        },

        updateSort(sort) {
            this.currentSort = sort
            if (this.hasSearched) {
                this.performSearch({
                    ...this.currentSearchCriteria,
                    sort_by: sort.field,
                    sort_order: sort.order,
                })
            }
        },

        navigateToPage(url) {
            // Extract page number from URL and perform search
            const urlParams = new URLSearchParams(url.split('?')[1])
            const page = urlParams.get('page')
            
            this.performSearch({
                ...this.currentSearchCriteria,
                page: page,
            })
        },

        async loadSavedSearches() {
            try {
                const response = await axios.get('/api/search/saved', {
                    params: { type: this.currentSearchType }
                })
                this.savedSearches = response.data.saved_searches
            } catch (error) {
                console.error('Failed to load saved searches:', error)
            }
        },
    },
}
</script>