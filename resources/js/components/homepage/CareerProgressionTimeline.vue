<template>
  <div class="career-progression-timeline">
    <!-- Timeline Header -->
    <header class="timeline-header">
      <h3 class="timeline-title">Career Progression Timeline</h3>
      <p class="timeline-subtitle">{{ timeframe }} journey from {{ beforeRole }} to {{ afterRole }}</p>
    </header>

    <!-- Before/After Comparison -->
    <div class="before-after-comparison">
      <div class="comparison-card before-card">
        <div class="card-header">
          <span class="card-label">Before</span>
          <div class="timeline-date">{{ formatDate(startDate) }}</div>
        </div>
        <div class="card-content">
          <h4 class="role-title">{{ careerProgression.before.role }}</h4>
          <p class="company-name">{{ careerProgression.before.company }}</p>
          <div class="role-details">
            <div class="detail-item">
              <span class="detail-label">Level:</span>
              <span class="detail-value">{{ careerProgression.before.level }}</span>
            </div>
            <div v-if="careerProgression.before.salary" class="detail-item">
              <span class="detail-label">Salary:</span>
              <span class="detail-value">${{ formatSalary(careerProgression.before.salary) }}</span>
            </div>
          </div>
          <div class="responsibilities">
            <h5 class="responsibilities-title">Key Responsibilities:</h5>
            <ul class="responsibilities-list">
              <li 
                v-for="responsibility in careerProgression.before.responsibilities" 
                :key="responsibility"
                class="responsibility-item"
              >
                {{ responsibility }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Progress Arrow -->
      <div class="progress-arrow">
        <div class="arrow-container">
          <svg class="arrow-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
          </svg>
          <div class="progress-stats">
            <div v-if="salaryIncrease" class="stat-item">
              <span class="stat-value">+{{ salaryIncrease }}%</span>
              <span class="stat-label">Salary</span>
            </div>
            <div class="stat-item">
              <span class="stat-value">{{ timeframe }}</span>
              <span class="stat-label">Duration</span>
            </div>
          </div>
        </div>
      </div>

      <div class="comparison-card after-card">
        <div class="card-header">
          <span class="card-label">After</span>
          <div class="timeline-date">{{ formatDate(endDate) }}</div>
        </div>
        <div class="card-content">
          <h4 class="role-title">{{ careerProgression.after.role }}</h4>
          <p class="company-name">{{ careerProgression.after.company }}</p>
          <div class="role-details">
            <div class="detail-item">
              <span class="detail-label">Level:</span>
              <span class="detail-value">{{ careerProgression.after.level }}</span>
            </div>
            <div v-if="careerProgression.after.salary" class="detail-item">
              <span class="detail-label">Salary:</span>
              <span class="detail-value">${{ formatSalary(careerProgression.after.salary) }}</span>
            </div>
          </div>
          <div class="responsibilities">
            <h5 class="responsibilities-title">Key Responsibilities:</h5>
            <ul class="responsibilities-list">
              <li 
                v-for="responsibility in careerProgression.after.responsibilities" 
                :key="responsibility"
                class="responsibility-item"
              >
                {{ responsibility }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Milestones Timeline -->
    <div v-if="careerProgression.keyMilestones.length > 0" class="milestones-timeline">
      <h4 class="milestones-title">Key Milestones</h4>
      <div class="timeline-container">
        <div class="timeline-line"></div>
        <div 
          v-for="(milestone, index) in sortedMilestones" 
          :key="milestone.title"
          class="milestone-item"
          :class="{ 'milestone-animate': animateMilestones }"
          :style="{ animationDelay: `${index * 0.2}s` }"
        >
          <div class="milestone-marker">
            <div class="marker-dot" :class="getMilestoneTypeClass(milestone.type)">
              <svg class="marker-icon" viewBox="0 0 24 24" fill="currentColor">
                <path :d="getMilestoneIcon(milestone.type)"/>
              </svg>
            </div>
            <div class="milestone-date">{{ formatMilestoneDate(milestone.date) }}</div>
          </div>
          <div class="milestone-content">
            <h5 class="milestone-title">{{ milestone.title }}</h5>
            <p class="milestone-description">{{ milestone.description }}</p>
            <span class="milestone-type-badge" :class="getMilestoneTypeClass(milestone.type)">
              {{ formatMilestoneType(milestone.type) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Metrics Display -->
    <div v-if="successMetrics.length > 0" class="success-metrics">
      <h4 class="metrics-title">Success Outcomes</h4>
      <div class="metrics-grid">
        <div 
          v-for="metric in successMetrics" 
          :key="metric.type"
          class="metric-card"
          :class="{ 'metric-animate': animateMetrics }"
        >
          <div class="metric-icon" :class="getMetricTypeClass(metric.type)">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path :d="getMetricIcon(metric.type)"/>
            </svg>
          </div>
          <div class="metric-content">
            <div class="metric-value">{{ formatMetricValue(metric) }}</div>
            <div class="metric-label">{{ getMetricLabel(metric.type) }}</div>
            <div class="metric-timeframe">{{ metric.timeframe }}</div>
            <div v-if="metric.verified" class="metric-verified">
              <svg class="verified-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
              <span>Verified</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LinkedIn Integration -->
    <div v-if="linkedinUrl" class="linkedin-integration">
      <h4 class="linkedin-title">Connect with {{ alumniName }}</h4>
      <div class="linkedin-card">
        <img 
          :src="profileImage" 
          :alt="`${alumniName} profile photo`"
          class="linkedin-avatar"
        />
        <div class="linkedin-info">
          <h5 class="linkedin-name">{{ alumniName }}</h5>
          <p class="linkedin-role">{{ currentRole }}</p>
          <p class="linkedin-company">{{ currentCompany }}</p>
        </div>
        <a 
          :href="linkedinUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="linkedin-button"
          @click="trackLinkedInClick"
        >
          <svg class="linkedin-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
          </svg>
          View LinkedIn Profile
        </a>
      </div>
    </div>

    <!-- Animation Controls -->
    <div class="animation-controls">
      <button 
        @click="toggleAnimations"
        class="animation-toggle-button"
        :aria-label="animationsEnabled ? 'Disable animations' : 'Enable animations'"
      >
        <svg class="animation-icon" viewBox="0 0 24 24" fill="currentColor">
          <path v-if="animationsEnabled" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
          <path v-else d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM4 12c0-4.42 3.58-8 8-8 1.85 0 3.55.63 4.9 1.69L5.69 16.9C4.63 15.55 4 13.85 4 12zm8 8c-1.85 0-3.55-.63-4.9-1.69L18.31 7.1C19.37 8.45 20 10.15 20 12c0 4.42-3.58 8-8 8z"/>
        </svg>
        {{ animationsEnabled ? 'Disable' : 'Enable' }} Animations
      </button>
      
      <button 
        @click="replayAnimations"
        class="replay-button"
        :disabled="!animationsEnabled"
        aria-label="Replay animations"
      >
        <svg class="replay-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
        </svg>
        Replay
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import type { CareerProgression, SuccessMetric, Milestone } from '@/types/homepage'

interface Props {
  careerProgression: CareerProgression
  successMetrics?: SuccessMetric[]
  alumniName: string
  currentRole: string
  currentCompany: string
  profileImage: string
  linkedinUrl?: string
  animationsEnabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  successMetrics: () => [],
  animationsEnabled: true
})

const emit = defineEmits<{
  'linkedin-click': [url: string]
}>()

// State
const animateMilestones = ref(false)
const animateMetrics = ref(false)
const animationsEnabled = ref(props.animationsEnabled)

// Computed properties
const timeframe = computed(() => props.careerProgression.timeframe)
const beforeRole = computed(() => props.careerProgression.before.role)
const afterRole = computed(() => props.careerProgression.after.role)

const startDate = computed(() => {
  // Calculate start date based on timeframe and milestones
  if (props.careerProgression.keyMilestones.length > 0) {
    const earliestMilestone = [...props.careerProgression.keyMilestones]
      .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())[0]
    return new Date(earliestMilestone.date)
  }
  // Fallback: estimate based on timeframe
  const now = new Date()
  const timeframeMonths = parseTimeframe(timeframe.value)
  return new Date(now.getTime() - (timeframeMonths * 30 * 24 * 60 * 60 * 1000))
})

const endDate = computed(() => {
  if (props.careerProgression.keyMilestones.length > 0) {
    const latestMilestone = [...props.careerProgression.keyMilestones]
      .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())[0]
    return new Date(latestMilestone.date)
  }
  return new Date()
})

const sortedMilestones = computed(() => {
  return [...props.careerProgression.keyMilestones]
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
})

const salaryIncrease = computed(() => {
  const before = props.careerProgression.before.salary
  const after = props.careerProgression.after.salary
  
  if (before && after) {
    return Math.round(((after - before) / before) * 100)
  }
  return null
})

// Methods
const parseTimeframe = (timeframe: string): number => {
  const match = timeframe.match(/(\d+)\s*(month|year)s?/i)
  if (match) {
    const value = parseInt(match[1])
    const unit = match[2].toLowerCase()
    return unit === 'year' ? value * 12 : value
  }
  return 12 // Default to 12 months
}

const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short'
  }).format(date)
}

