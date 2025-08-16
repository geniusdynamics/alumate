<template>
  <div class="landing-page-dashboard">
    <div class="header">
      <h1>Landing Page Builder</h1>
      <div class="header-actions">
        <router-link to="/admin/landing-pages/create" class="btn btn-primary">
          Create Landing Page
        </router-link>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Total Pages</h3>
        <div class="stat-value">{{ stats.total_pages }}</div>
      </div>
      <div class="stat-card">
        <h3>Published</h3>
        <div class="stat-value">{{ stats.published_pages }}</div>
      </div>
      <div class="stat-card">
        <h3>Drafts</h3>
        <div class="stat-value">{{ stats.draft_pages }}</div>
      </div>
      <div class="stat-card">
        <h3>Total Submissions</h3>
        <div class="stat-value">{{ stats.total_submissions }}</div>
      </div>
    </div>

    <!-- Landing Pages Table -->
    <div class="landing-pages-table">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Audience</th>
            <th>Campaign</th>
            <th>Status</th>
            <th>Views</th>
            <th>Submissions</th>
            <th>Conversion Rate</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="page in landingPages.data" :key="page.id">
            <td>
              <div class="page-info">
                <strong>{{ page.name }}</strong>
                <small>{{ page.title }}</small>
              </div>
            </td>
            <td>
              <span class="audience-badge" :class="page.target_audience">
                {{ formatAudience(page.target_audience) }}
              </span>
            </td>
            <td>
              <div class="campaign-info">
                <span class="campaign-type">{{ formatCampaignType(page.campaign_type) }}</span>
                <small v-if="page.campaign_name">{{ page.campaign_name }}</small>
              </div>
            </td>
            <td>
              <span class="status-badge" :class="page.status">
                {{ page.status }}
              </span>
            </td>
            <td>{{ page.total_views || 0 }}</td>
            <td>{{ page.total_submissions || 0 }}</td>
            <td>{{ formatPercentage(page.conversion_rate) }}</td>
            <td>{{ formatDate(page.created_at) }}</td>
            <td>
              <div class="action-buttons">
                <router-link 
                  :to="`/admin/landing-pages/${page.id}`" 
                  class="btn btn-sm btn-secondary"
                >
                  View
                </router-link>
                <router-link 
                  :to="`/admin/landing-pages/${page.id}/edit`" 
                  class="btn btn-sm btn-primary"
                >
                  Edit
                </router-link>
                <button 
                  v-if="page.status === 'draft'" 
                  @click="publishPage(page.id)" 
                  class="btn btn-sm btn-success"
                >
                  Publish
                </button>
                <button 
                  v-else 
                  @click="unpublishPage(page.id)" 
                  class="btn btn-sm btn-warning"
                >
                  Unpublish
                </button>
                <button 
                  @click="duplicatePage(page.id)" 
                  class="btn btn-sm btn-secondary"
                >
                  Duplicate
                </button>
                <button 
                  @click="deletePage(page.id)" 
                  class="btn btn-sm btn-danger"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination">
        <button 
          v-for="link in landingPages.links" 
          :key="link.label"
          @click="changePage(link.url)"
          :disabled="!link.url"
          :class="{ active: link.active }"
          class="page-btn"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'

interface LandingPage {
  id: number
  name: string
  title: string
  target_audience: string
  campaign_type: string
  campaign_name?: string
  status: string
  total_views?: number
  total_submissions?: number
  conversion_rate?: number
  created_at: string
}

interface Stats {
  total_pages: number
  published_pages: number
  draft_pages: number
  total_submissions: number
}

const props = defineProps<{
  landingPages: {
    data: LandingPage[]
    links: Array<{ label: string; url: string | null; active: boolean }>
  }
  stats: Stats
}>()

const formatAudience = (audience: string) => {
  const audiences = {
    institution: 'Institution',
    employer: 'Employer',
    partner: 'Partner',
    alumni: 'Alumni',
    general: 'General'
  }
  return audiences[audience] || audience
}

const formatCampaignType = (type: string) => {
  const types = {
    onboarding: 'Onboarding',
    marketing: 'Marketing',
    event: 'Event',
    product_launch: 'Product Launch',
    trial: 'Trial',
    demo: 'Demo'
  }
  return types[type] || type
}

const formatPercentage = (value?: number) => {
  return value ? `${value.toFixed(1)}%` : '0%'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const changePage = (url: string | null) => {
  if (url) {
    router.get(url)
  }
}

const publishPage = async (pageId: number) => {
  try {
    await fetch(`/admin/landing-pages/${pageId}/publish`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    router.reload()
  } catch (error) {
    console.error('Failed to publish page:', error)
  }
}

const unpublishPage = async (pageId: number) => {
  try {
    await fetch(`/admin/landing-pages/${pageId}/unpublish`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    router.reload()
  } catch (error) {
    console.error('Failed to unpublish page:', error)
  }
}

const duplicatePage = async (pageId: number) => {
  try {
    await fetch(`/admin/landing-pages/${pageId}/duplicate`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    router.reload()
  } catch (error) {
    console.error('Failed to duplicate page:', error)
  }
}

const deletePage = async (pageId: number) => {
  if (!confirm('Are you sure you want to delete this landing page?')) return
  
  try {
    await fetch(`/admin/landing-pages/${pageId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    router.reload()
  } catch (error) {
    console.error('Failed to delete page:', error)
  }
}
</script>

<style scoped>
.landing-page-dashboard {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.stat-card h3 {
  margin: 0 0 10px 0;
  font-size: 14px;
  color: #666;
  text-transform: uppercase;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  color: #333;
}

.landing-pages-table {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.landing-pages-table table {
  width: 100%;
  border-collapse: collapse;
}

.landing-pages-table th,
.landing-pages-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #dee2e6;
}

.landing-pages-table th {
  background: #f8f9fa;
  font-weight: bold;
  color: #495057;
}

.page-info strong {
  display: block;
}

.page-info small {
  color: #666;
  font-size: 12px;
}

.audience-badge, .status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.audience-badge.institution { background: #e3f2fd; color: #1976d2; }
.audience-badge.employer { background: #f3e5f5; color: #7b1fa2; }
.audience-badge.partner { background: #e8f5e8; color: #388e3c; }
.audience-badge.alumni { background: #fff3e0; color: #f57c00; }
.audience-badge.general { background: #f5f5f5; color: #666; }

.status-badge.draft { background: #f5f5f5; color: #666; }
.status-badge.published { background: #e8f5e8; color: #388e3c; }
.status-badge.archived { background: #ffebee; color: #d32f2f; }

.campaign-info .campaign-type {
  display: block;
  font-weight: 500;
}

.campaign-info small {
  color: #666;
  font-size: 12px;
}

.action-buttons {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-warning { background: #ffc107; color: #212529; }
.btn-danger { background: #dc3545; color: white; }

.btn-sm {
  padding: 4px 8px;
  font-size: 11px;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 5px;
  padding: 20px;
}

.page-btn {
  padding: 8px 12px;
  border: 1px solid #dee2e6;
  background: white;
  cursor: pointer;
  border-radius: 4px;
}

.page-btn:hover:not(:disabled) {
  background: #e9ecef;
}

.page-btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>