<template>
  <article 
    class="success-story-card"
    :class="{ 'featured': story.featured, 'expanded': isExpanded }"
    @click="toggleExpanded"
    role="button"
    :aria-expanded="isExpanded"
    :aria-label="`Success story of ${story.alumniProfile.name}`"
    tabindex="0"
    @keydown.enter="toggleExpanded"
    @keydown.space.prevent="toggleExpanded"
  >
    <!-- Story Header -->
    <header class="story-header">
      <div class="alumni-info">
        <img 
          :src="story.alumniProfile.profileImage" 
          :alt="`${story.alumniProfile.name} profile photo`"
          class="profile-image"
          loading="lazy"
        />
        <div class="alumni-details">
          <h3 class="alumni-name">{{ story.alumniProfile.name }}</h3>
          <p class="current-role">{{ story.alumniProfile.currentRole }}</p>
          <p class="company">{{ story.alumniProfile.currentCompany }}</p>
          <div class="meta-info">
            <span class="graduation-year">Class of {{ story.graduationYear }}</span>
            <span class="industry">{{ story.industry }}</span>
          </div>
        </div>
      </div>
      
      <!-- LinkedIn Link -->
      <a 
        v-if="story.alumniProfile.linkedinUrl"
        :href="story.alumniProfile.linkedinUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="linkedin-link"
        @click.stop
        aria-label="View LinkedIn profile"
      >
        <svg class="linkedin-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
        </svg>
      </a>
    </header>

    <!-- Story Summary -->
    <div class="story-content">
      <h4 class="story-title">{{ story.title }}</h4>
      <p class="story-summary">{{ story.summary }}</p>
      
      <!-- Key Metrics Preview -->
      <div class="metrics-preview">
        <div 
          v-for="metric in topMetrics" 
          :key="metric.type"
          class="metric-item"
        >
          <span class="metric-value">
            {{ formatMetricValue(metric) }}
          </span>
          <span class="metric-label">{{ getMetricLabel(metric.type) }}</span>
        </div>
      </div>

      <!-- Career Progression Preview -->
      <div class="progression-preview">
        <div class="before-after">
          <div class="career-stage before">
            <span class="stage-label">Before</span>
            <p class="stage-role">{{ story.careerProgression.before.role }}</p>
            <p class="stage-company">{{ story.careerProgression.before.company }}</p>
          </div>
          <div class="progression-arrow">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
            </svg>
          </div>
          <div class="career-stage after">
            <span class="stage-label">After</span>
            <p class="stage-role">{{ story.careerProgression.after.role }}</p>
            <p class="stage-company">{{ story.careerProgression.after.company }}</p>
          </div>
        </div>
        <p class="timeframe">{{ story.careerProgression.timeframe }}</p>
      </div>
    </div>

    <!-- Expanded Content -->
    <div v-if="isExpanded" class="expanded-content">
      <!-- Career Progression Visualization -->
      <div class="career-progression-section">
        <CareerProgressionTimeline
          :career-progression="story.careerProgression"
          :success-metrics="story.metrics"
          :alumni-name="story.alumniProfile.name"
          :current-role="story.alumniProfile.currentRole"
          :current-company="story.alumniProfile.currentCompany"
          :profile-image="story.alumniProfile.profileImage"
          :linkedin-url="story.alumniProfile.linkedinUrl"
          :animations-enabled="true"
          @linkedin-click="handleLinkedInClick"
        />
      </div>

      <!-- Before/After Comparison -->
      <div class="before-after-section">
        <BeforeAfterComparison
          :career-progression="story.careerProgression"
          :success-metrics="story.metrics"
          :platform-impact="story.platformImpact"
          :alumni-name="story.alumniProfile.name"
          :profile-image="story.alumniProfile.profileImage"
        />
      </div>

      <!-- Platform Impact Summary -->
      <div class="platform-impact">
        <h5>Platform Impact Summary</h5>
        <div class="impact-metrics">
          <div class="impact-item">
            <span class="impact-value">{{ story.platformImpact.connectionsMade }}</span>
            <span class="impact-label">Connections Made</span>
          </div>
          <div class="impact-item">
            <span class="impact-value">{{ story.platformImpact.mentorsWorkedWith }}</span>
            <span class="impact-label">Mentors</span>
          </div>
          <div class="impact-item">
            <span class="impact-value">{{ story.platformImpact.referralsReceived }}</span>
            <span class="impact-label">Referrals</span>
          </div>
          <div class="impact-item">
            <span class="impact-value">{{ story.platformImpact.eventsAttended }}</span>
            <span class="impact-label">Events</span>
          </div>
        </div>
      </div>

      <!-- Skills Acquired -->
      <div v-if="story.platformImpact.skillsAcquired.length" class="skills-acquired">
        <h5>Skills Acquired</h5>
        <div class="skills-list">
          <span 
            v-for="skill in story.platformImpact.skillsAcquired" 
            :key="skill"
            class="skill-tag"
          >
            {{ skill }}
          </span>
        </div>
      </div>

      <!-- Video Testimonial -->
      <div v-if="story.testimonialVideo" class="video-testimonial">
        <button 
          @click.stop="playVideo"
          class="video-play-button"
          aria-label="Play video testimonial"
        >
          <svg class="play-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M8 5v14l11-7z"/>
          </svg>
          <span>Watch Video Testimonial</span>
        </button>
      </div>

      <!-- Social Sharing -->
      <div class="social-sharing">
        <h5>Share This Story</h5>
        <div class="share-buttons">
          <button 
            @click.stop="shareStory('linkedin')"
            class="share-button linkedin"
            aria-label="Share on LinkedIn"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
            </svg>
            LinkedIn
          </button>
          <button 
            @click.stop="shareStory('twitter')"
            class="share-button twitter"
            aria-label="Share on Twitter"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
            </svg>
            Twitter
          </button>
          <button 
            @click.stop="shareStory('copy')"
            class="share-button copy"
            aria-label="Copy link"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            </svg>
            Copy Link
          </button>
        </div>
      </div>
    </div>

    <!-- Expand/Collapse Button -->
    <button 
      class="expand-button"
      @click.stop="toggleExpanded"
      :aria-label="isExpanded ? 'Collapse story' : 'Expand story'"
    >
      <span>{{ isExpanded ? 'Show Less' : 'Read Full Story' }}</span>
      <svg 
        class="expand-icon" 
        :class="{ 'rotated': isExpanded }"
        viewBox="0 0 24 24" 
        fill="currentColor"
      >
        <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
      </svg>
    </button>
  </article>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import CareerProgressionTimeline from './CareerProgressionTimeline.vue'