const formatMilestoneDate = (date: Date): string => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(date))
}

const formatSalary = (salary: number): string => {
  return salary.toLocaleString()
}

const formatMilestoneType = (type: string): string => {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getMilestoneTypeClass = (type: string): string => {
  const classes = {
    'promotion': 'milestone-promotion',
    'job_change': 'milestone-job-change',
    'skill_acquisition': 'milestone-skill',
    'achievement': 'milestone-achievement'
  }
  return classes[type] || 'milestone-default'
}

const getMilestoneIcon = (type: string): string => {
  const icons = {
    'promotion': 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
    'job_change': 'M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 0 0-5.5-1.65l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z',
    'skill_acquisition': 'M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9l-11-6zM18.82 9L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z',
    'achievement': 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'
  }
  return icons[type] || icons['achievement']
}

const getMetricTypeClass = (type: string): string => {
  const classes = {
    'salary_increase': 'metric-salary',
    'promotion': 'metric-promotion',
    'job_placement': 'metric-job',
    'business_growth': 'metric-business',
    'network_expansion': 'metric-network'
  }
  return classes[type] || 'metric-default'
}

const getMetricIcon = (type: string): string => {
  const icons = {
    'salary_increase': 'M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z',
    'promotion': 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
    'job_placement': 'M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 0 0-5.5-1.65l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2z',
    'business_growth': 'M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z',
    'network_expansion': 'M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18c0 1.11.89 2 2 2s2-.89 2-2-.89-2-2-2-2 .89-2 2zm0-12c0-1.11.89-2 2-2s2 .89 2 2-.89-2-2-2-2 .89-2 2zm12 8c0 1.11.89 2 2 2s2-.89 2-2-.89-2-2-2-2 .89-2 2zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z'
  }
  return icons[type] || icons['business_growth']
}

const formatMetricValue = (metric: SuccessMetric): string => {
  switch (metric.unit) {
    case 'percentage':
      return `+${metric.value}%`
    case 'dollar':
      return `$${metric.value.toLocaleString()}`
    case 'count':
      return metric.value.toString()
    case 'days':
      return `${metric.value} days`
    default:
      return metric.value.toString()
  }
}

const getMetricLabel = (type: string): string => {
  const labels = {
    'salary_increase': 'Salary Increase',
    'promotion': 'Promotions',
    'job_placement': 'Job Placements',
    'business_growth': 'Business Growth',
    'network_expansion': 'Network Growth'
  }
  return labels[type] || type.replace('_', ' ')
}

const toggleAnimations = () => {
  animationsEnabled.value = !animationsEnabled.value
}

const replayAnimations = () => {
  if (!animationsEnabled.value) return
  
  animateMilestones.value = false
  animateMetrics.value = false
  
  setTimeout(() => {
    animateMilestones.value = true
    setTimeout(() => {
      animateMetrics.value = true
    }, 500)
  }, 100)
}

const trackLinkedInClick = () => {
  if (props.linkedinUrl) {
    emit('linkedin-click', props.linkedinUrl)
  }
}

// Initialize animations
onMounted(() => {
  if (animationsEnabled.value) {
    setTimeout(() => {
      animateMilestones.value = true
      setTimeout(() => {
        animateMetrics.value = true
      }, 1000)
    }, 500)
  }
})

// Watch for animation setting changes
watch(() => props.animationsEnabled, (newValue) => {
  animationsEnabled.value = newValue
  if (newValue) {
    replayAnimations()
  }
})
</script>

<style scoped>
.career-progression-timeline {
  @apply bg-white rounded-lg shadow-lg p-8 space-y-8;
}

.timeline-header {
  @apply text-center;
}

.timeline-title {
  @apply text-2xl font-bold text-gray-900 mb-2;
}

.timeline-subtitle {
  @apply text-lg text-gray-600;
}

.before-after-comparison {
  @apply flex flex-col lg:flex-row gap-6 items-center;
}

.comparison-card {
  @apply flex-1 bg-gray-50 rounded-lg p-6 border-2 transition-all duration-300;
}

.before-card {
  @apply border-red-200 bg-red-50;
}

.after-card {
  @apply border-green-200 bg-green-50;
}

.card-header {
  @apply flex justify-between items-center mb-4;
}

.card-label {
  @apply text-sm font-semibold uppercase tracking-wide text-gray-500;
}

.timeline-date {
  @apply text-sm text-gray-500 font-medium;
}

.role-title {
  @apply text-xl font-bold text-gray-900 mb-1;
}

.company-name {
  @apply text-lg text-gray-700 mb-4;
}

.role-details {
  @apply space-y-2 mb-4;
}

.detail-item {
  @apply flex justify-between;
}

.detail-label {
  @apply text-gray-600 font-medium;
}

.detail-value {
  @apply text-gray-900 font-semibold;
}

.responsibilities-title {
  @apply text-sm font-semibold text-gray-700 mb-2;
}

.responsibilities-list {
  @apply space-y-1;
}

.responsibility-item {
  @apply text-sm text-gray-600 flex items-start;
}

.responsibility-item::before {
  content: "•";
  @apply text-blue-500 font-bold mr-2 mt-0.5;
}

.progress-arrow {
  @apply flex flex-col items-center justify-center p-4;
}

.arrow-container {
  @apply text-center;
}

.arrow-icon {
  @apply w-8 h-8 text-blue-600 mb-2;
  transform: rotate(90deg);
}

@media (min-width: 1024px) {
  .arrow-icon {
    transform: rotate(0deg);
  }
}

.progress-stats {
  @apply space-y-2;
}

.stat-item {
  @apply text-center;
}

.stat-value {
  @apply block text-lg font-bold text-blue-600;
}

.stat-label {
  @apply text-xs text-gray-500 uppercase tracking-wide;
}

.milestones-timeline {
  @apply space-y-6;
}

.milestones-title {
  @apply text-xl font-bold text-gray-900 text-center;
}

.timeline-container {
  @apply relative;
}

.timeline-line {
  @apply absolute left-6 top-0 bottom-0 w-0.5 bg-gray-300;
}

.milestone-item {
  @apply relative flex gap-6 pb-8;
}

.milestone-item.milestone-animate {
  animation: slideInLeft 0.6s ease-out forwards;
  opacity: 0;
  transform: translateX(-20px);
}

@keyframes slideInLeft {
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.milestone-marker {
  @apply flex flex-col items-center;
}

.marker-dot {
  @apply w-12 h-12 rounded-full flex items-center justify-center relative z-10 border-4 border-white shadow-lg;
}

.milestone-promotion {
  @apply bg-yellow-500;
}

.milestone-job-change {
  @apply bg-blue-500;
}

.milestone-skill {
  @apply bg-green-500;
}

.milestone-achievement {
  @apply bg-purple-500;
}

.milestone-default {
  @apply bg-gray-500;
}

.marker-icon {
  @apply w-6 h-6 text-white;
}

.milestone-date {
  @apply text-xs text-gray-500 mt-2 text-center font-medium;
}

.milestone-content {
  @apply flex-1 bg-white rounded-lg p-4 shadow-sm border border-gray-200;
}

.milestone-title {
  @apply font-semibold text-gray-900 mb-2;
}

.milestone-description {
  @apply text-gray-700 mb-3;
}

.milestone-type-badge {
  @apply inline-block px-2 py-1 text-xs rounded-full text-white font-medium;
}

.success-metrics {
  @apply space-y-6;
}

.metrics-title {
  @apply text-xl font-bold text-gray-900 text-center;
}

.metrics-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.metric-card {
  @apply bg-white rounded-lg p-6 border border-gray-200 shadow-sm text-center;
}

.metric-card.metric-animate {
  animation: scaleIn 0.5s ease-out forwards;
  opacity: 0;
  transform: scale(0.9);
}

@keyframes scaleIn {
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.metric-icon {
  @apply w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4;
}

.metric-salary {
  @apply bg-green-100 text-green-600;
}

.metric-promotion {
  @apply bg-yellow-100 text-yellow-600;
}

.metric-job {
  @apply bg-blue-100 text-blue-600;
}

.metric-business {
  @apply bg-purple-100 text-purple-600;
}

.metric-network {
  @apply bg-indigo-100 text-indigo-600;
}

.metric-default {
  @apply bg-gray-100 text-gray-600;
}

.metric-icon svg {
  @apply w-6 h-6;
}

.metric-value {
  @apply text-2xl font-bold text-gray-900 mb-1;
}

.metric-label {
  @apply text-gray-600 font-medium mb-2;
}

.metric-timeframe {
  @apply text-sm text-gray-500 mb-2;
}

.metric-verified {
  @apply flex items-center justify-center gap-1 text-green-600 text-sm;
}

.verified-icon {
  @apply w-4 h-4;
}

.linkedin-integration {
  @apply space-y-4;
}

.linkedin-title {
  @apply text-xl font-bold text-gray-900 text-center;
}

.linkedin-card {
  @apply bg-white rounded-lg p-6 border border-gray-200 shadow-sm flex items-center gap-4;
}

.linkedin-avatar {
  @apply w-16 h-16 rounded-full object-cover border-2 border-gray-200;
}

.linkedin-info {
  @apply flex-1;
}

.linkedin-name {
  @apply text-lg font-semibold text-gray-900;
}

.linkedin-role {
  @apply text-gray-700 font-medium;
}

.linkedin-company {
  @apply text-gray-600;
}

.linkedin-button {
  @apply flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.linkedin-icon {
  @apply w-5 h-5;
}

.animation-controls {
  @apply flex justify-center gap-4 pt-6 border-t border-gray-200;
}

.animation-toggle-button,
.replay-button {
  @apply flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.animation-icon,
.replay-icon {
  @apply w-5 h-5;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .career-progression-timeline {
    @apply p-4 space-y-6;
  }
  
  .timeline-title {
    @apply text-xl;
  }
  
  .timeline-subtitle {
    @apply text-base;
  }
  
  .comparison-card {
    @apply p-4;
  }
  
  .role-title {
    @apply text-lg;
  }
  
  .company-name {
    @apply text-base;
  }
  
  .milestone-item {
    @apply gap-4;
  }
  
  .marker-dot {
    @apply w-10 h-10;
  }
  
  .marker-icon {
    @apply w-5 h-5;
  }
  
  .milestone-content {
    @apply p-3;
  }
  
  .metrics-grid {
    @apply grid-cols-1;
  }
  
  .linkedin-card {
    @apply flex-col text-center gap-3;
  }
  
  .animation-controls {
    @apply flex-col;
  }
}
</style>