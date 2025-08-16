<template>
  <div class="before-after-comparison">
    <!-- Comparison Header -->
    <header class="comparison-header">
      <h3 class="comparison-title">Career Transformation</h3>
      <p class="comparison-subtitle">{{ timeframe }} journey of professional growth</p>
    </header>

    <!-- Visual Comparison -->
    <div class="visual-comparison">
      <!-- Before Section -->
      <div class="comparison-section before-section">
        <div class="section-header">
          <span class="section-label before-label">Before</span>
          <div class="section-date">{{ formatDate(startDate) }}</div>
        </div>
        
        <div class="career-card before-card">
          <!-- Profile Section -->
          <div class="profile-section">
            <div class="avatar-container">
              <img 
                :src="profileImage" 
                :alt="`${alumniName} profile photo`"
                class="profile-avatar"
              />
              <div class="experience-badge before-badge">
                {{ getExperienceLevel(careerProgression.before.level) }}
              </div>
            </div>
            <div class="profile-info">
              <h4 class="profile-name">{{ alumniName }}</h4>
              <p class="profile-role">{{ careerProgression.before.role }}</p>
              <p class="profile-company">{{ careerProgression.before.company }}</p>
            </div>
          </div>

          <!-- Career Stats -->
          <div class="career-stats">
            <div class="stat-item">
              <span class="stat-label">Level</span>
              <span class="stat-value">{{ careerProgression.before.level }}</span>
            </div>
            <div v-if="careerProgression.before.salary" class="stat-item">
              <span class="stat-label">Salary</span>
              <span class="stat-value">${{ formatSalary(careerProgression.before.salary) }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Responsibilities</span>
              <span class="stat-value">{{ careerProgression.before.responsibilities.length }}</span>
            </div>
          </div>

          <!-- Key Responsibilities -->
          <div class="responsibilities-section">
            <h5 class="responsibilities-title">Key Responsibilities</h5>
            <ul class="responsibilities-list">
              <li 
                v-for="responsibility in careerProgression.before.responsibilities.slice(0, 3)" 
                :key="responsibility"
                class="responsibility-item"
              >
                {{ responsibility }}
              </li>
              <li v-if="careerProgression.before.responsibilities.length > 3" class="responsibility-more">
                +{{ careerProgression.before.responsibilities.length - 3 }} more
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Transformation Arrow -->
      <div class="transformation-arrow">
        <div class="arrow-container">
          <div class="arrow-line"></div>
          <div class="arrow-head">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
            </svg>
          </div>
          <div class="transformation-stats">
            <div v-if="salaryIncrease" class="transform-stat">
              <span class="transform-value">+{{ salaryIncrease }}%</span>
              <span class="transform-label">Salary</span>
            </div>
            <div class="transform-stat">
              <span class="transform-value">{{ timeframe }}</span>
              <span class="transform-label">Duration</span>
            </div>
            <div v-if="levelImprovement" class="transform-stat">
              <span class="transform-value">{{ levelImprovement }}</span>
              <span class="transform-label">Levels</span>
            </div>
          </div>
        </div>
      </div>

      <!-- After Section -->
      <div class="comparison-section after-section">
        <div class="section-header">
          <span class="section-label after-label">After</span>
          <div class="section-date">{{ formatDate(endDate) }}</div>
        </div>
        
        <div class="career-card after-card">
          <!-- Profile Section -->
          <div class="profile-section">
            <div class="avatar-container">
              <img 
                :src="profileImage" 
                :alt="`${alumniName} profile photo`"
                class="profile-avatar"
              />
              <div class="experience-badge after-badge">
                {{ getExperienceLevel(careerProgression.after.level) }}
              </div>
            </div>
            <div class="profile-info">
              <h4 class="profile-name">{{ alumniName }}</h4>
              <p class="profile-role">{{ careerProgression.after.role }}</p>
              <p class="profile-company">{{ careerProgression.after.company }}</p>
            </div>
          </div>

          <!-- Career Stats -->
          <div class="career-stats">
            <div class="stat-item">
              <span class="stat-label">Level</span>
              <span class="stat-value">{{ careerProgression.after.level }}</span>
            </div>
            <div v-if="careerProgression.after.salary" class="stat-item">
              <span class="stat-label">Salary</span>
              <span class="stat-value">${{ formatSalary(careerProgression.after.salary) }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Responsibilities</span>
              <span class="stat-value">{{ careerProgression.after.responsibilities.length }}</span>
            </div>
          </div>

          <!-- Key Responsibilities -->
          <div class="responsibilities-section">
            <h5 class="responsibilities-title">Key Responsibilities</h5>
            <ul class="responsibilities-list">
              <li 
                v-for="responsibility in careerProgression.after.responsibilities.slice(0, 3)" 
                :key="responsibility"
                class="responsibility-item"
              >
                {{ responsibility }}
              </li>
              <li v-if="careerProgression.after.responsibilities.length > 3" class="responsibility-more">
                +{{ careerProgression.after.responsibilities.length - 3 }} more
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Metrics Comparison -->
    <div v-if="successMetrics.length > 0" class="metrics-comparison">
      <h4 class="metrics-title">Success Metrics</h4>
      <div class="metrics-grid">
        <div 
          v-for="metric in successMetrics" 
          :key="metric.type"
          class="metric-comparison-card"
        >
          <div class="metric-header">
            <div class="metric-icon" :class="getMetricTypeClass(metric.type)">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path :d="getMetricIcon(metric.type)"/>
              </svg>
            </div>
            <h5 class="metric-name">{{ getMetricLabel(metric.type) }}</h5>
          </div>
          
          <div class="metric-comparison">
            <div class="metric-before">
              <span class="metric-label">Before</span>
              <span class="metric-value">{{ getBeforeMetricValue(metric) }}</span>
            </div>
            <div class="metric-arrow">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
              </svg>
            </div>
            <div class="metric-after">
              <span class="metric-label">After</span>
              <span class="metric-value">{{ formatMetricValue(metric) }}</span>
            </div>
          </div>
          
          <div class="metric-improvement">
            <span class="improvement-value">{{ formatMetricValue(metric) }}</span>
            <span class="improvement-label">improvement</span>
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

    <!-- Platform Impact -->
    <div v-if="platformImpact" class="platform-impact">
      <h4 class="impact-title">Platform Impact</h4>
      <div class="impact-grid">
        <div class="impact-item">
          <div class="impact-number">{{ platformImpact.connectionsMade }}</div>
          <div class="impact-label">New Connections</div>
        </div>
        <div class="impact-item">
          <div class="impact-number">{{ platformImpact.mentorsWorkedWith }}</div>
          <div class="impact-label">Mentors</div>
        </div>
        <div class="impact-item">
          <div class="impact-number">{{ platformImpact.referralsReceived }}</div>
          <div class="impact-label">Referrals</div>
        </div>
        <div class="impact-item">
          <div class="impact-number">{{ platformImpact.eventsAttended }}</div>
          <div class="impact-label">Events Attended</div>
        </div>
        <div class="impact-item">
          <div class="impact-number">+{{ platformImpact.networkGrowth }}%</div>
          <div class="impact-label">Network Growth</div>
        </div>
        <div class="impact-item">
          <div class="impact-number">{{ platformImpact.skillsAcquired.length }}</div>
          <div class="impact-label">Skills Acquired</div>
        </div>
      </div>
      
      <!-- Skills Acquired -->
      <div v-if="platformImpact.skillsAcquired.length > 0" class="skills-acquired">
        <h5 class="skills-title">Skills Acquired</h5>
        <div class="skills-list">
          <span 
            v-for="skill in platformImpact.skillsAcquired" 
            :key="skill"
            class="skill-tag"
          >
            {{ skill }}
          </span>
        </div>
      </div>
    </div>

    <!-- Interactive Toggle -->
    <div class="interactive-controls">
      <button 
        @click="toggleView"
        class="toggle-view-button"
        :aria-label="showDetailed ? 'Show simple view' : 'Show detailed view'"
      >
        <svg class="toggle-icon" viewBox="0 0 24 24" fill="currentColor">
          <path v-if="showDetailed" d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z"/>
          <path v-else d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/>
        </svg>
        {{ showDetailed ? 'Show Less' : 'Show More Details' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { CareerProgression, SuccessMetric, PlatformImpact } from '@/types/homepage'

interface Props {
  careerProgression: CareerProgression
  successMetrics?: SuccessMetric[]
  platformImpact?: PlatformImpact
  alumniName: string
  profileImage: string
  startDate?: Date
  endDate?: Date
}

const props = withDefaults(defineProps<Props>(), {
  successMetrics: () => [],
  startDate: () => new Date(Date.now() - 365 * 24 * 60 * 60 * 1000), // 1 year ago
  endDate: () => new Date()
})

// State
const showDetailed = ref(false)

// Computed properties
const timeframe = computed(() => props.careerProgression.timeframe)

const salaryIncrease = computed(() => {
  const before = props.careerProgression.before.salary
  const after = props.careerProgression.after.salary
  
  if (before && after) {
    return Math.round(((after - before) / before) * 100)
  }
  return null
})

const levelImprovement = computed(() => {
  const levels = ['Entry', 'Junior', 'Mid', 'Senior', 'Lead', 'Principal', 'Executive']
  const beforeIndex = levels.indexOf(props.careerProgression.before.level)
  const afterIndex = levels.indexOf(props.careerProgression.after.level)
  
  if (beforeIndex !== -1 && afterIndex !== -1 && afterIndex > beforeIndex) {
    return `+${afterIndex - beforeIndex}`
  }
  return null
})

// Methods
const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short'
  }).format(date)
}

const formatSalary = (salary: number): string => {
  return salary.toLocaleString()
}

const getExperienceLevel = (level: string): string => {
  const levelMap = {
    'Entry': 'Entry Level',
    'Junior': 'Junior',
    'Mid': 'Mid-Level',
    'Senior': 'Senior',
    'Lead': 'Lead',
    'Principal': 'Principal',
    'Executive': 'Executive'
  }
  return levelMap[level] || level
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
    'network_expansion': 'M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18c0 1.11.89 2 2 2s2-.89 2-2-.89-2-2-2-2 .89-2 2zm0-12c0-1.11.89-2 2-2s2 .89 2 2-.89-2-2-2-2 .89-2 2zm12 8c0 1.11.89 2 2 2s2-.89 2-2-.89-2-2-2-2 .89-2 2z'
  }
  return icons[type] || icons['business_growth']
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

const getBeforeMetricValue = (metric: SuccessMetric): string => {
  // For most metrics, "before" would be 0 or baseline
  switch (metric.type) {
    case 'salary_increase':
      return '$0'
    case 'promotion':
      return '0'
    case 'job_placement':
      return '0'
    case 'business_growth':
      return '0%'
    case 'network_expansion':
      return '0%'
    default:
      return '0'
  }
}

const toggleView = () => {
  showDetailed.value = !showDetailed.value
}
</script>

<style scoped>
.before-after-comparison {
  @apply bg-white rounded-lg shadow-lg p-8 space-y-8;
}

.comparison-header {
  @apply text-center;
}

.comparison-title {
  @apply text-2xl font-bold text-gray-900 mb-2;
}

.comparison-subtitle {
  @apply text-lg text-gray-600;
}

.visual-comparison {
  @apply flex flex-col lg:flex-row gap-8 items-stretch;
}

.comparison-section {
  @apply flex-1;
}

.section-header {
  @apply flex justify-between items-center mb-4;
}

.section-label {
  @apply text-lg font-semibold uppercase tracking-wide;
}

.before-label {
  @apply text-red-600;
}

.after-label {
  @apply text-green-600;
}

.section-date {
  @apply text-sm text-gray-500 font-medium;
}

.career-card {
  @apply rounded-lg p-6 border-2 space-y-6;
}

.before-card {
  @apply border-red-200 bg-red-50;
}

.after-card {
  @apply border-green-200 bg-green-50;
}

.profile-section {
  @apply flex items-center gap-4;
}

.avatar-container {
  @apply relative;
}

.profile-avatar {
  @apply w-16 h-16 rounded-full object-cover border-4 border-white shadow-lg;
}

.experience-badge {
  @apply absolute -bottom-1 -right-1 px-2 py-1 text-xs font-semibold rounded-full text-white;
}

.before-badge {
  @apply bg-red-500;
}

.after-badge {
  @apply bg-green-500;
}

.profile-info {
  @apply flex-1;
}

.profile-name {
  @apply text-lg font-bold text-gray-900;
}

.profile-role {
  @apply text-gray-700 font-medium;
}

.profile-company {
  @apply text-gray-600;
}

.career-stats {
  @apply grid grid-cols-3 gap-4;
}

.stat-item {
  @apply text-center;
}

.stat-label {
  @apply block text-xs text-gray-500 uppercase tracking-wide mb-1;
}

.stat-value {
  @apply text-lg font-bold text-gray-900;
}

.responsibilities-section {
  @apply space-y-3;
}

.responsibilities-title {
  @apply text-sm font-semibold text-gray-700;
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

.responsibility-more {
  @apply text-sm text-gray-500 italic;
}

.transformation-arrow {
  @apply flex items-center justify-center p-6;
}

.arrow-container {
  @apply text-center;
}

.arrow-line {
  @apply w-16 h-0.5 bg-blue-300 mb-2 mx-auto;
}

.arrow-head {
  @apply text-blue-600 mb-4;
}

.arrow-head svg {
  @apply w-8 h-8 mx-auto;
  transform: rotate(90deg);
}

@media (min-width: 1024px) {
  .arrow-line {
    @apply w-0.5 h-16;
  }
  
  .arrow-head svg {
    transform: rotate(0deg);
  }
}

.transformation-stats {
  @apply space-y-2;
}

.transform-stat {
  @apply text-center;
}

.transform-value {
  @apply block text-lg font-bold text-blue-600;
}

.transform-label {
  @apply text-xs text-gray-500 uppercase tracking-wide;
}

.metrics-comparison {
  @apply space-y-6;
}

.metrics-title {
  @apply text-xl font-bold text-gray-900 text-center;
}

.metrics-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.metric-comparison-card {
  @apply bg-white rounded-lg p-6 border border-gray-200 shadow-sm;
}

.metric-header {
  @apply flex items-center gap-3 mb-4;
}

.metric-icon {
  @apply w-10 h-10 rounded-full flex items-center justify-center;
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
  @apply w-5 h-5;
}

.metric-name {
  @apply font-semibold text-gray-900;
}

.metric-comparison {
  @apply flex items-center justify-between mb-4;
}

.metric-before,
.metric-after {
  @apply text-center;
}

.metric-label {
  @apply block text-xs text-gray-500 uppercase tracking-wide mb-1;
}

.metric-value {
  @apply text-lg font-bold text-gray-900;
}

.metric-arrow {
  @apply text-gray-400;
}

.metric-arrow svg {
  @apply w-5 h-5;
}

.metric-improvement {
  @apply text-center pt-4 border-t border-gray-200;
}

.improvement-value {
  @apply block text-xl font-bold text-green-600;
}

.improvement-label {
  @apply text-sm text-gray-600;
}

.metric-verified {
  @apply flex items-center justify-center gap-1 text-green-600 text-sm mt-2;
}

.verified-icon {
  @apply w-4 h-4;
}

.platform-impact {
  @apply space-y-6;
}

.impact-title {
  @apply text-xl font-bold text-gray-900 text-center;
}

.impact-grid {
  @apply grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4;
}

.impact-item {
  @apply text-center p-4 bg-blue-50 rounded-lg;
}

.impact-number {
  @apply text-2xl font-bold text-blue-600;
}

.impact-label {
  @apply text-sm text-gray-600 mt-1;
}

.skills-acquired {
  @apply space-y-3;
}

.skills-title {
  @apply text-lg font-semibold text-gray-900 text-center;
}

.skills-list {
  @apply flex flex-wrap gap-2 justify-center;
}

.skill-tag {
  @apply px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full;
}

.interactive-controls {
  @apply text-center pt-6 border-t border-gray-200;
}

.toggle-view-button {
  @apply flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors mx-auto;
}

.toggle-icon {
  @apply w-5 h-5;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .before-after-comparison {
    @apply p-4 space-y-6;
  }
  
  .comparison-title {
    @apply text-xl;
  }
  
  .comparison-subtitle {
    @apply text-base;
  }
  
  .visual-comparison {
    @apply gap-6;
  }
  
  .career-card {
    @apply p-4 space-y-4;
  }
  
  .profile-section {
    @apply gap-3;
  }
  
  .profile-avatar {
    @apply w-12 h-12;
  }
  
  .profile-name {
    @apply text-base;
  }
  
  .career-stats {
    @apply grid-cols-1 gap-2;
  }
  
  .metrics-grid {
    @apply grid-cols-1;
  }
  
  .impact-grid {
    @apply grid-cols-2;
  }
}
</style>