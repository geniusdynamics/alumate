<template>
  <div class="job-card" :class="{ 'applied': job.has_applied }">
    <!-- Match Score Badge -->
    <div class="match-badge" :class="`match-${job.match_score?.level_color}`">
      <div class="match-percentage">{{ job.match_score?.percentage }}%</div>
      <div class="match-label">{{ job.match_score?.level }} Match</div>
    </div>

    <!-- Company Header -->
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
        <h3 class="job-title">{{ job.title }}</h3>
        <p class="company-name">{{ job.company.name }}</p>
      </div>
    </div>

    <!-- Job Details -->
    <div class="job-details">
      <div class="detail-item">
        <Icon name="location" class="detail-icon" />
        <span>{{ job.location }}</span>
        <span v-if="job.remote_allowed" class="remote-badge">Remote OK</span>
      </div>
      
      <div v-if="job.employment_type" class="detail-item">
        <Icon name="briefcase" class="detail-icon" />
        <span>{{ formatEmploymentType(job.employment_type) }}</span>
      </div>
      
      <div v-if="job.salary_range" class="detail-item">
        <Icon name="currency" class="detail-icon" />
        <span>{{ job.salary_range }}</span>
      </div>
    </div>

    <!-- Match Reasons -->
    <div v-if="job.match_score?.top_reasons" class="match-reasons">
      <h4 class="reasons-title">Why this matches:</h4>
      <ul class="reasons-list">
        <li
          v-for="reason in job.match_score.top_reasons.slice(0, 2)"
          :key="reason"
          class="reason-item"
        >
          {{ reason }}
        </li>
      </ul>
    </div>

    <!-- Connection Insights -->
    <div v-if="job.match_score?.mutual_connections_count > 0" class="connections-info">
      <Icon name="users" class="connection-icon" />
      <span class="connection-text">
        {{ job.match_score.mutual_connections_count }} mutual connection{{ job.match_score.mutual_connections_count > 1 ? 's' : '' }}
      </span>
    </div>

    <!-- Posted Date -->
    <div class="posted-date">
      Posted {{ formatDate(job.posted_at) }}
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <button
        @click="$emit('view-details', job)"
        class="btn btn-secondary"
      >
        View Details
      </button>
      
      <button
        v-if="!job.has_applied"
        @click="$emit('apply', job)"
        class="btn btn-primary"
      >
        Apply Now
      </button>
      
      <div v-else class="applied-status">
        <Icon name="check" class="applied-icon" />
        Applied
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  job: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['view-details', 'apply'])

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

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else if (diffDays < 30) {
    const weeks = Math.floor(diffDays / 7)
    return `${weeks} week${weeks > 1 ? 's' : ''} ago`
  } else {
    return date.toLocaleDateString()
  }
}
</script>

<style scoped>
.job-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  position: relative;
  transition: all 0.2s ease;
  cursor: pointer;
}

.job-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
  transform: translateY(-2px);
}

.job-card.applied {
  border-color: #10b981;
  background: #f0fdf4;
}

.match-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  text-align: center;
  font-size: 0.75rem;
  font-weight: 600;
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

.match-percentage {
  font-size: 1rem;
  font-weight: 700;
}

.match-label {
  font-size: 0.625rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.company-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
  padding-right: 5rem; /* Space for match badge */
}

.company-logo {
  flex-shrink: 0;
}

.logo-image {
  width: 48px;
  height: 48px;
  border-radius: 0.5rem;
  object-fit: cover;
}

.logo-placeholder {
  width: 48px;
  height: 48px;
  background: #f3f4f6;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 600;
  color: #6b7280;
}

.company-info {
  flex: 1;
  min-width: 0;
}

.job-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
  line-height: 1.4;
}

.company-name {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

.job-details {
  margin-bottom: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.detail-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.remote-badge {
  background: #dbeafe;
  color: #1d4ed8;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  margin-left: 0.5rem;
}

.match-reasons {
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 0.5rem;
}

.reasons-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.5rem 0;
}

.reasons-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.reason-item {
  font-size: 0.8125rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
  position: relative;
  padding-left: 1rem;
}

.reason-item::before {
  content: '•';
  color: #3b82f6;
  position: absolute;
  left: 0;
}

.connections-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: #eff6ff;
  border-radius: 0.5rem;
}

.connection-icon {
  width: 16px;
  height: 16px;
  color: #3b82f6;
}

.connection-text {
  font-size: 0.875rem;
  color: #1e40af;
  font-weight: 500;
}

.posted-date {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-bottom: 1rem;
}

.action-buttons {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
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
  border-color: #9ca3af;
}

.applied-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #10b981;
  font-size: 0.875rem;
  font-weight: 500;
  flex: 1;
  justify-content: center;
}

.applied-icon {
  width: 16px;
  height: 16px;
}

@media (max-width: 640px) {
  .job-card {
    padding: 1rem;
  }
  
  .company-header {
    padding-right: 4rem;
  }
  
  .match-badge {
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.375rem 0.5rem;
  }
  
  .match-percentage {
    font-size: 0.875rem;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .btn {
    flex: none;
    width: 100%;
  }
}
</style>