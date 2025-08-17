<template>
    <AppLayout title="Achievements">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Achievements & Recognition
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Celebrate and showcase alumni accomplishments
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Link
                        :href="route('achievements.leaderboard')"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        View Leaderboard
                    </Link>
                    <button
                        @click="showCreateModal = true"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        Add Achievement
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Achievement Categories -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div
                        v-for="category in achievementCategories"
                        :key="category.id"
                        @click="selectedCategory = category.id"
                        :class="[
                            'bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 cursor-pointer transition-all duration-200',
                            selectedCategory === category.id 
                                ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' 
                                : 'hover:shadow-lg'
                        ]"
                    >
                        <div class="flex items-center space-x-3">
                            <div :class="[
                                'w-12 h-12 rounded-lg flex items-center justify-center',
                                category.color
                            ]">
                                <component :is="category.icon" class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ category.name }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ category.count }} achievements
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search Achievements
                            </label>
                            <div class="relative">
                                <input
                                    id="search"
                                    v-model="searchQuery"
                                    type="text"
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Search by name, achievement, or company..."
                                />
                                <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                            </div>
                        </div>

                        <!-- Year Filter -->
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Year
                            </label>
                            <select
                                id="year"
                                v-model="selectedYear"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">All Years</option>
                                <option v-for="year in availableYears" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <!-- Sort -->
                        <div>
                            <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sort By
                            </label>
                            <select
                                id="sort"
                                v-model="sortBy"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="recent">Most Recent</option>
                                <option value="popular">Most Popular</option>
                                <option value="alphabetical">Alphabetical</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Recent Achievements Highlight -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-6 mb-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold mb-2">🎉 Recent Achievements</h3>
                            <p class="text-blue-100">Celebrating our latest alumni successes</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold">{{ recentAchievements.length }}</div>
                            <div class="text-blue-100">This Month</div>
                        </div>
                    </div>
                </div>

                <!-- Achievements Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <AchievementCard
                        v-for="achievement in filteredAchievements"
                        :key="achievement.id"
                        :achievement="achievement"
                        @view-details="handleViewDetails"
                        @celebrate="handleCelebrate"
                        @share="handleShare"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="filteredAchievements.length === 0" class="text-center py-12">
                    <TrophyIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No achievements found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Try adjusting your search criteria or be the first to add an achievement!
                    </p>
                </div>

                <!-- Load More -->
                <div v-if="hasMoreAchievements" class="text-center mt-8">
                    <button
                        @click="loadMoreAchievements"
                        :disabled="loadingMore"
                        class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                    >
                        {{ loadingMore ? 'Loading...' : 'Load More Achievements' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Achievement Modal -->
        <CreateAchievementModal
            :show="showCreateModal"
            @close="showCreateModal = false"
            @created="handleAchievementCreated"
        />

        <!-- Achievement Celebration -->
        <AchievementCelebration
            v-if="celebratingAchievement"
            :achievement="celebratingAchievement"
            @close="celebratingAchievement = null"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AchievementCard from '@/components/Achievements/AchievementCard.vue'
import CreateAchievementModal from '@/components/Achievements/CreateAchievementModal.vue'
import AchievementCelebration from '@/components/AchievementCelebration.vue'
import {
    MagnifyingGlassIcon,
    TrophyIcon,
    BriefcaseIcon,
    AcademicCapIcon,
    StarIcon,
    RocketLaunchIcon
} from '@heroicons/vue/24/outline'

const searchQuery = ref('')
const selectedCategory = ref('')
const selectedYear = ref('')
const sortBy = ref('recent')
const showCreateModal = ref(false)
const loadingMore = ref(false)
const hasMoreAchievements = ref(true)
const celebratingAchievement = ref(null)

const achievementCategories = ref([
    {
        id: 'career',
        name: 'Career',
        icon: BriefcaseIcon,
        color: 'bg-blue-500',
        count: 45
    },
    {
        id: 'education',
        name: 'Education',
        icon: AcademicCapIcon,
        color: 'bg-green-500',
        count: 32
    },
    {
        id: 'awards',
        name: 'Awards',
        icon: TrophyIcon,
        color: 'bg-yellow-500',
        count: 28
    },
    {
        id: 'innovation',
        name: 'Innovation',
        icon: RocketLaunchIcon,
        color: 'bg-purple-500',
        count: 19
    }
])

const achievements = ref([
    {
        id: 1,
        title: 'Promoted to Senior Engineer',
        description: 'Advanced to senior engineering role at leading tech company',
        user_name: 'Sarah Johnson',
        user_avatar: null,
        category: 'career',
        company: 'TechCorp Inc.',
        date: '2024-01-15',
        likes_count: 24,
        comments_count: 8,
        is_liked: false,
        tags: ['promotion', 'engineering', 'tech']
    },
    {
        id: 2,
        title: 'PhD in Computer Science',
        description: 'Completed doctoral studies with research in AI and machine learning',
        user_name: 'Michael Chen',
        user_avatar: null,
        category: 'education',
        company: 'Stanford University',
        date: '2024-01-10',
        likes_count: 42,
        comments_count: 15,
        is_liked: true,
        tags: ['phd', 'computer science', 'ai']
    }
])

const recentAchievements = computed(() => {
    const oneMonthAgo = new Date()
    oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1)
    return achievements.value.filter(achievement => 
        new Date(achievement.date) > oneMonthAgo
    )
})

const availableYears = computed(() => {
    const years = [...new Set(achievements.value.map(a => new Date(a.date).getFullYear()))]
    return years.sort((a, b) => b - a)
})

const filteredAchievements = computed(() => {
    let filtered = achievements.value

    if (selectedCategory.value) {
        filtered = filtered.filter(achievement => achievement.category === selectedCategory.value)
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(achievement =>
            achievement.title.toLowerCase().includes(query) ||
            achievement.description.toLowerCase().includes(query) ||
            achievement.user_name.toLowerCase().includes(query) ||
            achievement.company.toLowerCase().includes(query)
        )
    }

    if (selectedYear.value) {
        filtered = filtered.filter(achievement => 
            new Date(achievement.date).getFullYear() === parseInt(selectedYear.value)
        )
    }

    // Sort
    if (sortBy.value === 'recent') {
        filtered.sort((a, b) => new Date(b.date) - new Date(a.date))
    } else if (sortBy.value === 'popular') {
        filtered.sort((a, b) => b.likes_count - a.likes_count)
    } else if (sortBy.value === 'alphabetical') {
        filtered.sort((a, b) => a.user_name.localeCompare(b.user_name))
    }

    return filtered
})

const handleViewDetails = (achievementId) => {
    window.location.href = `/achievements/${achievementId}`
}

const handleCelebrate = (achievement) => {
    celebratingAchievement.value = achievement
    // Also increment likes
    achievement.likes_count++
    achievement.is_liked = true
}

const handleShare = (achievement) => {
    if (navigator.share) {
        navigator.share({
            title: achievement.title,
            text: `${achievement.user_name} achieved: ${achievement.title}`,
            url: window.location.origin + `/achievements/${achievement.id}`
        })
    }
}

const loadMoreAchievements = () => {
    loadingMore.value = true
    setTimeout(() => {
        loadingMore.value = false
        hasMoreAchievements.value = false
    }, 1000)
}

const handleAchievementCreated = (achievement) => {
    achievements.value.unshift(achievement)
    showCreateModal.value = false
}

onMounted(() => {
    // Load initial data
})
</script>
