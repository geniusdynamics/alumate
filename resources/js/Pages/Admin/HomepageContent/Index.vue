<template>
  <div class="homepage-content-admin">
    <div class="header">
      <h1>Homepage Content Management</h1>
      <div class="header-actions">
        <button @click="showPreview = !showPreview" class="btn btn-secondary">
          {{ showPreview ? 'Hide Preview' : 'Show Preview' }}
        </button>
        <button @click="exportContent" class="btn btn-secondary">
          Export Content
        </button>
        <button @click="showImportModal = true" class="btn btn-secondary">
          Import Content
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters">
      <select v-model="selectedAudience" @change="loadContent">
        <option value="both">All Audiences</option>
        <option value="individual">Individual Alumni</option>
        <option value="institutional">Institutional</option>
      </select>
      
      <select v-model="selectedSection" @change="filterContent">
        <option value="">All Sections</option>
        <option v-for="(label, key) in sections" :key="key" :value="key">
          {{ label }}
        </option>
      </select>
      
      <select v-model="statusFilter" @change="filterContent">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="pending">Pending Approval</option>
        <option value="approved">Approved</option>
        <option value="published">Published</option>
      </select>
    </div>

    <!-- Pending Approvals Alert -->
    <div v-if="pendingApprovals.length > 0" class="pending-approvals-alert">
      <h3>Pending Approvals ({{ pendingApprovals.length }})</h3>
      <div class="approval-items">
        <div v-for="approval in pendingApprovals" :key="approval.id" class="approval-item">
          <div class="approval-info">
            <strong>{{ approval.homepage_content.section }} - {{ approval.homepage_content.key }}</strong>
            <span class="audience-badge">{{ approval.homepage_content.audience }}</span>
            <p>{{ approval.request_notes }}</p>
            <small>Requested by {{ approval.requester.name }} on {{ formatDate(approval.requested_at) }}</small>
          </div>
          <div class="approval-actions">
            <button @click="approveContent(approval.homepage_content_id)" class="btn btn-success btn-sm">
              Approve
            </button>
            <button @click="rejectContent(approval.homepage_content_id)" class="btn btn-danger btn-sm">
              Reject
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Content List -->
    <div class="content-grid">
      <div v-for="item in filteredContent" :key="item.id" class="content-item">
        <div class="content-header">
          <h3>{{ sections[item.section] || item.section }} - {{ item.key }}</h3>
          <div class="content-meta">
            <span class="audience-badge" :class="item.audience">{{ item.audience }}</span>
            <span class="status-badge" :class="item.status">{{ item.status }}</span>
          </div>
        </div>
        
        <div class="content-body">
          <div v-if="editingItem === item.id" class="edit-form">
            <textarea 
              v-model="editForm.value" 
              class="content-textarea"
              rows="4"
            ></textarea>
            
            <div class="metadata-section" v-if="editForm.metadata">
              <h4>Metadata</h4>
              <div v-for="(value, key) in editForm.metadata" :key="key" class="metadata-item">
                <label>{{ key }}:</label>
                <input v-model="editForm.metadata[key]" type="text" />
              </div>
            </div>
            
            <div class="form-actions">
              <textarea 
                v-model="editForm.change_notes" 
                placeholder="Change notes (optional)"
                rows="2"
              ></textarea>
              <div class="buttons">
                <button @click="saveContent(item)" class="btn btn-primary">Save</button>
                <button @click="cancelEdit" class="btn btn-secondary">Cancel</button>
              </div>
            </div>
          </div>
          
          <div v-else class="content-display">
            <div class="content-value">{{ item.value }}</div>
            <div v-if="item.metadata" class="metadata-display">
              <strong>Metadata:</strong>
              <pre>{{ JSON.stringify(item.metadata, null, 2) }}</pre>
            </div>
          </div>
        </div>
        
        <div class="content-footer">
          <div class="content-info">
            <small>
              Created by {{ item.creator?.name }} on {{ formatDate(item.created_at) }}
              <span v-if="item.approved_by">
                | Approved by {{ item.approver?.name }} on {{ formatDate(item.approved_at) }}
              </span>
            </small>
          </div>
          
          <div class="content-actions">
            <button v-if="editingItem !== item.id" @click="editContent(item)" class="btn btn-sm btn-secondary">
              Edit
            </button>
            <button @click="viewHistory(item.id)" class="btn btn-sm btn-secondary">
              History
            </button>
            <button 
              v-if="item.status === 'draft'" 
              @click="requestApproval(item.id)" 
              class="btn btn-sm btn-warning"
            >
              Request Approval
            </button>
            <button 
              v-if="item.status === 'approved'" 
              @click="publishContent(item.id)" 
              class="btn btn-sm btn-success"
            >
              Publish
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreview" class="preview-modal">
      <div class="preview-content">
        <div class="preview-header">
          <h2>Content Preview</h2>
          <button @click="showPreview = false" class="close-btn">&times;</button>
        </div>
        <div class="preview-body">
          <iframe :src="previewUrl" class="preview-iframe"></iframe>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="history-modal">
      <div class="history-content">
        <div class="history-header">
          <h2>Content History</h2>
          <button @click="showHistoryModal = false" class="close-btn">&times;</button>
        </div>
        <div class="history-body">
          <div v-for="version in contentHistory" :key="version.id" class="history-item">
            <div class="version-info">
              <strong>Version {{ version.version_number }}</strong>
              <span>by {{ version.creator.name }}</span>
              <span>{{ formatDate(version.created_at) }}</span>
            </div>
            <div class="version-content">{{ version.value }}</div>
            <div v-if="version.change_notes" class="change-notes">
              <strong>Notes:</strong> {{ version.change_notes }}
            </div>
            <button @click="revertToVersion(selectedContentId, version.version_number)" class="btn btn-sm btn-secondary">
              Revert to This Version
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="import-modal">
      <div class="import-content">
        <div class="import-header">
          <h2>Import Content</h2>
          <button @click="showImportModal = false" class="close-btn">&times;</button>
        </div>
        <div class="import-body">
          <textarea 
            v-model="importData" 
            placeholder="Paste JSON content data here..."
            rows="10"
            class="import-textarea"
          ></textarea>
          <div class="import-actions">
            <button @click="importContent" class="btn btn-primary">Import</button>
            <button @click="showImportModal = false" class="btn btn-secondary">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

