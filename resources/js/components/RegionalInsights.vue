<template>
  <div class="regional-insights-panel">
    <div class="panel-header">
      <h5 class="panel-title">
        <i class="fas fa-chart-bar"></i>
        {{ region }} Insights
      </h5>
      <button 
        class="btn-close"
        @click="$emit('close')"
        aria-label="Close"
      ></button>
    </div>

    <div class="panel-content">
      <div v-if="loading" class="loading-state">
        <div class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Loading insights...</span>
        </div>
        <p>Loading regional data...</p>
      </div>

      <div v-else-if="stats" class="insights-content">
        <!-- Overview Stats -->
        <div class="stats-overview">
          <div class="stat-card">
            <div class="stat-value">{{ stats.total_alumni }}</div>
            <div class="stat-label">Total Alumni</div>
          </div>
          
          <div class="stat-card">
            <div class="stat-value">{{ stats.average_experience }}</div>
            <div class="stat-label">Avg. Experience (years)</div>
          </div>
        </div>

        <!-- Industry Distribution -->
        <div v-if="stats.industries && Object.keys(stats.industries).length" class="insight-section">
          <h6 class="section-title">
            <i class="fas fa-industry"></i>
            Top Industries
          </h6>
          <div class="industry-chart">
            <div 
              v-for="(count, industry) in topIndustries" 
              :key="industry"
              class="industry-bar"
            >
              <div class="industry-info">
                <span class="industry-name">{{ industry }}</span>
                <span class="industry-count">{{ count }}</span>
              </div>
              <div class="industry-progress">
                <div 
                  class="progress-bar"
                  :style="{ width: getIndustryPercentage(count) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Graduation Years -->
        <div v-if="stats.graduation_years && Object.keys(stats.graduation_years).length" class="insight-section">
          <h6 class="section-title">
            <i class="fas fa-calendar-alt"></i>
            Graduation Years
          </h6>
          <div class="year-distribution">
            <div class="year-chart">
              <div 
                v-for="(count, year) in recentYears" 
                :key="year"
                class="year-bar"
                :style="{ height: getYearBarHeight(count) + '%' }"
                :title="`${year}: ${count} alumni`"
              >
                <span class="year-label">{{ year }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Companies -->
        <div v-if="stats.top_companies && Object.keys(stats.top_companies).length" class="insight-section">
          <h6 class="section-title">
            <i class="fas fa-building"></i>
            Top Employers
          </h6>
          <div class="companies-list">
            <div 
              v-for="(count, company) in topCompanies" 
              :key="company"
              class="company-item"
            >
              <div class="company-info">
                <span class="company-name">{{ company }}</span>
                <span class="company-count">{{ count }} alumni</span>
              </div>
              <div class="company-progress">
                <div 
                  class="progress-bar"
                  :style="{ width: getCompanyPercentage(count) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Regional Groups Suggestions -->
        <div v-if="suggestedGroups.length" class="insight-section">
          <h6 class="section-title">
            <i class="fas fa-users"></i>
            Suggested Groups
          </h6>
          <div class="groups-suggestions">
            <div 
              v-for="group in suggestedGroups" 
              :key="group.name"
              class="group-suggestion"
            >
              <div class="group-header">
                <h6 class="group-name">{{ group.name }}</h6>
                <span class="group-type">{{ group.type }}</span>
              </div>
              <p class="group-details">
                {{ group.member_count }} potential members
                <span v-if="group.industries">
                  • {{ group.industries.slice(0, 2).join(', ') }}
                </span>
              </p>
              <button 
                class="btn btn-sm btn-outline-primary"
                @click="exploreGroup(group)"
              >
                Explore Group
              </button>
            </div>
          </div>
        </div>

        <!-- Networking Opportunities -->
        <div class="insight-section">
          <h6 class="section-title">
            <i class="fas fa-handshake"></i>
            Networking Opportunities
          </h6>
          <div class="opportunities-list">
            <div class="opportunity-item">
              <i class="fas fa-calendar-check text-primary"></i>
              <div class="opportunity-content">
                <h6>Regional Meetup</h6>
                <p>Connect with {{ Math.min(stats.total_alumni, 50) }} nearby alumni</p>
              </div>
              <button class="btn btn-sm btn-primary">
                Organize
              </button>
            </div>
            
            <div class="opportunity-item">
              <i class="fas fa-briefcase text-success"></i>
              <div class="opportunity-content">
                <h6>Industry Network</h6>
                <p>Join professional groups in your field</p>
              </div>
              <button class="btn btn-sm btn-success">
                Join
              </button>
            </div>
            
            <div class="opportunity-item">
              <i class="fas fa-graduation-cap text-info"></i>
              <div class="opportunity-content">
                <h6>Mentorship Program</h6>
                <p>Connect with alumni for career guidance</p>
              </div>
              <button class="btn btn-sm btn-info">
                Learn More
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <i class="fas fa-chart-bar text-muted"></i>
        <p>No data available for this region</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

interface RegionalStats {
  total_alumni: number
  industries: Record<string, number>
  graduation_years: Record<string, number>
  top_companies: Record<string, number>
  average_experience: number
}

interface GroupSuggestion {
  name: string
  type: string
  member_count: number
  industries?: string[]
  location?: string
}

interface RegionalInsightsProps {
  region: string
  stats?: RegionalStats | null
}

const props = defineProps<RegionalInsightsProps>()

const emit = defineEmits<{
  close: []
  exploreGroup: [group: GroupSuggestion]
}>()

const loading = ref(false)
const suggestedGroups = ref<GroupSuggestion[]>([])

const topIndustries = computed(() => {
  if (!props.stats?.industries) return {}
  
  return Object.fromEntries(
    Object.entries(props.stats.industries)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 5)
  )
})