import BeforeAfterComparison from './BeforeAfterComparison.vue'
import type { SuccessStory, SuccessMetric } from '@/types/homepage'

interface Props {
  story: SuccessStory
}

const props = defineProps<Props>()
const emit = defineEmits<{
  share: [platform: string, story: SuccessStory]
  playVideo: [videoUrl: string]
}>()

const isExpanded = ref(false)

const topMetrics = computed(() => {
  return props.story.metrics
    .filter(metric => metric.verified)
    .sort((a, b) => {
      const priority = { 'salary_increase': 4, 'promotion': 3, 'job_placement': 2, 'business_growth': 1 }
      return (priority[b.type] || 0) - (priority[a.type] || 0)
    })
    .slice(0, 2)
})

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
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
    'job_placement': 'Job Placement',
    'promotion': 'Promotion',
    'business_growth': 'Business Growth',
    'network_expansion': 'Network Growth'
  }
  return labels[type] || type.replace('_', ' ')
}

const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short'
  }).format(new Date(date))
}

const playVideo = () => {
  if (props.story.testimonialVideo) {
    emit('playVideo', props.story.testimonialVideo)
  }
}

const shareStory = (platform: string) => {
  emit('share', platform, props.story)
}

const handleLinkedInClick = (url: string) => {
  // Track LinkedIn profile click
  window.open(url, '_blank', 'noopener,noreferrer')
}
</script>

<style scoped>
.success-story-card {
  @apply bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer;
  @apply border border-gray-200 overflow-hidden;
}

.success-story-card.featured {
  @apply ring-2 ring-blue-500 shadow-xl;
}

.success-story-card:hover {
  @apply transform -translate-y-1;
}

.story-header {
  @apply p-6 pb-4 flex justify-between items-start;
}

.alumni-info {
  @apply flex gap-4 flex-1;
}

.profile-image {
  @apply w-16 h-16 rounded-full object-cover border-2 border-gray-200;
}

.alumni-details {
  @apply flex-1;
}

.alumni-name {
  @apply text-xl font-semibold text-gray-900 mb-1;
}

.current-role {
  @apply text-lg text-gray-700 font-medium mb-1;
}

.company {
  @apply text-gray-600 mb-2;
}

.meta-info {
  @apply flex gap-4 text-sm text-gray-500;
}

.linkedin-link {
  @apply text-blue-600 hover:text-blue-800 transition-colors;
}

