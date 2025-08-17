<template>
    <div class="card-mobile bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">People You May Know</h3>
            <UsersIcon class="h-6 w-6 text-green-600 dark:text-green-400" />
        </div>
        
        <div class="space-y-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        </div>
                        <div class="h-8 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                </div>
            </div>
            
            <!-- Suggestions -->
            <div v-else-if="suggestions.length > 0" class="space-y-3">
                <div 
                    v-for="suggestion in suggestions" 
                    :key="suggestion.id"
                    class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                    <div class="flex-shrink-0">
                        <img 
                            v-if="suggestion.avatar_url"
                            :src="suggestion.avatar_url" 
                            :alt="suggestion.name"
                            class="h-10 w-10 rounded-full object-cover"
                        />
                        <div 
                            v-else
                            class="h-10 w-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-white font-medium text-sm">
                                {{ getInitials(suggestion.name) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ suggestion.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ suggestion.title }} at {{ suggestion.company }}
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            {{ suggestion.connection_reason }}
                        </p>
                    </div>
                    
                    <div class="flex-shrink-0">
                        <button
                            @click="sendConnectionRequest(suggestion)"
                            :disabled="suggestion.connecting"
                            class="btn-mobile-sm bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <UserPlusIcon v-if="!suggestion.connecting" class="h-4 w-4" />
                            <div v-else class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-else class="text-center py-6">
                <UsersIcon class="h-12 w-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No suggestions available</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Complete your profile to get better suggestions
                </p>
            </div>
        </div>
        
        <!-- View All Link -->
        <div v-if="suggestions.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <Link 
                :href="route('alumni.directory')"
                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium flex items-center justify-center space-x-1"
            >
                <span>Browse Alumni Directory</span>
                <ArrowRightIcon class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
    UsersIcon,
    UserPlusIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'

const loading = ref(true)
const suggestions = ref([])

const props = defineProps({
    limit: {
        type: Number,
        default: 3
    }
})

onMounted(async () => {
    await fetchSuggestions()
})

const fetchSuggestions = async () => {
    try {
        loading.value = true
        const response = await fetch(`/api/dashboard/alumni-suggestions?limit=${props.limit}`)
        const data = await response.json()
        suggestions.value = data.suggestions || []
    } catch (error) {
        console.error('Failed to fetch alumni suggestions:', error)
        suggestions.value = []
    } finally {
        loading.value = false
    }
}

const getInitials = (name) => {
    if (!name) return 'U'
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const sendConnectionRequest = async (suggestion) => {
    try {
        suggestion.connecting = true
        
        const response = await fetch('/api/connections/request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                user_id: suggestion.id,
                message: `Hi ${suggestion.name.split(' ')[0]}, I'd like to connect with you on our alumni platform!`
            })
        })
        
        if (response.ok) {
            // Remove from suggestions after successful request
            suggestions.value = suggestions.value.filter(s => s.id !== suggestion.id)
        } else {
            throw new Error('Failed to send connection request')
        }
    } catch (error) {
        console.error('Failed to send connection request:', error)
        alert('Failed to send connection request. Please try again.')
    } finally {
        suggestion.connecting = false
    }
}
</script>