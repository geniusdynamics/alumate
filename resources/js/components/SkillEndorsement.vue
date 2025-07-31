<template>
  <div class="skill-endorsement">
    <div class="endorsement-header">
      <h3 class="text-lg font-semibold text-gray-900">Endorse Skills</h3>
      <p class="text-sm text-gray-600">Help your connections showcase their expertise</p>
    </div>

    <div class="connection-search mb-6">
      <div class="search-input">
        <Icon name="search" class="w-5 h-5 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search connections..."
          class="form-input pl-10"
          @input="searchConnections"
        />
      </div>
    </div>

    <div class="connections-list">
      <div
        v-for="connection in filteredConnections"
        :key="connection.id"
        class="connection-card"
      >
        <div class="connection-info">
          <img
            :src="connection.avatar_url"
            :alt="connection.name"
            class="w-12 h-12 rounded-full"
          />
          <div class="connection-details">
            <h4 class="connection-name">{{ connection.name }}</h4>
            <p class="connection-title">{{ connection.current_position }}</p>
            <p class="connection-company">{{ connection.company }}</p>
          </div>
        </div>

        <div class="connection-skills">
          <h5 class="skills-title">Skills to endorse:</h5>
          <div class="skills-list">
            <button
              v-for="skill in connection.endorsable_skills"
              :key="skill.id"
              @click="openEndorsementModal(connection, skill)"
              class="skill-tag"
              :class="{ 'already-endorsed': skill.already_endorsed }"
              :disabled="skill.already_endorsed"
            >
              {{ skill.skill.name }}
              <span class="endorsement-count">{{ skill.endorsed_count }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Endorsement Modal -->
    <div v-if="showEndorsementModal" class="modal-overlay" @click="closeEndorsementModal">
      <div class="modal-content" @click.stop>
        <h3 class="modal-title">
          Endorse {{ selectedConnection?.name }} for {{ selectedSkill?.skill.name }}
        </h3>

        <div class="endorsement-preview">
          <div class="connection-preview">
            <img
              :src="selectedConnection?.avatar_url"
              :alt="selectedConnection?.name"
              class="w-16 h-16 rounded-full"
            />
            <div>
              <h4 class="font-semibold">{{ selectedConnection?.name }}</h4>
              <p class="text-sm text-gray-600">{{ selectedConnection?.current_position }}</p>
            </div>
          </div>

          <div class="skill-preview">
            <div class="skill-info">
              <h5 class="font-medium">{{ selectedSkill?.skill.name }}</h5>
              <div class="skill-meta">
                <span class="proficiency-level">{{ selectedSkill?.proficiency_level }}</span>
                <span class="experience">{{ selectedSkill?.years_experience }} years</span>
              </div>
            </div>
          </div>
        </div>

        <form @submit.prevent="submitEndorsement">
          <div class="form-group">
            <label>Add a personal message (optional)</label>
            <textarea
              v-model="endorsementMessage"
              class="form-textarea"
              rows="3"
              placeholder="Share why you're endorsing this skill..."
              maxlength="500"
            ></textarea>
            <div class="character-count">
              {{ endorsementMessage.length }}/500
            </div>
          </div>

          <div class="endorsement-tips">
            <h6 class="tips-title">Endorsement Tips:</h6>
            <ul class="tips-list">
              <li>Be specific about their expertise</li>
              <li>Mention projects you've worked on together</li>
              <li>Highlight their unique strengths</li>
            </ul>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeEndorsementModal" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Endorsing...' : 'Send Endorsement' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Success Message -->
    <div v-if="showSuccessMessage" class="success-toast">
      <Icon name="check-circle" class="w-5 h-5 text-green-500" />
      <span>Endorsement sent successfully!</span>
    </div>
  </div>
</template>

<script>
import Icon from './Icon.vue'

export default {
  name: 'SkillEndorsement',
  components: {
    Icon
  },
  data() {
    return {
      connections: [],
      filteredConnections: [],
      searchQuery: '',
      showEndorsementModal: false,
      selectedConnection: null,
      selectedSkill: null,
      endorsementMessage: '',
      loading: false,
      showSuccessMessage: false
    }
  },
  mounted() {
    this.loadConnections()
  },
  methods: {
    async loadConnections() {
      try {
        const response = await fetch('/api/connections/endorsable')
        const data = await response.json()
        
        this.connections = data.connections
        this.filteredConnections = data.connections
      } catch (error) {
        console.error('Failed to load connections:', error)
      }
    },
    searchConnections() {
      if (!this.searchQuery.trim()) {
        this.filteredConnections = this.connections
        return
      }

      const query = this.searchQuery.toLowerCase()
      this.filteredConnections = this.connections.filter(connection =>
        connection.name.toLowerCase().includes(query) ||
        connection.current_position?.toLowerCase().includes(query) ||
        connection.company?.toLowerCase().includes(query)
      )
    },
    openEndorsementModal(connection, skill) {
      this.selectedConnection = connection
      this.selectedSkill = skill
      this.endorsementMessage = ''
      this.showEndorsementModal = true
    },
    closeEndorsementModal() {
      this.showEndorsementModal = false
      this.selectedConnection = null
      this.selectedSkill = null
      this.endorsementMessage = ''
    },
    async submitEndorsement() {
      this.loading = true
      try {
        const response = await fetch('/api/skills/endorse', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            user_skill_id: this.selectedSkill.id,
            message: this.endorsementMessage.trim() || null
          })
        })

        if (response.ok) {
          this.closeEndorsementModal()
          this.showSuccessToast()
          await this.loadConnections() // Refresh to update endorsement status
        } else {
          const error = await response.json()
          alert(error.message || 'Failed to send endorsement')
        }
      } catch (error) {
        console.error('Failed to submit endorsement:', error)
        alert('Failed to send endorsement')
      } finally {
        this.loading = false
      }
    },
    showSuccessToast() {
      this.showSuccessMessage = true
      setTimeout(() => {
        this.showSuccessMessage = false
      }, 3000)
    }
  }
}
</script>

