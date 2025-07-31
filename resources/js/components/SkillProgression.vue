<template>
  <div class="skill-progression">
    <div class="progression-header">
      <h3 class="text-xl font-semibold text-gray-900">Skill Development Tracking</h3>
      <p class="text-sm text-gray-600">Monitor your skill growth over time</p>
    </div>

    <div class="skill-selector mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Skill to Track</label>
      <select v-model="selectedSkillId" @change="loadProgression" class="form-select">
        <option value="">Choose a skill...</option>
        <option v-for="skill in userSkills" :key="skill.id" :value="skill.skill.id">
          {{ skill.skill.name }} ({{ skill.proficiency_level }})
        </option>
      </select>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="animate-pulse">
        <div class="h-64 bg-gray-200 rounded-lg mb-4"></div>
        <div class="space-y-3">
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <div v-else-if="progressionData" class="progression-content">
      <!-- Skill Overview -->
      <div class="skill-overview">
        <div class="skill-header">
          <h4 class="skill-name">{{ progressionData.skill.name }}</h4>
          <div class="skill-badges">
            <span class="proficiency-badge" :class="progressionData.current_level.toLowerCase()">
              {{ progressionData.current_level }}
            </span>
            <span class="experience-badge">
              {{ progressionData.years_experience }} years
            </span>
          </div>
        </div>
        
        <div class="skill-stats">
          <div class="stat-card">
            <div class="stat-number">{{ progressionData.total_endorsements }}</div>
            <div class="stat-label">Total Endorsements</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">{{ getRecentEndorsements() }}</div>
            <div class="stat-label">This Month</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">{{ getGrowthRate() }}%</div>
            <div class="stat-label">Growth Rate</div>
          </div>
        </div>
      </div>

      <!-- Progression Chart -->
      <div class="progression-chart">
        <h5 class="chart-title">Endorsement Timeline</h5>
        <div class="chart-container">
          <div class="chart-area">
            <div
              v-for="(point, index) in chartData"
              :key="index"
              class="chart-point"
              :style="getPointStyle(point, index)"
              :title="`${point.month}: ${point.endorsement_count} endorsements`"
            >
              <div class="point-dot"></div>
              <div class="point-label">{{ point.month }}</div>
              <div class="point-value">{{ point.endorsement_count }}</div>
            </div>
          </div>
          <div class="chart-line" :style="getLineStyle()"></div>
        </div>
      </div>

      <!-- Milestones -->
      <div class="progression-milestones">
        <h5 class="milestones-title">Achievement Milestones</h5>
        <div class="milestones-timeline">
          <div
            v-for="milestone in milestones"
            :key="milestone.id"
            class="milestone-item"
            :class="{ 'achieved': milestone.achieved }"
          >
            <div class="milestone-icon">
              <Icon :name="milestone.icon" class="w-5 h-5" />
            </div>
            <div class="milestone-content">
              <h6 class="milestone-title">{{ milestone.title }}</h6>
              <p class="milestone-description">{{ milestone.description }}</p>
              <div class="milestone-progress" v-if="!milestone.achieved">
                <div class="progress-bar">
                  <div 
                    class="progress-fill" 
                    :style="{ width: milestone.progress + '%' }"
                  ></div>
                </div>
                <span class="progress-text">{{ milestone.progress }}% complete</span>
              </div>
              <div class="milestone-date" v-else>
                Achieved {{ formatDate(milestone.achieved_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Learning Recommendations -->
      <div class="learning-recommendations">
        <h5 class="recommendations-title">Recommended Next Steps</h5>
        <div class="recommendations-grid">
          <div
            v-for="recommendation in recommendations"
            :key="recommendation.id"
            class="recommendation-card"
          >
            <div class="recommendation-type">
              <Icon :name="getRecommendationIcon(recommendation.type)" class="w-5 h-5" />
              {{ recommendation.type }}
            </div>
            <h6 class="recommendation-title">{{ recommendation.title }}</h6>
            <p class="recommendation-description">{{ recommendation.description }}</p>
            <div class="recommendation-actions">
              <a 
                :href="recommendation.url" 
                target="_blank" 
                class="btn-primary btn-sm"
              >
                Start Learning
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Skill Comparison -->
      <div class="skill-comparison">
        <h5 class="comparison-title">Industry Comparison</h5>
        <div class="comparison-chart">
          <div class="comparison-item">
            <div class="comparison-label">Your Level</div>
            <div class="comparison-bar">
              <div 
                class="comparison-fill your-level" 
                :style="{ width: getUserLevelPercentage() + '%' }"
              ></div>
            </div>
            <div class="comparison-value">{{ progressionData.current_level }}</div>
          </div>
          <div class="comparison-item">
            <div class="comparison-label">Industry Average</div>
            <div class="comparison-bar">
              <div 
                class="comparison-fill industry-average" 
                :style="{ width: '65%' }"
              ></div>
            </div>
            <div class="comparison-value">Intermediate</div>
          </div>
          <div class="comparison-item">
            <div class="comparison-label">Top 10%</div>
            <div class="comparison-bar">
              <div 
                class="comparison-fill top-performers" 
                :style="{ width: '90%' }"
              ></div>
            </div>
            <div class="comparison-value">Expert</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="selectedSkillId" class="empty-state">
      <Icon name="chart-bar" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
      <p class="text-gray-600 text-center">No progression data available for this skill yet.</p>
    </div>
  </div>
</template>

<script>
import Icon from './Icon.vue'

export default {
  name: 'SkillProgression',
  components: {
    Icon
  },
  data() {
    return {
      userSkills: [],
      selectedSkillId: '',
      progressionData: null,
      loading: false,
      chartData: [],
      milestones: [],
      recommendations: []
    }
  },
  mounted() {
    this.loadUserSkills()
  },
  methods: {
    async loadUserSkills() {
      try {
        const response = await fetch('/api/users/me/skills')
        const data = await response.json()
        this.userSkills = data.skills
      } catch (error) {
        console.error('Failed to load user skills:', error)
      }
    },
    async loadProgression() {
      if (!this.selectedSkillId) {
        this.progressionData = null
        return
      }

      this.loading = true
      try {
        const response = await fetch(`/api/skills/${this.selectedSkillId}/progression`)
        const data = await response.json()
        
        this.progressionData = data
        this.chartData = data.progression || []
        this.generateMilestones()
        this.loadRecommendations()
      } catch (error) {
        console.error('Failed to load progression:', error)
      } finally {
        this.loading = false
      }
    },
    async loadRecommendations() {
      try {
        const response = await fetch(`/api/skills/${this.selectedSkillId}/recommendations`)
        const data = await response.json()
        this.recommendations = data.recommendations || []
      } catch (error) {
        console.error('Failed to load recommendations:', error)
      }
    },
    generateMilestones() {
      const currentEndorsements = this.progressionData.total_endorsements
      
      this.milestones = [
        {
          id: 1,
          title: 'First Endorsement',
          description: 'Receive your first skill endorsement',
          icon: 'thumb-up',
          achieved: currentEndorsements >= 1,
          progress: Math.min(currentEndorsements, 1) * 100,
          achieved_at: currentEndorsements >= 1 ? '2024-01-15' : null
        },
        {
          id: 2,
          title: 'Recognized Expert',
          description: 'Get 5 endorsements from colleagues',
          icon: 'star',
          achieved: currentEndorsements >= 5,
          progress: Math.min(currentEndorsements / 5, 1) * 100,
          achieved_at: currentEndorsements >= 5 ? '2024-03-20' : null
        },
        {
          id: 3,
          title: 'Community Leader',
          description: 'Reach 10 endorsements and mentor others',
          icon: 'users',
          achieved: currentEndorsements >= 10,
          progress: Math.min(currentEndorsements / 10, 1) * 100,
          achieved_at: currentEndorsements >= 10 ? '2024-06-10' : null
        },
        {
          id: 4,
          title: 'Industry Authority',
          description: 'Achieve 25 endorsements and expert status',
          icon: 'badge-check',
          achieved: currentEndorsements >= 25,
          progress: Math.min(currentEndorsements / 25, 1) * 100,
          achieved_at: currentEndorsements >= 25 ? '2024-09-05' : null
        }
      ]
    },
    getRecentEndorsements() {
      if (!this.chartData.length) return 0
      const currentMonth = new Date().toISOString().slice(0, 7)
      const recentData = this.chartData.find(d => d.month === currentMonth)
      return recentData ? recentData.endorsement_count : 0
    },
    getGrowthRate() {
      if (this.chartData.length < 2) return 0
      const recent = this.chartData[this.chartData.length - 1]
      const previous = this.chartData[this.chartData.length - 2]
      if (!previous.endorsement_count) return 100
      return Math.round(((recent.endorsement_count - previous.endorsement_count) / previous.endorsement_count) * 100)
    },
    getPointStyle(point, index) {
      const maxValue = Math.max(...this.chartData.map(d => d.endorsement_count))
      const height = maxValue > 0 ? (point.endorsement_count / maxValue) * 100 : 0
      const left = (index / (this.chartData.length - 1)) * 100
      
      return {
        left: `${left}%`,
        bottom: `${height}%`
      }
    },
    getLineStyle() {
      // Simple line connecting points - in a real implementation, you'd use a charting library
      return {
        background: 'linear-gradient(to right, #3B82F6, #10B981)'
      }
    },
    getUserLevelPercentage() {
      const levels = { 'Beginner': 25, 'Intermediate': 50, 'Advanced': 75, 'Expert': 100 }
      return levels[this.progressionData.current_level] || 0
    },
    getRecommendationIcon(type) {
      const icons = {
        'Course': 'academic-cap',
        'Article': 'document-text',
        'Video': 'play',
        'Book': 'book-open',
        'Workshop': 'users',
        'Certification': 'badge-check'
      }
      return icons[type] || 'lightbulb'
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.skill-progression {
  @apply space-y-8;
}

.progression-header {
  @apply text-center mb-8;
}

.skill-overview {
  @apply bg-white p-6 rounded-lg border;
}

.skill-header {
  @apply flex justify-between items-center mb-4;
}

.skill-name {
  @apply text-xl font-semibold text-gray-900;
}

.skill-badges {
  @apply flex space-x-2;
}

.proficiency-badge {
  @apply px-3 py-1 rounded-full text-sm font-medium;
}

.proficiency-badge.beginner {
  @apply bg-gray-100 text-gray-700;
}

.proficiency-badge.intermediate {
  @apply bg-blue-100 text-blue-700;
}

.proficiency-badge.advanced {
  @apply bg-green-100 text-green-700;
}

.proficiency-badge.expert {
  @apply bg-purple-100 text-purple-700;
}

.experience-badge {
  @apply px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm font-medium;
}

.skill-stats {
  @apply grid grid-cols-3 gap-4;
}

.stat-card {
  @apply text-center p-4 bg-gray-50 rounded-lg;
}

.stat-number {
  @apply text-2xl font-bold text-blue-600;
}

.stat-label {
  @apply text-sm text-gray-600;
}

.progression-chart {
  @apply bg-white p-6 rounded-lg border;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 mb-4;
}

.chart-container {
  @apply relative h-64 bg-gray-50 rounded-lg p-4;
}

.chart-area {
  @apply relative h-full;
}

.chart-point {
  @apply absolute transform -translate-x-1/2;
}

.point-dot {
  @apply w-3 h-3 bg-blue-500 rounded-full;
}

.point-label {
  @apply text-xs text-gray-600 mt-2 transform -translate-x-1/2;
}

.point-value {
  @apply text-xs font-medium text-blue-600 -mt-6 transform -translate-x-1/2;
}

.progression-milestones {
  @apply bg-white p-6 rounded-lg border;
}

.milestones-title {
  @apply text-lg font-semibold text-gray-900 mb-4;
}

.milestones-timeline {
  @apply space-y-4;
}

.milestone-item {
  @apply flex items-start space-x-4 p-4 rounded-lg;
  @apply bg-gray-50;
}

.milestone-item.achieved {
  @apply bg-green-50;
}

.milestone-icon {
  @apply flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center;
  @apply bg-gray-200 text-gray-600;
}

.milestone-item.achieved .milestone-icon {
  @apply bg-green-200 text-green-600;
}

.milestone-content {
  @apply flex-1;
}

.milestone-title {
  @apply font-semibold text-gray-900;
}

.milestone-description {
  @apply text-sm text-gray-600 mt-1;
}

.milestone-progress {
  @apply mt-2;
}

.progress-bar {
  @apply w-full bg-gray-200 rounded-full h-2;
}

.progress-fill {
  @apply bg-blue-500 h-2 rounded-full transition-all duration-300;
}

.progress-text {
  @apply text-xs text-gray-600 mt-1;
}

.milestone-date {
  @apply text-xs text-green-600 mt-1;
}

.learning-recommendations {
  @apply bg-white p-6 rounded-lg border;
}

.recommendations-title {
  @apply text-lg font-semibold text-gray-900 mb-4;
}

.recommendations-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.recommendation-card {
  @apply p-4 border rounded-lg hover:shadow-md transition-shadow;
}

.recommendation-type {
  @apply flex items-center space-x-2 text-sm text-blue-600 font-medium mb-2;
}

.recommendation-title {
  @apply font-semibold text-gray-900 mb-2;
}

.recommendation-description {
  @apply text-sm text-gray-600 mb-3;
}

.skill-comparison {
  @apply bg-white p-6 rounded-lg border;
}

.comparison-title {
  @apply text-lg font-semibold text-gray-900 mb-4;
}

.comparison-chart {
  @apply space-y-4;
}

.comparison-item {
  @apply flex items-center space-x-4;
}

.comparison-label {
  @apply w-32 text-sm font-medium text-gray-700;
}

.comparison-bar {
  @apply flex-1 h-6 bg-gray-200 rounded-full overflow-hidden;
}

.comparison-fill {
  @apply h-full transition-all duration-500;
}

.comparison-fill.your-level {
  @apply bg-blue-500;
}

.comparison-fill.industry-average {
  @apply bg-yellow-500;
}

.comparison-fill.top-performers {
  @apply bg-green-500;
}

.comparison-value {
  @apply w-20 text-sm font-medium text-gray-900;
}

.empty-state {
  @apply text-center py-12;
}
</style>