interface HomepageContent {
  id: number
  section: string
  key: string
  value: string
  audience: string
  metadata?: Record<string, unknown>
  status: string
  created_by: number
  approved_by?: number
  created_at: string
  updated_at: string
  approved_at?: string
  creator?: { name: string }
  approver?: { name: string }
}

interface ContentApproval {
  id: number
  homepage_content_id: number
  requested_by: number
  request_notes?: string
  requested_at: string
  homepage_content: HomepageContent
  requester: { name: string }
}

const props = defineProps<{
  content: HomepageContent[]
  pendingApprovals: ContentApproval[]
  sections: Record<string, string>
  audiences: string[]
}>()

// Reactive state
const selectedAudience = ref('both')
const selectedSection = ref('')
const statusFilter = ref('')
const editingItem = ref<number | null>(null)
const showPreview = ref(false)
const showHistoryModal = ref(false)
const showImportModal = ref(false)
const contentHistory = ref([])
const selectedContentId = ref<number | null>(null)
const importData = ref('')

const editForm = ref({
  value: '',
  metadata: null as Record<string, unknown> | null,
  change_notes: ''
})

// Computed
const filteredContent = computed(() => {
  let filtered = props.content

  if (selectedSection.value) {
    filtered = filtered.filter(item => item.section === selectedSection.value)
  }

  if (statusFilter.value) {
    filtered = filtered.filter(item => item.status === statusFilter.value)
  }

  return filtered
})

const previewUrl = computed(() => {
  return `/?preview=true&audience=${selectedAudience.value}`
})

// Methods
const loadContent = async () => {
  try {
    const response = await fetch(`/admin/homepage-content/content?audience=${selectedAudience.value}`)
    const data = await response.json()
    // Update content would require proper state management
  } catch (error) {
    console.error('Failed to load content:', error)
  }
}

const filterContent = () => {
  // Filtering is handled by computed property
}

const editContent = (item: HomepageContent) => {
  editingItem.value = item.id
  editForm.value = {
    value: item.value,
    metadata: item.metadata ? { ...item.metadata } : null,
    change_notes: ''
  }
}

const cancelEdit = () => {
  editingItem.value = null
  editForm.value = {
    value: '',
    metadata: null,
    change_notes: ''
  }
}

const saveContent = async (item: HomepageContent) => {
  try {
    const response = await fetch('/admin/homepage-content', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        section: item.section,
        key: item.key,
        value: editForm.value.value,
        audience: item.audience,
        metadata: editForm.value.metadata,
        change_notes: editForm.value.change_notes
      })
    })

    const data = await response.json()
    
    if (data.success) {
      // Refresh the page to show updated content
      router.reload()
    } else {
      alert('Failed to save content: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to save content:', error)
    alert('Failed to save content')
  }
}

const requestApproval = async (contentId: number) => {
  const notes = prompt('Enter approval request notes (optional):')
  
  try {
    const response = await fetch(`/admin/homepage-content/${contentId}/request-approval`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ notes })
    })

    const data = await response.json()
    
    if (data.success) {
      router.reload()
    } else {
      alert('Failed to request approval: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to request approval:', error)
    alert('Failed to request approval')
  }
}

const approveContent = async (contentId: number) => {
  const notes = prompt('Enter approval notes (optional):')
  
  try {
    const response = await fetch(`/admin/homepage-content/${contentId}/approve`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ notes })
    })

    const data = await response.json()
    
    if (data.success) {
      router.reload()
    } else {
      alert('Failed to approve content: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to approve content:', error)
    alert('Failed to approve content')
  }
}