<style scoped>
.skill-endorsement {
  @apply space-y-6;
}

.endorsement-header {
  @apply text-center mb-6;
}

.search-input {
  @apply relative;
}

.search-input .form-input {
  @apply pl-10;
}

.search-input svg {
  @apply absolute left-3 top-1/2 transform -translate-y-1/2;
}

.connections-list {
  @apply space-y-4;
}

.connection-card {
  @apply bg-white p-6 rounded-lg border hover:shadow-md transition-shadow;
}

.connection-info {
  @apply flex items-center space-x-4 mb-4;
}

.connection-details {
  @apply flex-1;
}

.connection-name {
  @apply font-semibold text-gray-900;
}

.connection-title {
  @apply text-sm text-gray-600;
}

.connection-company {
  @apply text-sm text-gray-500;
}

.skills-title {
  @apply text-sm font-medium text-gray-700 mb-2;
}

.skills-list {
  @apply flex flex-wrap gap-2;
}

.skill-tag {
  @apply inline-flex items-center space-x-1 px-3 py-1 rounded-full text-sm;
  @apply bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors;
}

.skill-tag.already-endorsed {
  @apply bg-gray-100 text-gray-500 cursor-not-allowed;
}

.endorsement-count {
  @apply bg-blue-200 text-blue-800 px-1.5 py-0.5 rounded-full text-xs;
}

.endorsement-preview {
  @apply space-y-4 mb-6;
}

.connection-preview {
  @apply flex items-center space-x-4 p-4 bg-gray-50 rounded-lg;
}

.skill-preview {
  @apply p-4 bg-blue-50 rounded-lg;
}

.skill-meta {
  @apply flex items-center space-x-2 text-sm text-gray-600;
}

.proficiency-level {
  @apply px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs;
}

.character-count {
  @apply text-xs text-gray-500 text-right mt-1;
}

.endorsement-tips {
  @apply bg-yellow-50 p-4 rounded-lg mb-6;
}

.tips-title {
  @apply font-medium text-yellow-800 mb-2;
}

.tips-list {
  @apply text-sm text-yellow-700 space-y-1;
}

.tips-list li {
  @apply flex items-start;
}

.tips-list li::before {
  content: "•";
  @apply text-yellow-500 mr-2;
}

.success-toast {
  @apply fixed bottom-4 right-4 bg-green-100 text-green-800 px-4 py-2 rounded-lg;
  @apply flex items-center space-x-2 shadow-lg z-50;
}
</style>