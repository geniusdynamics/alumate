<template>
  <div v-if="show" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h2 class="modal-title">{{ job.title }}</h2>
        <button @click="$emit('close')" class="close-button">
          <Icon name="x" />
        </button>
      </div>

      <div class="modal-content">
        <!-- Company Info -->
        <div class="company-section">
          <div class="company-header">
            <div class="company-logo">
              <img
                v-if="job.company.logo_url"
                :src="job.company.logo_url"
                :alt="job.company.name"
                class="logo-image"
              />
              <div v-else class="logo-placeholder">
                {{ job.company.name.charAt(0) }}
              </div>
            </div>
            <div class="company-info">
              <h3 class="company-name">{{ job.company.name }}</h3>
              <p v-if="job.company.industry" class="company-industry">
                {{ job.company.industry }}
              </p>
              <p v-if="job.company.size" class="company-size">
                {{ formatCompanySize(job.company.size) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Job Details -->
        <div class="job-details-section">
          <div class="details-grid">
            <div class="detail-item">
              <Icon name="location" class="detail-icon" />
              <div>
                <span class="detail-label">Location</span>
                <span class="detail-value">{{ job.location }}</span>
                <span v-if="job.remote_allowed" class="remote-badge">Remote OK</span>
              </div>
            </div>

            <div v-if="job.employment_type" class="detail-item">
              <Icon name="briefcase" class="detail-icon" />
              <div>
                <span class="detail-label">Employment Type</span>
                <span class="detail-value">{{ formatEmploymentType(job.employment_type) }}</span>
              </div>
            </div>

            <div v-if="job.experience_level" class="detail-item">
              <Icon name="trending-up" class="detail-icon" />
              <div>
                <span class="detail-label">Experience Level</span>
                <span class="detail-value">{{ formatExperienceLevel(job.experience_level) }}</span>
              </div>
            </div>

            <div v-if="job.salary_range" class="detail-item">
              <Icon name="currency" class="detail-icon" />
              <div>
                <span class="detail-label">Salary Range</span>
                <span class="detail-value">{{ job.salary_range }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Match Analysis -->
        <div v-if="job.match_analysis" class="match-section">
          <h4 class="section-title">Match Analysis</h4>
          <div class="match-overview">
            <div class="match-score" :class="`match-${job.match_analysis.level_color}`">
              <div class="score-number">{{ job.match_analysis.percentage }}%</div>
              <div class="score-label">{{ job.match_analysis.level }} Match</div>
            </div>
            <div class="match-breakdown">
              <div class="breakdown-item">
                <span class="breakdown-label">Network Connections</span>
                <div class="breakdown-bar">
                  <div 
                    class="breakdown-fill" 
                    :style="{ width: `${job.match_analysis.breakdown.connections}%` }"
                  ></div>
                </div>
                <span class="breakdown-value">{{ job.match_analysis.breakdown.connections }}%</span>
              </div>
              <div class="breakdown-item">
                <span class="breakdown-label">Skills Match</span>
                <div class="breakdown-bar">
                  <div 
                    class="breakdown-fill" 
                    :style="{ width: `${job.match_analysis.breakdown.skills}%` }"
                  ></div>
                </div>
                <span class="breakdown-value">{{ job.match_analysis.breakdown.skills }}%</span>
              </div>
              <div class="breakdown-item">
                <span class="breakdown-label">Education</span>
                <div class="breakdown-bar">
                  <div 
                    class="breakdown-fill" 
                    :style="{ width: `${job.match_analysis.breakdown.education}%` }"
                  ></div>
                </div>
                <span class="breakdown-value">{{ job.match_analysis.breakdown.education }}%</span>
              </div>
              <div class="breakdown-item">
                <span class="breakdown-label">Alumni Circles</span>
                <div class="breakdown-bar">
                  <div 
                    class="breakdown-fill" 
                    :style="{ width: `${job.match_analysis.breakdown.circles}%` }"
                  ></div>
                </div>
                <span class="breakdown-value">{{ job.match_analysis.breakdown.circles }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Connection Insights -->
        <div v-if="job.mutual_connections && job.mutual_connections.length > 0" class="connections-section">
          <ConnectionInsights
            :connections="job.mutual_connections"
            :company-name="job.company.name"
            :job-title="job.title"
            @request-introduction="handleIntroductionRequest"
          />
        </div>

        <!-- Job Description -->
        <div class="description-section">
          <h4 class="section-title">Job Description</h4>
          <div class="description-content" v-html="formatDescription(job.description)"></div>
        </div>

        <!-- Requirements -->
        <div v-if="job.requirements && job.requirements.length > 0" class="requirements-section">
          <h4 class="section-title">Requirements</h4>
          <ul class="requirements-list">
            <li v-for="requirement in job.requirements" :key="requirement" class="requirement-item">
              {{ requirement }}
            </li>
          </ul>
        </div>

        <!-- Skills -->
        <div v-if="job.skills_required && job.skills_required.length > 0" class="skills-section">
          <h4 class="section-title">Required Skills</h4>
          <div class="skills-list">
            <span
              v-for="skill in job.skills_required"
              :key="skill"
              class="skill-tag"
            >
              {{ skill }}
            </span>
          </div>
        </div>

        <!-- Application Status -->
        <div v-if="job.application_status" class="application-status-section">
          <div class="status-card" :class="`status-${job.application_status.status_color}`">
            <Icon name="check-circle" class="status-icon" />
            <div class="status-info">
              <h5 class="status-title">{{ job.application_status.status_label }}</h5>
              <p class="status-date">Applied {{ formatDate(job.application_status.applied_at) }}</p>
              <p v-if="job.application_status.introduction_requested" class="status-note">
                Introduction requested through your network
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Actions -->
      <div class="modal-actions">
        <button @click="$emit('close')" class="btn btn-secondary">
          Close
        </button>
        <button
          v-if="!job.application_status"
          @click="$emit('apply', job)"
          class="btn btn-primary"
        >
          Apply for this Job
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Icon from './Icon.vue'
import ConnectionInsights from './ConnectionInsights.vue'

const props = defineProps({
  job: {
    type: Object,
    required: true
  },
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'apply', 'request-introduction'])

const closeModal = () => {
  emit('close')
}

const handleIntroductionRequest = (contactId, message) => {
  emit('request-introduction', contactId, message)
}

const formatEmploymentType = (type) => {
  const types = {
    'full_time': 'Full-time',
    'part_time': 'Part-time',
    'contract': 'Contract',
    'internship': 'Internship',
    'temporary': 'Temporary'
  }
  return types[type] || type
}

const formatExperienceLevel = (level) => {
  const levels = {
    'entry': 'Entry Level',
    'mid': 'Mid Level',
    'senior': 'Senior Level',
    'executive': 'Executive Level'
  }
  return levels[level] || level
}

const formatCompanySize = (size) => {
  const sizes = {
    'startup': '1-10 employees',
    'small': '11-50 employees',
    'medium': '51-200 employees',
    'large': '201-1000 employees',
    'enterprise': '1000+ employees'
  }
  return sizes[size] || size
}

const formatDescription = (description) => {
  // Convert line breaks to HTML and sanitize
  return description.replace(/\n/g, '<br>')
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString()
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 0.75rem;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.close-button {
  background: none;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  color: #6b7280;
  border-radius: 0.375rem;
  transition: background-color 0.2s;
}

.close-button:hover {
  background: #f3f4f6;
}

.modal-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.company-section {
  margin-bottom: 2rem;
}

.company-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.company-logo .logo-image {
  width: 60px;
  height: 60px;
  border-radius: 0.5rem;
  object-fit: cover;
}

.company-logo .logo-placeholder {
  width: 60px;
  height: 60px;
  background: #f3f4f6;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 600;
  color: #6b7280;
}

.company-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.company-industry,
.company-size {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

.job-details-section {
  margin-bottom: 2rem;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.detail-icon {
  width: 20px;
  height: 20px;
  color: #3b82f6;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.detail-label {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.detail-value {
  display: block;
  font-weight: 500;
  color: #111827;
}

.remote-badge {
  display: inline-block;
  background: #dbeafe;
  color: #1d4ed8;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  margin-left: 0.5rem;
}

.match-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 0.75rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 1rem 0;
}

.match-overview {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

.match-score {
  text-align: center;
  padding: 1rem;
  border-radius: 0.5rem;
  min-width: 120px;
}

.match-green {
  background: #dcfce7;
  color: #166534;
}

.match-yellow {
  background: #fef3c7;
  color: #92400e;
}

.match-red {
  background: #fee2e2;
  color: #991b1b;
}

.score-number {
  font-size: 2rem;
  font-weight: 700;
}

.score-label {
  font-size: 0.875rem;
  font-weight: 500;
}

.match-breakdown {
  flex: 1;
}

.breakdown-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.breakdown-label {
  font-size: 0.875rem;
  color: #374151;
  min-width: 120px;
}

.breakdown-bar {
  flex: 1;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.breakdown-fill {
  height: 100%;
  background: #3b82f6;
  transition: width 0.3s ease;
}

.breakdown-value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #111827;
  min-width: 40px;
  text-align: right;
}

.connections-section {
  margin-bottom: 2rem;
}

.description-section,
.requirements-section,
.skills-section {
  margin-bottom: 2rem;
}

.description-content {
  color: #374151;
  line-height: 1.6;
}

.requirements-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.requirement-item {
  padding: 0.5rem 0;
  border-bottom: 1px solid #f3f4f6;
  position: relative;
  padding-left: 1.5rem;
}

.requirement-item::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: #10b981;
  font-weight: 600;
}

.skills-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.skill-tag {
  background: #e0e7ff;
  color: #3730a3;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.application-status-section {
  margin-bottom: 2rem;
}

.status-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 0.5rem;
  border: 1px solid;
}

.status-green {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #166534;
}

.status-icon {
  width: 24px;
  height: 24px;
}

.status-title {
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.status-date,
.status-note {
  margin: 0;
  font-size: 0.875rem;
  opacity: 0.8;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 0.375rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #f9fafb;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #f3f4f6;
}

@media (max-width: 768px) {
  .modal-overlay {
    padding: 0.5rem;
  }
  
  .modal-container {
    max-height: 95vh;
  }
  
  .modal-header,
  .modal-content,
  .modal-actions {
    padding: 1rem;
  }
  
  .match-overview {
    flex-direction: column;
    gap: 1rem;
  }
  
  .details-grid {
    grid-template-columns: 1fr;
  }
  
  .breakdown-item {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }
  
  .breakdown-label {
    min-width: auto;
  }
  
  .breakdown-value {
    text-align: left;
  }
}
</style>