<template>
  <div class="lead-management">
    <div class="header">
      <h1>Lead Management</h1>
      <div class="header-actions">
        <button @click="showCreateModal = true" class="btn btn-primary">
          Add Lead
        </button>
        <button @click="bulkSync" class="btn btn-secondary">
          Sync to CRM
        </button>
        <button @click="exportLeads" class="btn btn-secondary">
          Export
        </button>
      </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="analytics-section">
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Leads</h3>
          <div class="stat-value">{{ analytics.total_leads }}</div>
        </div>
        <div class="stat-card">
          <h3>Qualified Rate</h3>
          <div class="stat-value">{{ Math.round(analytics.qualified_rate) }}%</div>
        </div>
        <div class="stat-card">
          <h3>Conversion Rate</h3>
          <div class="stat-value">{{ Math.round(analytics.conversion_rate) }}%</div>
        </div>
        <div class="stat-card">
          <h3>Hot Leads</h3>
          <div class="stat-value">{{ analytics.hot_leads }}</div>
        </div>
      </div>

      <!-- Pipeline Chart -->
      <div class="pipeline-section">
        <h3>Lead Pipeline</h3>
        <div class="pipeline-stages">
          <div 
            v-for="stage in pipeline" 
            :key="stage.status" 
            class="pipeline-stage"
            :class="stage.status"
          >
            <div class="stage-header">
              <span class="stage-name">{{ formatStatus(stage.status) }}</span>
              <span class="stage-count">{{ stage.count }}</span>
            </div>
            <div class="stage-bar">
              <div 
                class="stage-fill" 
                :style="{ width: stage.percentage + '%' }"
              ></div>
            </div>
            <div class="stage-score">Avg Score: {{ stage.avg_score }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Needs Attention Alert -->
    <div v-if="needsAttention.length > 0" class="needs-attention-alert">
      <h3>Leads Needing Attention ({{ needsAttention.length }})</h3>
      <div class="attention-items">
        <div v-for="lead in needsAttention" :key="lead.id" class="attention-item">
          <div class="lead-info">
            <strong>{{ lead.full_name }}</strong>
            <span class="company">{{ lead.company }}</span>
            <span class="priority-badge" :class="lead.priority">{{ lead.priority }}</span>
          </div>
          <div class="lead-actions">
            <button @click="viewLead(lead.id)" class="btn btn-sm btn-primary">
              View
            </button>
            <button @click="contactLead(lead.id)" class="btn btn-sm btn-success">
              Contact
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters">
      <select v-model="filters.status" @change="applyFilters">
        <option value="">All Status</option>
        <option value="new">New</option>
        <option value="contacted">Contacted</option>
        <option value="qualified">Qualified</option>
        <option value="proposal">Proposal</option>
        <option value="negotiation">Negotiation</option>
        <option value="closed_won">Closed Won</option>
        <option value="closed_lost">Closed Lost</option>
      </select>
      
      <select v-model="filters.type" @change="applyFilters">
        <option value="">All Types</option>
        <option value="individual">Individual</option>
        <option value="institutional">Institutional</option>
        <option value="enterprise">Enterprise</option>
      </select>
      
      <select v-model="filters.priority" @change="applyFilters">
        <option value="">All Priorities</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
      
      <select v-model="filters.assigned_to" @change="applyFilters">
        <option value="">All Assigned</option>
        <option value="unassigned">Unassigned</option>
        <!-- Add users dynamically -->
      </select>
    </div>

    <!-- Leads Table -->
    <div class="leads-table">
      <table>
        <thead>
          <tr>
            <th>
              <input 
                type="checkbox" 
                v-model="selectAll" 
                @change="toggleSelectAll"
              />
            </th>
            <th>Name</th>
            <th>Company</th>
            <th>Email</th>
            <th>Type</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Score</th>
            <th>Assigned To</th>
            <th>Last Contact</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lead in leads.data" :key="lead.id">
            <td>
              <input 
                type="checkbox" 
                v-model="selectedLeads" 
                :value="lead.id"
              />
            </td>
            <td>
              <div class="lead-name">
                <strong>{{ lead.full_name }}</strong>
                <small v-if="lead.job_title">{{ lead.job_title }}</small>
              </div>
            </td>
            <td>{{ lead.company || '-' }}</td>
            <td>
              <a :href="`mailto:${lead.email}`">{{ lead.email }}</a>
            </td>
            <td>
              <span class="type-badge" :class="lead.lead_type">
                {{ lead.lead_type }}
              </span>
            </td>
            <td>
              <span class="status-badge" :class="lead.status">
                {{ formatStatus(lead.status) }}
              </span>
            </td>
            <td>
              <span class="priority-badge" :class="lead.priority">
                {{ lead.priority }}
              </span>
            </td>
            <td>
              <div class="score-display">
                <span class="score-value">{{ lead.score }}</span>
                <div class="score-bar">
                  <div 
                    class="score-fill" 
                    :style="{ width: lead.score + '%' }"
                  ></div>
                </div>
              </div>
            </td>
            <td>{{ lead.assigned_user?.name || 'Unassigned' }}</td>
            <td>{{ formatDate(lead.last_contacted_at) || 'Never' }}</td>
            <td>
              <div class="action-buttons">
                <button @click="viewLead(lead.id)" class="btn btn-sm btn-secondary">
                  View
                </button>
                <button @click="editLead(lead)" class="btn btn-sm btn-primary">
                  Edit
                </button>
                <button @click="qualifyLead(lead)" class="btn btn-sm btn-success">
                  Qualify
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination">
        <button 
          v-for="link in leads.links" 
          :key="link.label"
          @click="changePage(link.url)"
          :disabled="!link.url"
          :class="{ active: link.active }"
          class="page-btn"
          v-html="link.label"
        ></button>
      </div>
    </div>

    <!-- Create Lead Modal -->
    <div v-if="showCreateModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Create New Lead</h2>
          <button @click="showCreateModal = false" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createLead">
            <div class="form-row">
              <div class="form-group">
                <label>First Name *</label>
                <input 
                  v-model="createForm.first_name" 
                  type="text" 
                  required 
                />
              </div>
              <div class="form-group">
                <label>Last Name *</label>
                <input 
                  v-model="createForm.last_name" 
                  type="text" 
                  required 
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label>Email *</label>
                <input 
                  v-model="createForm.email" 
                  type="email" 
                  required 
                />
              </div>
              <div class="form-group">
                <label>Phone</label>
                <input 
                  v-model="createForm.phone" 
                  type="tel" 
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label>Company</label>
                <input 
                  v-model="createForm.company" 
                  type="text" 
                />
              </div>
              <div class="form-group">
                <label>Job Title</label>
                <input 
                  v-model="createForm.job_title" 
                  type="text" 
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label>Lead Type *</label>
                <select v-model="createForm.lead_type" required>
                  <option value="individual">Individual</option>
                  <option value="institutional">Institutional</option>
                  <option value="enterprise">Enterprise</option>
                </select>
              </div>
              <div class="form-group">
                <label>Source *</label>
                <select v-model="createForm.source" required>
                  <option value="homepage">Homepage</option>
                  <option value="demo_request">Demo Request</option>
                  <option value="trial_signup">Trial Signup</option>
                  <option value="contact_form">Contact Form</option>
                  <option value="referral">Referral</option>
                  <option value="organic">Organic</option>
                  <option value="paid_ads">Paid Ads</option>
                </select>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn btn-primary">Create Lead</button>
              <button type="button" @click="showCreateModal = false" class="btn btn-secondary">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Lead Modal -->
    <div v-if="showEditModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Edit Lead</h2>
          <button @click="showEditModal = false" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updateLead">
            <div class="form-row">
              <div class="form-group">
                <label>First Name</label>
                <input 
                  v-model="editForm.first_name" 
                  type="text" 
                />
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input 
                  v-model="editForm.last_name" 
                  type="text" 
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label>Status</label>
                <select v-model="editForm.status">
                  <option value="new">New</option>
                  <option value="contacted">Contacted</option>
                  <option value="qualified">Qualified</option>
                  <option value="proposal">Proposal</option>
                  <option value="negotiation">Negotiation</option>
                  <option value="closed_won">Closed Won</option>
                  <option value="closed_lost">Closed Lost</option>
                </select>
              </div>
              <div class="form-group">
                <label>Priority</label>
                <select v-model="editForm.priority">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label>Notes</label>
              <textarea 
                v-model="editForm.notes" 
                rows="4"
              ></textarea>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn btn-primary">Update Lead</button>
              <button type="button" @click="showEditModal = false" class="btn btn-secondary">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Qualify Lead Modal -->
    <div v-if="showQualifyModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Qualify Lead</h2>
          <button @click="showQualifyModal = false" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitQualification">
            <div class="form-group">
              <label>Budget Range</label>
              <select v-model="qualifyForm.budget">
                <option value="under_1k">Under $1,000</option>
                <option value="1k_5k">$1,000 - $5,000</option>
                <option value="5k_10k">$5,000 - $10,000</option>
                <option value="10k_50k">$10,000 - $50,000</option>
                <option value="over_50k">Over $50,000</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Timeline</label>
              <select v-model="qualifyForm.timeline">
                <option value="immediate">Immediate</option>
                <option value="1_month">Within 1 month</option>
                <option value="3_months">Within 3 months</option>
                <option value="6_months">Within 6 months</option>
                <option value="future">Future consideration</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Decision Maker</label>
              <select v-model="qualifyForm.decision_maker">
                <option value="yes">Yes</option>
                <option value="no">No</option>
                <option value="influencer">Influencer</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Qualification Notes</label>
              <textarea 
                v-model="qualifyForm.notes" 
                rows="4"
                placeholder="Additional qualification details..."
              ></textarea>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn btn-success">Qualify Lead</button>
              <button type="button" @click="showQualifyModal = false" class="btn btn-secondary">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

interface Lead {
  id: number
  first_name: string
  last_name: string
  email: string
  phone?: string
  company?: string
  job_title?: string
  lead_type: string
  status: string
  priority: string
  score: number
  assigned_to?: number
  last_contacted_at?: string
  full_name: string
  assigned_user?: { name: string }
}

interface Analytics {
  total_leads: number
  qualified_rate: number
  conversion_rate: number
  hot_leads: number
}

interface PipelineStage {
  status: string
  count: number
  avg_score: number
  percentage: number
}

const props = defineProps<{
  leads: {
    data: Lead[]
    links: Array<{ label: string; url: string | null; active: boolean }>
  }
  analytics: Analytics
  pipeline: PipelineStage[]
  needsAttention: Lead[]
  filters: Record<string, string>
}>()

// Reactive state
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showQualifyModal = ref(false)
const selectedLeads = ref<number[]>([])
const selectAll = ref(false)

const filters = ref({
  status: '',
  type: '',
  priority: '',
  assigned_to: '',
})

const createForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  company: '',
  job_title: '',
  lead_type: 'individual',
  source: 'homepage',
})