const topCompanies = computed(() => {
  if (!props.stats?.top_companies) return {}
  
  return Object.fromEntries(
    Object.entries(props.stats.top_companies)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 5)
  )
})

const recentYears = computed(() => {
  if (!props.stats?.graduation_years) return {}
  
  return Object.fromEntries(
    Object.entries(props.stats.graduation_years)
      .sort(([a], [b]) => parseInt(b) - parseInt(a))
      .slice(0, 10)
  )
})

const getIndustryPercentage = (count: number): number => {
  if (!props.stats?.total_alumni) return 0
  return Math.round((count / props.stats.total_alumni) * 100)
}

const getCompanyPercentage = (count: number): number => {
  if (!props.stats?.total_alumni) return 0
  return Math.round((count / props.stats.total_alumni) * 100)
}

const getYearBarHeight = (count: number): number => {
  if (!props.stats?.graduation_years) return 0
  
  const maxCount = Math.max(...Object.values(props.stats.graduation_years))
  return Math.round((count / maxCount) * 100)
}

const loadSuggestedGroups = async () => {
  try {
    const response = await fetch(`/api/regions/${encodeURIComponent(props.region)}/groups`)
    const groups = await response.json()
    suggestedGroups.value = groups.slice(0, 3)
  } catch (error) {
    console.error('Error loading suggested groups:', error)
    
    // Fallback suggestions
    suggestedGroups.value = [
      {
        name: `${props.region} Tech Professionals`,
        type: 'Industry',
        member_count: Math.floor(Math.random() * 50) + 10,
        industries: ['Technology', 'Software']
      },
      {
        name: `${props.region} Alumni Network`,
        type: 'Regional',
        member_count: Math.floor(Math.random() * 100) + 20
      }
    ]
  }
}

const exploreGroup = (group: GroupSuggestion) => {
  emit('exploreGroup', group)
}

watch(() => props.region, () => {
  if (props.region) {
    loadSuggestedGroups()
  }
}, { immediate: true })

onMounted(() => {
  if (props.region) {
    loadSuggestedGroups()
  }
})
</script>

<style scoped>
.regional-insights-panel {
  position: fixed;
  top: 20px;
  right: 20px;
  width: 350px;
  max-height: calc(100vh - 40px);
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: linear-gradient(135deg, #007bff, #0056b3);
  color: white;
}

.panel-title {
  margin: 0;
  font-size: 1.1em;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  opacity: 0.8;
}

.btn-close:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.3);
}

.panel-content {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
  color: #6c757d;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.stats-overview {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}

.stat-value {
  font-size: 1.5em;
  font-weight: bold;
  color: #007bff;
}

.stat-label {
  font-size: 0.8em;
  color: #6c757d;
  margin-top: 0.25rem;
}

.insight-section {
  margin-bottom: 1.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  font-size: 0.95em;
  color: #495057;
}

.industry-chart,
.companies-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.industry-bar,
.company-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.industry-info,
.company-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85em;
}

.industry-name,
.company-name {
  font-weight: 500;
  color: #495057;
}

.industry-count,
.company-count {
  color: #6c757d;
  font-size: 0.8em;
}

.industry-progress,
.company-progress {
  height: 6px;
  background: #e9ecef;
  border-radius: 3px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #007bff, #0056b3);
  transition: width 0.3s ease;
}

.year-distribution {
  margin-top: 0.5rem;
}

.year-chart {
  display: flex;
  align-items: end;
  gap: 4px;
  height: 80px;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 6px;
}

.year-bar {
  flex: 1;
  background: linear-gradient(to top, #007bff, #0056b3);
  border-radius: 2px 2px 0 0;
  min-height: 10px;
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
}

.year-bar:hover {
  opacity: 0.8;
  transform: scaleY(1.1);
}

.year-label {
  position: absolute;
  bottom: -20px;
  left: 50%;
  transform: translateX(-50%) rotate(-45deg);
  font-size: 0.7em;
  color: #6c757d;
  white-space: nowrap;
}

.groups-suggestions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.group-suggestion {
  padding: 0.75rem;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  background: #f8f9fa;
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.group-name {
  margin: 0;
  font-size: 0.9em;
  color: #495057;
}

.group-type {
  font-size: 0.7em;
  background: #007bff;
  color: white;
  padding: 0.2rem 0.4rem;
  border-radius: 10px;
}

.group-details {
  font-size: 0.8em;
  color: #6c757d;
  margin: 0 0 0.5rem 0;
}

.opportunities-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.opportunity-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  background: #f8f9fa;
}

.opportunity-item i {
  font-size: 1.2em;
}

.opportunity-content {
  flex: 1;
}

.opportunity-content h6 {
  margin: 0 0 0.25rem 0;
  font-size: 0.9em;
  color: #495057;
}

.opportunity-content p {
  margin: 0;
  font-size: 0.8em;
  color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .regional-insights-panel {
    position: fixed;
    top: 0;
    right: 0;
    left: 0;
    bottom: 0;
    width: auto;
    max-height: none;
    border-radius: 0;
  }
  
  .stats-overview {
    grid-template-columns: 1fr;
  }
}

/* Custom scrollbar */
.panel-content::-webkit-scrollbar {
  width: 6px;
}

.panel-content::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.panel-content::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.panel-content::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>