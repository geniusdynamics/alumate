<template>
    <AppLayout title="Scholarships">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Scholarships
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Discover and apply for scholarships to support your education
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button
                        @click="showCreateModal = true"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        Create Scholarship
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search and Filters -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search -->
                            <div class="md:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Search Scholarships
                                </label>
                                <div class="relative">
                                    <input
                                        id="search"
                                        v-model="searchQuery"
                                        type="text"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Search by name, field of study, or institution..."
                                    />
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Field Filter -->
                            <div>
                                <label for="field" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Field of Study
                                </label>
                                <select
                                    id="field"
                                    v-model="selectedField"
                                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">All Fields</option>
                                    <option value="engineering">Engineering</option>
                                    <option value="business">Business</option>
                                    <option value="medicine">Medicine</option>
                                    <option value="arts">Arts</option>
                                    <option value="sciences">Sciences</option>
                                </select>
                            </div>

                            <!-- Amount Filter -->
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Amount Range
                                </label>
                                <select
                                    id="amount"
                                    v-model="selectedAmount"
                                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Any Amount</option>
                                    <option value="0-1000">$0 - $1,000</option>
                                    <option value="1000-5000">$1,000 - $5,000</option>
                                    <option value="5000-10000">$5,000 - $10,000</option>
                                    <option value="10000+">$10,000+</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <AcademicCapIcon class="h-6 w-6 text-blue-600" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                            Available Scholarships
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ scholarshipStats.total }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <CurrencyDollarIcon class="h-6 w-6 text-green-600" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                            Total Value
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            ${{ scholarshipStats.totalValue.toLocaleString() }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <UserGroupIcon class="h-6 w-6 text-purple-600" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                            Recipients
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ scholarshipStats.recipients }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ClockIcon class="h-6 w-6 text-orange-600" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                            Deadline Soon
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ scholarshipStats.deadlineSoon }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scholarships Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <ScholarshipCard
                        v-for="scholarship in filteredScholarships"
                        :key="scholarship.id"
                        :scholarship="scholarship"
                        @apply="handleApply"
                        @view-details="handleViewDetails"
                        @save="handleSave"
                        @share="handleShare"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="filteredScholarships.length === 0" class="text-center py-12">
                    <AcademicCapIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No scholarships found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Try adjusting your search criteria or check back later for new opportunities.
                    </p>
                </div>

                <!-- Load More -->
                <div v-if="hasMoreScholarships" class="text-center mt-8">
                    <button
                        @click="loadMoreScholarships"
                        :disabled="loadingMore"
                        class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                    >
                        {{ loadingMore ? 'Loading...' : 'Load More Scholarships' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Scholarship Modal -->
        <CreateScholarshipModal
            :show="showCreateModal"
            @close="showCreateModal = false"
            @created="handleScholarshipCreated"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ScholarshipCard from '@/components/Scholarships/ScholarshipCard.vue'
import CreateScholarshipModal from '@/components/Scholarships/CreateScholarshipModal.vue'
import {
    MagnifyingGlassIcon,
    AcademicCapIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

const searchQuery = ref('')
const selectedField = ref('')
const selectedAmount = ref('')
const showCreateModal = ref(false)
const loadingMore = ref(false)
const hasMoreScholarships = ref(true)

// Mock data - replace with actual API calls
const scholarships = ref([
    {
        id: 1,
        title: 'Engineering Excellence Scholarship',
        description: 'Supporting outstanding engineering students in their academic journey.',
        amount: 5000,
        field: 'engineering',
        institution: 'Tech University',
        deadline: '2024-03-15',
        requirements: ['3.5+ GPA', 'Engineering major', 'Financial need'],
        recipients_count: 25,
        is_saved: false,
        status: 'open'
    },
    {
        id: 2,
        title: 'Business Leadership Award',
        description: 'Recognizing future business leaders with academic excellence.',
        amount: 3000,
        field: 'business',
        institution: 'Business School',
        deadline: '2024-04-01',
        requirements: ['Business major', 'Leadership experience', '3.0+ GPA'],
        recipients_count: 15,
        is_saved: true,
        status: 'open'
    }
])

const scholarshipStats = ref({
    total: 156,
    totalValue: 2450000,
    recipients: 1250,
    deadlineSoon: 23
})

const filteredScholarships = computed(() => {
    let filtered = scholarships.value

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(scholarship =>
            scholarship.title.toLowerCase().includes(query) ||
            scholarship.description.toLowerCase().includes(query) ||
            scholarship.institution.toLowerCase().includes(query)
        )
    }

    if (selectedField.value) {
        filtered = filtered.filter(scholarship => scholarship.field === selectedField.value)
    }

    if (selectedAmount.value) {
        const [min, max] = selectedAmount.value.split('-').map(v => v.replace('+', ''))
        filtered = filtered.filter(scholarship => {
            if (max) {
                return scholarship.amount >= parseInt(min) && scholarship.amount <= parseInt(max)
            } else {
                return scholarship.amount >= parseInt(min)
            }
        })
    }

    return filtered
})

const handleApply = (scholarshipId) => {
    // Navigate to application page
    window.location.href = `/scholarships/${scholarshipId}/apply`
}

const handleViewDetails = (scholarshipId) => {
    // Navigate to scholarship details
    window.location.href = `/scholarships/${scholarshipId}`
}

const handleSave = (scholarshipId) => {
    // Toggle save status
    const scholarship = scholarships.value.find(s => s.id === scholarshipId)
    if (scholarship) {
        scholarship.is_saved = !scholarship.is_saved
    }
}

const handleShare = (scholarshipId) => {
    // Share scholarship
    const scholarship = scholarships.value.find(s => s.id === scholarshipId)
    if (scholarship && navigator.share) {
        navigator.share({
            title: scholarship.title,
            text: scholarship.description,
            url: window.location.origin + `/scholarships/${scholarshipId}`
        })
    }
}

const loadMoreScholarships = () => {
    loadingMore.value = true
    // Simulate API call
    setTimeout(() => {
        loadingMore.value = false
        hasMoreScholarships.value = false
    }, 1000)
}

const handleScholarshipCreated = (scholarship) => {
    scholarships.value.unshift(scholarship)
    showCreateModal.value = false
}

onMounted(() => {
    // Load initial data
})
</script>