const editForm = ref({
  id: null as number | null,
  first_name: '',
  last_name: '',
  status: '',
  priority: '',
  notes: '',
})

const qualifyForm = ref({
  lead_id: null as number | null,
  budget: '',
  timeline: '',
  decision_maker: '',
  notes: '',
})

// Computed
const pipeline = computed(() => {
  const total = props.pipeline.reduce((sum, stage) => sum + stage.count, 0)
  return props.pipeline.map(stage => ({
    ...stage,
    percentage: total > 0 ? (stage.count / total) * 100 : 0
  }))
})

// Methods
const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedLeads.value = props.leads.data.map(lead => lead.id)
  } else {
    selectedLeads.value = []
  }
}

const applyFilters = () => {
  router.get('/admin/lead-management', filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

const changePage = (url: string | null) => {
  if (url) {
    router.get(url, {}, {
      preserveState: true,
      preserveScroll: true,
    })
  }
}

const viewLead = (leadId: number) => {
  router.get(`/admin/lead-management/${leadId}`)
}

const editLead = (lead: Lead) => {
  editForm.value = {
    id: lead.id,
    first_name: lead.first_name,
    last_name: lead.last_name,
    status: lead.status,
    priority: lead.priority,
    notes: '',
  }
  showEditModal.value = true
}

const qualifyLead = (lead: Lead) => {
  qualifyForm.value.lead_id = lead.id
  showQualifyModal.value = true
}

const contactLead = (leadId: number) => {
  // Navigate to lead detail page or open contact modal
  viewLead(leadId)
}

const createLead = async () => {
  try {
    const response = await fetch('/admin/lead-management', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(createForm.value)
    })

    const data = await response.json()
    
    if (data.success) {
      showCreateModal.value = false
      createForm.value = {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        company: '',
        job_title: '',
        lead_type: 'individual',
        source: 'homepage',
      }
      router.reload()
    } else {
      alert('Failed to create lead: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to create lead:', error)
    alert('Failed to create lead')
  }
}

const updateLead = async () => {
  if (!editForm.value.id) return

  try {
    const response = await fetch(`/admin/lead-management/${editForm.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(editForm.value)
    })

    const data = await response.json()
    
    if (data.success) {
      showEditModal.value = false
      router.reload()
    } else {
      alert('Failed to update lead: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to update lead:', error)
    alert('Failed to update lead')
  }
}

const submitQualification = async () => {
  if (!qualifyForm.value.lead_id) return

  try {
    const response = await fetch(`/admin/lead-management/${qualifyForm.value.lead_id}/qualify`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        qualification_data: {
          budget: qualifyForm.value.budget,
          timeline: qualifyForm.value.timeline,
          decision_maker: qualifyForm.value.decision_maker,
        },
        notes: qualifyForm.value.notes,
      })
    })

    const data = await response.json()
    
    if (data.success) {
      showQualifyModal.value = false
      qualifyForm.value = {
        lead_id: null,
        budget: '',
        timeline: '',
        decision_maker: '',
        notes: '',
      }
      router.reload()
    } else {
      alert('Failed to qualify lead: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to qualify lead:', error)
    alert('Failed to qualify lead')
  }
}

const bulkSync = async () => {
  if (selectedLeads.value.length === 0) {
    alert('Please select leads to sync')
    return
  }

  try {
    const response = await fetch('/admin/lead-management/bulk-sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        lead_ids: selectedLeads.value
      })
    })

    const data = await response.json()
    
    if (data.success) {
      alert(`Successfully synced ${data.results.total_leads} leads`)
      selectedLeads.value = []
      selectAll.value = false
    } else {
      alert('Failed to sync leads: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to sync leads:', error)
    alert('Failed to sync leads')
  }
}

const exportLeads = async () => {
  try {
    const response = await fetch('/admin/lead-management/export?' + new URLSearchParams(filters.value))
    const data = await response.json()
    
    if (data.success) {
      const blob = new Blob([JSON.stringify(data.leads, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `leads-export-${new Date().toISOString().split('T')[0]}.json`
      a.click()
      URL.revokeObjectURL(url)
    } else {
      alert('Failed to export leads: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to export leads:', error)
    alert('Failed to export leads')
  }
}

const formatStatus = (status: string) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString: string | null) => {
  if (!dateString) return null
  return new Date(dateString).toLocaleDateString()
}

onMounted(() => {
  // Calculate pipeline percentages
  const total = props.pipeline.reduce((sum, stage) => sum + stage.count, 0)
  props.pipeline.forEach(stage => {
    stage.percentage = total > 0 ? (stage.count / total) * 100 : 0
  })
})
</script>

<style scoped>
.lead-management {
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

.header-actions {
  display: flex;
  gap: 10px;
}

.analytics-section {
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

.pipeline-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pipeline-stages {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-top: 15px;
}

.pipeline-stage {
  text-align: center;
}

.stage-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.stage-name {
  font-weight: bold;
  font-size: 12px;
  text-transform: uppercase;
}

.stage-count {
  background: #007bff;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
}

.stage-bar {
  height: 8px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 5px;
}

.stage-fill {
  height: 100%;
  background: #007bff;
  transition: width 0.3s ease;
}

.stage-score {
  font-size: 11px;
  color: #666;
}

.needs-attention-alert {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 30px;
}

.attention-items {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 15px;
}

.attention-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  background: white;
  border-radius: 4px;
}

.lead-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.company {
  color: #666;
  font-size: 14px;
}

.priority-badge, .status-badge, .type-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.priority-badge.low { background: #d4edda; color: #155724; }
.priority-badge.medium { background: #fff3cd; color: #856404; }
.priority-badge.high { background: #f8d7da; color: #721c24; }
.priority-badge.urgent { background: #dc3545; color: white; }

.status-badge.new { background: #e2e3e5; color: #383d41; }
.status-badge.contacted { background: #d1ecf1; color: #0c5460; }
.status-badge.qualified { background: #d4edda; color: #155724; }
.status-badge.proposal { background: #fff3cd; color: #856404; }
.status-badge.negotiation { background: #f8d7da; color: #721c24; }
.status-badge.closed_won { background: #28a745; color: white; }
.status-badge.closed_lost { background: #6c757d; color: white; }

.type-badge.individual { background: #e3f2fd; color: #1976d2; }
.type-badge.institutional { background: #f3e5f5; color: #7b1fa2; }
.type-badge.enterprise { background: #e8f5e8; color: #388e3c; }

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.leads-table {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.leads-table table {
  width: 100%;
  border-collapse: collapse;
}

.leads-table th,
.leads-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #dee2e6;
}

.leads-table th {
  background: #f8f9fa;
  font-weight: bold;
  color: #495057;
}

.lead-name strong {
  display: block;
}

.lead-name small {
  color: #666;
  font-size: 12px;
}

.score-display {
  display: flex;
  align-items: center;
  gap: 8px;
}

.score-value {
  font-weight: bold;
  min-width: 30px;
}

.score-bar {
  flex: 1;
  height: 6px;
  background: #e9ecef;
  border-radius: 3px;
  overflow: hidden;
}

.score-fill {
  height: 100%;
  background: linear-gradient(90deg, #dc3545 0%, #ffc107 50%, #28a745 100%);
  transition: width 0.3s ease;
}

.action-buttons {
  display: flex;
  gap: 5px;
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

.modal {
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
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90%;
  overflow: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #dee2e6;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

.modal-body {
  padding: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-bottom: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 5px;
  font-weight: bold;
  color: #495057;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #dee2e6;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }

.btn-sm {
  padding: 4px 8px;
  font-size: 12px;
}

.btn:hover {
  opacity: 0.9;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
    gap: 15px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .pipeline-stages {
    grid-template-columns: 1fr;
  }

  .filters {
    flex-direction: column;
  }

  .leads-table {
    overflow-x: auto;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .attention-item {
    flex-direction: column;
    gap: 10px;
  }
}
</style>