const rejectContent = async (contentId: number) => {
  const notes = prompt('Enter rejection reason:')
  if (!notes) return
  
  try {
    const response = await fetch(`/admin/homepage-content/${contentId}/reject`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ notes })
    })

    const data = await response.json()
    
    if (data.success) {
      router.reload()
    } else {
      alert('Failed to reject content: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to reject content:', error)
    alert('Failed to reject content')
  }
}

const publishContent = async (contentId: number) => {
  if (!confirm('Are you sure you want to publish this content?')) return
  
  try {
    const response = await fetch(`/admin/homepage-content/${contentId}/publish`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    const data = await response.json()
    
    if (data.success) {
      router.reload()
    } else {
      alert('Failed to publish content: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to publish content:', error)
    alert('Failed to publish content')
  }
}

const viewHistory = async (contentId: number) => {
  selectedContentId.value = contentId
  
  try {
    const response = await fetch(`/admin/homepage-content/${contentId}/history`)
    const data = await response.json()
    
    if (data.success) {
      contentHistory.value = data.history
      showHistoryModal.value = true
    } else {
      alert('Failed to load history: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to load history:', error)
    alert('Failed to load history')
  }
}

const revertToVersion = async (contentId: number, versionNumber: number) => {
  if (!confirm(`Are you sure you want to revert to version ${versionNumber}?`)) return
  
  try {
    const response = await fetch(`/admin/homepage-content/${contentId}/revert`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ version_number: versionNumber })
    })

    const data = await response.json()
    
    if (data.success) {
      showHistoryModal.value = false
      router.reload()
    } else {
      alert('Failed to revert content: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to revert content:', error)
    alert('Failed to revert content')
  }
}

const exportContent = async () => {
  try {
    const response = await fetch(`/admin/homepage-content/export?audience=${selectedAudience.value}`)
    const data = await response.json()
    
    if (data.success) {
      const blob = new Blob([JSON.stringify(data.content, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `homepage-content-${selectedAudience.value}-${new Date().toISOString().split('T')[0]}.json`
      a.click()
      URL.revokeObjectURL(url)
    } else {
      alert('Failed to export content: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to export content:', error)
    alert('Failed to export content')
  }
}

const importContent = async () => {
  if (!importData.value.trim()) {
    alert('Please enter content data to import')
    return
  }
  
  try {
    const content = JSON.parse(importData.value)
    
    const response = await fetch('/admin/homepage-content/import', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ content })
    })

    const data = await response.json()
    
    if (data.success) {
      showImportModal.value = false
      importData.value = ''
      router.reload()
    } else {
      alert('Failed to import content: ' + data.message)
    }
  } catch (error) {
    console.error('Failed to import content:', error)
    alert('Failed to import content. Please check the JSON format.')
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

onMounted(() => {
  // Initial load
})
</script>

<style scoped>
.homepage-content-admin {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  background: #f5f5f5;
  border-radius: 8px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.pending-approvals-alert {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.approval-items {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.approval-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  background: white;
  border-radius: 4px;
}

.approval-actions {
  display: flex;
  gap: 8px;
}

.content-grid {
  display: grid;
  gap: 20px;
}

.content-item {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  background: white;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.content-meta {
  display: flex;
  gap: 8px;
}

.audience-badge, .status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.audience-badge.individual { background: #e3f2fd; color: #1976d2; }
.audience-badge.institutional { background: #f3e5f5; color: #7b1fa2; }
.audience-badge.both { background: #e8f5e8; color: #388e3c; }

.status-badge.draft { background: #f5f5f5; color: #666; }
.status-badge.pending { background: #fff3e0; color: #f57c00; }
.status-badge.approved { background: #e8f5e8; color: #388e3c; }
.status-badge.published { background: #e3f2fd; color: #1976d2; }

.content-textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
}

.metadata-section {
  margin: 10px 0;
}

.metadata-item {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 5px 0;
}

.metadata-item input {
  flex: 1;
  padding: 5px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-actions {
  margin-top: 10px;
}

.form-actions textarea {
  width: 100%;
  margin-bottom: 10px;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.buttons {
  display: flex;
  gap: 8px;
}

.content-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #eee;
}

.content-actions {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-warning { background: #ffc107; color: #212529; }
.btn-danger { background: #dc3545; color: white; }

.btn-sm {
  padding: 4px 8px;
  font-size: 12px;
}

.preview-modal, .history-modal, .import-modal {
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

.preview-content, .history-content, .import-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 800px;
  max-height: 90%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.preview-header, .history-header, .import-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #ddd;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

.preview-body, .history-body, .import-body {
  flex: 1;
  overflow: auto;
  padding: 15px;
}

.preview-iframe {
  width: 100%;
  height: 600px;
  border: none;
}

.history-item {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 15px;
  margin-bottom: 10px;
}

.version-info {
  display: flex;
  gap: 15px;
  margin-bottom: 10px;
  font-size: 14px;
}

.version-content {
  background: #f8f9fa;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 10px;
}

.change-notes {
  font-size: 14px;
  color: #666;
  margin-bottom: 10px;
}

.import-textarea {
  width: 100%;
  height: 300px;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: monospace;
}

.import-actions {
  display: flex;
  gap: 10px;
  margin-top: 15px;
}
</style>