<template>
  <div class="job-result">
    <div class="job-header">
      <div class="job-info">
        <h3 class="job-title">
          <span v-html="highlightText(job.title, highlight.title)"></span>
        </h3>
        <div class="job-meta">
          <span class="company-name">{{ job.company }}</span>
          <span v-if="job.location" class="job-location">
            <MapPinIcon class="w-4 h-4" />
            {{ job.location }}
          </span>
          <span v-if="job.employment_type" class="employment-type">
            {{ formatEmploymentType(job.employment_type) }}
          </span>
        </div>
      </div>
      
      <div class="job-actions-header">
        <div class="relevance-score" :title="`Relevance score: ${score.toFixed(2)}`">
          <div class="score-bar">
            <div 
              class="score-fill" 
              :style="{ width: `${Math.min(score * 10, 100)}%` }"
            ></div>
          </div>
          <span class="score-text">{{ (score * 10).toFixed(0) }}%</span>
        </div>
        
        <div v-if="job.posted_date" class="posted-date">
          Posted {{ formatDate(job.posted_date) }}
        </div>
      </div>
    </div>
    
    <div class="job-description">
      <p v-html="highlightText(truncateDescription(job.description), highlight.description)"></p>
      <button
        v-if="job.description && job.description.length > 200"
        @click="showFullDescription = !showFullDescription"
        class="show-more-btn"
      >
        {{ showFullDescription ? 'Show Less' : 'Show More' }}
      </button>
    </div>
    
    <div class="job-details">
      <div v-if="job.salary_range" class="salary-info">
        <CurrencyDollarIcon class="w-4 h-4" />
        <span>{{ job.salary_range }}</span>
      </div>
      
      <div v-if="job.experience_level" class="experience-info">
        <BriefcaseIcon class="w-4 h-4" />
        <span>{{ formatExperienceLevel(job.experience_level) }}</span>
      </div>
      
      <div v-if="job.remote_option" class="remote-info">
        <ComputerDesktopIcon class="w-4 h-4" />
        <span>{{ formatRemoteOption(job.remote_option) }}</span>
      </div>
    </div>
    
    <div v-if="job.skills_required && job.skills_required.length > 0" class="required-skills">
      <span class="skills-label">Required Skills:</span>
      <div class="skills-list">
        <span
          v-for="skill in job.skills_required"
          :key="skill"
          class="skill-tag"
          v-html="highlightText(skill, highlight.skills_required)"
        ></span>
      </div>
    </div>
    
    <div v-if="networkConnections.length > 0" class="network-connections">
      <div class="connections-header">
        <UsersIcon class="w-4 h-4" />
        <span class="connections-title">Alumni at {{ job.company }}</span>
      </div>
      <div class="connections-list">
        <div
          v-for="connection in networkConnections.slice(0, 3)"
          :key="connection.id"
          class="connection-item"
        >
          <img
            v-if="connection.avatar_url"
            :src="connection.avatar_url"
            :alt="`${connection.name}'s avatar`"
            class="connection-avatar"
          />
          <div v-else class="connection-avatar-placeholder">
            {{ getInitials(connection.name) }}
          </div>
          <div class="connection-info">
            <span class="connection-name">{{ connection.name }}</span>
            <span class="connection-position">{{ connection.position }}</span>
          </div>
        </div>
        <div v-if="networkConnections.length > 3" class="more-connections">
          +{{ networkConnections.length - 3 }} more alumni
        </div>
      </div>
    </div>
    
    <div class="job-actions">
      <button
        @click="viewJob"
        class="action-btn primary"
      >
        <EyeIcon class="w-4 h-4" />
        View Details
      </button>
      
      <button
        @click="applyToJob"
        class="action-btn primary"
        :disabled="isApplying"
      >
        <PaperAirplaneIcon v-if="!isApplying" class="w-4 h-4" />
        <LoadingSpinner v-else class="w-4 h-4" />
        Apply Now
      </button>
      
      <button
        v-if="networkConnections.length > 0"
        @click="requestIntroduction"
        class="action-btn secondary"
        :disabled="isRequestingIntro"
      >
        <UserPlusIcon v-if="!isRequestingIntro" class="w-4 h-4" />
        <LoadingSpinner v-else class="w-4 h-4" />
        Request Introduction
      </button>
      
      <button
        @click="saveJob"
        class="action-btn secondary"
        :class="{ saved: isSaved }"
        :disabled="isSaving"
      >
        <BookmarkIcon v-if="!isSaving" class="w-4 h-4" />
        <LoadingSpinner v-else class="w-4 h-4" />
        {{ isSaved ? 'Saved' : 'Save' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import {
  MapPinIcon,
  CurrencyDollarIcon,
  BriefcaseIcon,
  ComputerDesktopIcon,
  UsersIcon,
  EyeIcon,
  PaperAirplaneIcon,
  UserPlusIcon,
  BookmarkIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from '../LoadingSpinner.vue'

interface Job {
  id: number
  title: string
  company: string
  location?: string
  description: string
  employment_type?: string
  salary_range?: string
  experience_level?: string
  remote_option?: string
  skills_required?: string[]
  posted_date: string
  application_deadline?: string
}

interface NetworkConnection {
  id: number
  name: string
  position: string
  avatar_url?: string
}

interface Highlight {
  title?: string[]
  description?: string[]
  skills_required?: string[]
  [key: string]: string[] | undefined
}

const props = defineProps<{
  job: Job
  highlight: Highlight
  score: number
}>()

const emit = defineEmits<{
  'job-viewed': [jobId: number]
  'job-applied': [jobId: number]
  'introduction-requested': [jobId: number]
  'job-saved': [jobId: number]
}>()

// Reactive state
const showFullDescription = ref(false)
const isApplying = ref(false)
const isRequestingIntro = ref(false)
const isSaving = ref(false)
const isSaved = ref(false) // This would be determined from user's saved jobs

// Mock network connections (this would come from props or API)
const networkConnections = ref<NetworkConnection[]>([
  {
    id: 1,
    name: 'John Smith',
    position: 'Senior Developer',
    avatar_url: undefined
  },
  {
    id: 2,
    name: 'Sarah Johnson',
    position: 'Product Manager'
  }
])

// Toast composable
const { showToast } = useToast()

// Computed properties
const truncateDescription = computed(() => {
  if (showFullDescription.value || !props.job.description) {
    return props.job.description
  }
  
  return props.job.description.length > 200 
    ? props.job.description.substring(0, 200) + '...'
    : props.job.description
})

// Methods
const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const highlightText = (text: string, highlights?: string[]): string => {
  if (!highlights || highlights.length === 0) {
    return text
  }
  
  // Join all highlight fragments and return as HTML
  return highlights.join('...')
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24))
  
  if (diffInDays === 0) {
    return 'today'
  } else if (diffInDays === 1) {
    return 'yesterday'
  } else if (diffInDays < 7) {
    return `${diffInDays} days ago`
  } else {
    return date.toLocaleDateString()
  }
}

const formatEmploymentType = (type: string): string => {
  const typeMap: Record<string, string> = {
    'full_time': 'Full-time',
    'part_time': 'Part-time',
    'contract': 'Contract',
    'internship': 'Internship',
    'temporary': 'Temporary'
  }
  
  return typeMap[type] || type
}

const formatExperienceLevel = (level: string): string => {
  const levelMap: Record<string, string> = {
    'entry': 'Entry Level',
    'mid': 'Mid Level',
    'senior': 'Senior Level',
    'lead': 'Lead/Principal',
    'executive': 'Executive'
  }
  
  return levelMap[level] || level
}

const formatRemoteOption = (option: string): string => {
  const optionMap: Record<string, string> = {
    'remote': 'Remote',
    'hybrid': 'Hybrid',
    'onsite': 'On-site'
  }
  
  return optionMap[option] || option
}

const viewJob = () => {
  router.visit(`/jobs/${props.job.id}`)
  emit('job-viewed', props.job.id)
}

const applyToJob = async () => {
  isApplying.value = true
  
  try {
    const response = await fetch(`/api/jobs/${props.job.id}/apply`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      showToast('Application submitted successfully!', 'success')
      emit('job-applied', props.job.id)
    } else {
      throw new Error('Failed to apply to job')
    }
  } catch (error) {
    console.error('Failed to apply to job:', error)
    showToast('Failed to submit application. Please try again.', 'error')
  } finally {
    isApplying.value = false
  }
}

const requestIntroduction = async () => {
  isRequestingIntro.value = true
  
  try {
    const response = await fetch(`/api/jobs/${props.job.id}/request-introduction`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      showToast('Introduction request sent!', 'success')
      emit('introduction-requested', props.job.id)
    } else {
      throw new Error('Failed to request introduction')
    }
  } catch (error) {
    console.error('Failed to request introduction:', error)
    showToast('Failed to request introduction. Please try again.', 'error')
  } finally {
    isRequestingIntro.value = false
  }
}

const saveJob = async () => {
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/jobs/${props.job.id}/save`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      const data = await response.json()
      isSaved.value = data.saved
      
      const action = data.saved ? 'saved' : 'unsaved'
      showToast(`Job ${action} successfully!`, 'success')
      emit('job-saved', props.job.id)
    } else {
      throw new Error('Failed to save job')
    }
  } catch (error) {
    console.error('Failed to save job:', error)
    showToast('Failed to save job. Please try again.', 'error')
  } finally {
    isSaving.value = false
  }
}
</script>

<style scoped>
.job-result {
  @apply space-y-4;
}

.job-header {
  @apply flex items-start justify-between;
}

.job-info {
  @apply flex-1 space-y-2;
}

.job-title {
  @apply text-lg font-semibold text-gray-900;
}

.job-meta {
  @apply flex items-center space-x-3 text-sm text-gray-600;
}

.company-name {
  @apply font-medium text-blue-600;
}

.job-location {
  @apply flex items-center space-x-1;
}

.employment-type {
  @apply px-2 py-1 bg-green-100 text-green-800 rounded text-xs;
}

.job-actions-header {
  @apply space-y-2 text-right;
}

.relevance-score {
  @apply space-y-1;
}

.score-bar {
  @apply w-16 h-2 bg-gray-200 rounded-full overflow-hidden;
}

.score-fill {
  @apply h-full bg-green-500 transition-all duration-300;
}

.score-text {
  @apply text-xs text-gray-500;
}

.posted-date {
  @apply text-xs text-gray-500;
}

.job-description {
  @apply space-y-2;
}

.job-description p {
  @apply text-gray-700 leading-relaxed;
}

.show-more-btn {
  @apply text-sm text-blue-600 hover:text-blue-800;
}

.job-details {
  @apply flex items-center space-x-4 text-sm text-gray-600;
}

.salary-info,
.experience-info,
.remote-info {
  @apply flex items-center space-x-1;
}

.required-skills {
  @apply space-y-2;
}

.skills-label {
  @apply text-sm font-medium text-gray-700;
}

.skills-list {
  @apply flex flex-wrap gap-1;
}

.skill-tag {
  @apply inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded;
}

.network-connections {
  @apply bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3;
}

.connections-header {
  @apply flex items-center space-x-2 text-blue-700 font-medium;
}

.connections-list {
  @apply space-y-2;
}

.connection-item {
  @apply flex items-center space-x-3;
}

.connection-avatar {
  @apply w-8 h-8 rounded-full object-cover;
}

.connection-avatar-placeholder {
  @apply w-8 h-8 rounded-full bg-blue-200 flex items-center justify-center;
  @apply text-blue-700 font-medium text-xs;
}

.connection-info {
  @apply space-y-0;
}

.connection-name {
  @apply block text-sm font-medium text-gray-900;
}

.connection-position {
  @apply block text-xs text-gray-600;
}

.more-connections {
  @apply text-sm text-blue-600 font-medium;
}

.job-actions {
  @apply flex items-center space-x-2 pt-3 border-t border-gray-200;
}

.action-btn {
  @apply flex items-center space-x-1 px-3 py-2 rounded-md text-sm;
  @apply transition-colors;
}

.action-btn.primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.action-btn.secondary {
  @apply border border-gray-300 text-gray-700 hover:bg-gray-50;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.action-btn.secondary.saved {
  @apply border-green-300 text-green-600 bg-green-50;
}

/* Highlight styles */
:deep(mark) {
  @apply bg-yellow-200 text-yellow-900 px-1 rounded;
}
</style>