.linkedin-icon {
  @apply w-6 h-6;
}

.story-content {
  @apply px-6 pb-4;
}

.story-title {
  @apply text-lg font-semibold text-gray-900 mb-2;
}

.story-summary {
  @apply text-gray-700 mb-4 leading-relaxed;
}

.metrics-preview {
  @apply flex gap-6 mb-4 p-4 bg-gray-50 rounded-lg;
}

.metric-item {
  @apply text-center;
}

.metric-value {
  @apply block text-2xl font-bold text-blue-600;
}

.metric-label {
  @apply text-sm text-gray-600;
}

.progression-preview {
  @apply mb-4;
}

.before-after {
  @apply flex items-center gap-4 mb-2;
}

.career-stage {
  @apply flex-1 text-center p-3 rounded-lg;
}

.career-stage.before {
  @apply bg-red-50 border border-red-200;
}

.career-stage.after {
  @apply bg-green-50 border border-green-200;
}

.stage-label {
  @apply text-xs font-semibold uppercase tracking-wide text-gray-500 block mb-1;
}

.stage-role {
  @apply font-medium text-gray-900;
}

.stage-company {
  @apply text-sm text-gray-600;
}

.progression-arrow {
  @apply text-gray-400;
}

.progression-arrow svg {
  @apply w-6 h-6;
}

.timeframe {
  @apply text-center text-sm text-gray-600 font-medium;
}

.expanded-content {
  @apply px-6 pb-4 border-t border-gray-200 pt-4 space-y-8;
}

.career-progression-section {
  @apply bg-gray-50 rounded-lg p-4 -mx-2;
}

.before-after-section {
  @apply bg-blue-50 rounded-lg p-4 -mx-2;
}

.platform-impact h5,
.milestones h5,
.skills-acquired h5,
.social-sharing h5 {
  @apply text-lg font-semibold text-gray-900 mb-3;
}

.impact-metrics {
  @apply grid grid-cols-2 md:grid-cols-4 gap-4;
}

.impact-item {
  @apply text-center p-3 bg-blue-50 rounded-lg;
}

.impact-value {
  @apply block text-xl font-bold text-blue-600;
}

.impact-label {
  @apply text-sm text-gray-600;
}

.milestone-list {
  @apply space-y-4;
}

.milestone-item {
  @apply flex gap-4 p-4 bg-gray-50 rounded-lg;
}

.milestone-date {
  @apply text-sm font-medium text-gray-500 min-w-20;
}

.milestone-content {
  @apply flex-1;
}

.milestone-title {
  @apply font-medium text-gray-900 mb-1;
}

.milestone-description {
  @apply text-gray-700 text-sm mb-2;
}

.milestone-type {
  @apply inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full capitalize;
}

.skills-list {
  @apply flex flex-wrap gap-2;
}

.skill-tag {
  @apply px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full;
}

.video-testimonial {
  @apply text-center;
}

.video-play-button {
  @apply inline-flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors;
}

.play-icon {
  @apply w-5 h-5;
}

.share-buttons {
  @apply flex gap-3 justify-center;
}

.share-button {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors;
}

.share-button svg {
  @apply w-4 h-4;
}

.share-button.linkedin {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.share-button.twitter {
  @apply bg-sky-500 text-white hover:bg-sky-600;
}

.share-button.copy {
  @apply bg-gray-600 text-white hover:bg-gray-700;
}

.expand-button {
  @apply w-full flex items-center justify-center gap-2 p-4 bg-gray-50 hover:bg-gray-100 transition-colors text-blue-600 font-medium;
}

.expand-icon {
  @apply w-5 h-5 transition-transform duration-200;
}

.expand-icon.rotated {
  @apply rotate-180;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .story-header {
    @apply p-4 pb-3;
  }
  
  .alumni-info {
    @apply gap-3;
  }
  
  .profile-image {
    @apply w-12 h-12;
  }
  
  .alumni-name {
    @apply text-lg;
  }
  
  .current-role {
    @apply text-base;
  }
  
  .story-content {
    @apply px-4;
  }
  
  .expanded-content {
    @apply px-4;
  }
  
  .metrics-preview {
    @apply flex-col gap-3;
  }
  
  .before-after {
    @apply flex-col gap-3;
  }
  
  .career-stage {
    @apply p-2;
  }
  
  .impact-metrics {
    @apply grid-cols-2;
  }
  
  .milestone-item {
    @apply flex-col gap-2;
  }
  
  .milestone-date {
    @apply min-w-auto;
  }
}
</style>