<template>
  <div class="skills-profile">
    <div class="skills-header">
      <h3 class="text-xl font-semibold text-gray-900">Skills & Expertise</h3>
      <button
        @click="showAddSkillModal = true"
        class="btn-primary"
        v-if="isOwnProfile"
      >
        Add Skill
      </button>
    </div>

    <div class="skills-stats mb-6">
      <div class="grid grid-cols-3 gap-4">
        <div class="stat-card">
          <div class="stat-number">{{ totalSkills }}</div>
          <div class="stat-label">Skills</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">{{ totalEndorsements }}</div>
          <div class="stat-label">Endorsements</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">{{ topSkillsCount }}</div>
          <div class="stat-label">Expert Level</div>
        </div>
      </div>
    </div>

    <div class="skills-categories">
      <div class="category-tabs mb-4">
        <button
          v-for="category in categories"
          :key="category"
          @click="selectedCategory = category"
          :class="[
            'category-tab',
            selectedCategory === category ? 'active' : ''
          ]"
        >
          {{ category }}
        </button>
      </div>

      <div class="skills-grid">
        <div
          v-for="skill in filteredSkills"
          :key="skill.id"
          class="skill-card"
        >
          <div class="skill-header">
            <h4 class="skill-name">{{ skill.skill.name }}</h4>
            <div class="skill-level" :class="skill.proficiency_level.toLowerCase()">
              {{ skill.proficiency_level }}
            </div>
          </div>
          
          <div class="skill-details">
            <div class="experience">
              {{ skill.years_experience }} years experience
            </div>
            <div class="endorsements">
              <Icon name="thumbs-up" class="w-4 h-4" />
              {{ skill.endorsed_count }} endorsements
            </div>
          </div>

          <div class="skill-endorsements" v-if="skill.endorsements.length > 0">
            <div class="endorsement-preview">
              <div
                v-for="endorsement in skill.endorsements.slice(0, 3)"
                :key="endorsement.id"
                class="endorser-avatar"
                :title="endorsement.endorser.name"
              >
                <img
                  :src="endorsement.endorser.avatar_url"
                  :alt="endorsement.endorser.name"
                  class="w-6 h-6 rounded-full"
                />
              </div>
              <button
                v-if="skill.endorsements.length > 3"
                @click="showEndorsements(skill)"
                class="more-endorsements"
              >
                +{{ skill.endorsements.length - 3 }}
              </button>
            </div>
          </div>

          <div class="skill-actions" v-if="!isOwnProfile">
            <button
              @click="endorseSkill(skill)"
              class="btn-secondary btn-sm"
              :disabled="hasEndorsed(skill)"
            >
              {{ hasEndorsed(skill) ? 'Endorsed' : 'Endorse' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Skill Modal -->
    <div v-if="showAddSkillModal" class="modal-overlay" @click="showAddSkillModal = false">
      <div class="modal-content" @click.stop>
        <h3 class="modal-title">Add New Skill</h3>
        
        <form @submit.prevent="addSkill">
          <div class="form-group">
            <label>Skill Name</label>
            <input
              v-model="newSkill.skill_name"
              type="text"
              class="form-input"
              placeholder="e.g., JavaScript, Project Management"
              required
            />
          </div>

          <div class="form-group">
            <label>Category</label>
            <select v-model="newSkill.category" class="form-select">
              <option value="Technical">Technical</option>
              <option value="Leadership">Leadership</option>
              <option value="Communication">Communication</option>
              <option value="Design">Design</option>
              <option value="Business">Business</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="form-group">
            <label>Proficiency Level</label>
            <select v-model="newSkill.proficiency_level" class="form-select" required>
              <option value="Beginner">Beginner</option>
              <option value="Intermediate">Intermediate</option>
              <option value="Advanced">Advanced</option>
              <option value="Expert">Expert</option>
            </select>
          </div>

          <div class="form-group">
            <label>Years of Experience</label>
            <input
              v-model.number="newSkill.years_experience"
              type="number"
              min="0"
              max="50"
              class="form-input"
            />
          </div>

          <div class="modal-actions">
            <button type="button" @click="showAddSkillModal = false" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Adding...' : 'Add Skill' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Endorsements Modal -->
    <div v-if="showEndorsementsModal" class="modal-overlay" @click="showEndorsementsModal = false">
      <div class="modal-content" @click.stop>
        <h3 class="modal-title">Endorsements for {{ selectedSkillForEndorsements?.skill.name }}</h3>
        
        <div class="endorsements-list">
          <div
            v-for="endorsement in selectedSkillForEndorsements?.endorsements"
            :key="endorsement.id"
            class="endorsement-item"
          >
            <img
              :src="endorsement.endorser.avatar_url"
              :alt="endorsement.endorser.name"
              class="w-10 h-10 rounded-full"
            />
            <div class="endorsement-content">
              <div class="endorser-name">{{ endorsement.endorser.name }}</div>
              <div class="endorsement-message" v-if="endorsement.message">
                "{{ endorsement.message }}"
              </div>
              <div class="endorsement-date">
                {{ formatDate(endorsement.created_at) }}
              </div>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button @click="showEndorsementsModal = false" class="btn-primary">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Icon from './Icon.vue'

export default {
  name: 'SkillsProfile',
  components: {
    Icon
  },
  props: {
    userId: {
      type: Number,
      required: true
    },
    isOwnProfile: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      skills: [],
      totalSkills: 0,
      totalEndorsements: 0,
      selectedCategory: 'All',
      showAddSkillModal: false,
      showEndorsementsModal: false,
      selectedSkillForEndorsements: null,
      loading: false,
      newSkill: {
        skill_name: '',
        category: 'Technical',
        proficiency_level: 'Beginner',
        years_experience: 0
      }
    }
  },
  computed: {
    categories() {
      const skillCategories = [...new Set(this.skills.map(skill => skill.skill.category))]
      return ['All', ...skillCategories]
    },
    filteredSkills() {
      if (this.selectedCategory === 'All') {
        return this.skills
      }
      return this.skills.filter(skill => skill.skill.category === this.selectedCategory)
    },
    topSkillsCount() {
      return this.skills.filter(skill => skill.proficiency_level === 'Expert').length
    }
  },
  mounted() {
    this.loadUserSkills()
  },
  methods: {
    async loadUserSkills() {
      try {
        const response = await fetch(`/api/users/${this.userId}/skills`)
        const data = await response.json()
        
        this.skills = data.skills
        this.totalSkills = data.total_skills
        this.totalEndorsements = data.total_endorsements
      } catch (error) {
        console.error('Failed to load skills:', error)
      }
    },
    async addSkill() {
      this.loading = true
      try {
        const response = await fetch('/api/users/skills', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(this.newSkill)
        })

        if (response.ok) {
          this.showAddSkillModal = false
          this.resetNewSkill()
          await this.loadUserSkills()
        }
      } catch (error) {
        console.error('Failed to add skill:', error)
      } finally {
        this.loading = false
      }
    },
    async endorseSkill(skill) {
      try {
        const response = await fetch('/api/skills/endorse', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            user_skill_id: skill.id
          })
        })

        if (response.ok) {
          await this.loadUserSkills()
        }
      } catch (error) {
        console.error('Failed to endorse skill:', error)
      }
    },
    hasEndorsed(skill) {
      const currentUserId = window.auth?.user?.id
      return skill.endorsements.some(e => e.endorser.id === currentUserId)
    },
    showEndorsements(skill) {
      this.selectedSkillForEndorsements = skill
      this.showEndorsementsModal = true
    },
    resetNewSkill() {
      this.newSkill = {
        skill_name: '',
        category: 'Technical',
        proficiency_level: 'Beginner',
        years_experience: 0
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.skills-profile {
  @apply space-y-6;
}

.skills-header {
  @apply flex justify-between items-center;
}

.skills-stats .stat-card {
  @apply bg-white p-4 rounded-lg border text-center;
}

.stat-number {
  @apply text-2xl font-bold text-blue-600;
}

.stat-label {
  @apply text-sm text-gray-600;
}

.category-tabs {
  @apply flex space-x-2 overflow-x-auto;
}

.category-tab {
  @apply px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap;
  @apply bg-gray-100 text-gray-700 hover:bg-gray-200;
}

.category-tab.active {
  @apply bg-blue-100 text-blue-700;
}

.skills-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4;
}

.skill-card {
  @apply bg-white p-4 rounded-lg border hover:shadow-md transition-shadow;
}

.skill-header {
  @apply flex justify-between items-start mb-2;
}

.skill-name {
  @apply font-semibold text-gray-900;
}

.skill-level {
  @apply px-2 py-1 rounded-full text-xs font-medium;
}

.skill-level.beginner {
  @apply bg-gray-100 text-gray-700;
}

.skill-level.intermediate {
  @apply bg-blue-100 text-blue-700;
}

.skill-level.advanced {
  @apply bg-green-100 text-green-700;
}

.skill-level.expert {
  @apply bg-purple-100 text-purple-700;
}

.skill-details {
  @apply space-y-1 text-sm text-gray-600 mb-3;
}

.endorsements {
  @apply flex items-center space-x-1;
}

.endorsement-preview {
  @apply flex items-center space-x-1;
}

.endorser-avatar img {
  @apply border border-gray-200;
}

.more-endorsements {
  @apply text-xs text-blue-600 hover:text-blue-800;
}

.skill-actions {
  @apply pt-2 border-t;
}

.endorsements-list {
  @apply space-y-4 max-h-96 overflow-y-auto;
}

.endorsement-item {
  @apply flex space-x-3;
}

.endorsement-content {
  @apply flex-1;
}

.endorser-name {
  @apply font-medium text-gray-900;
}

.endorsement-message {
  @apply text-gray-700 italic mt-1;
}

.endorsement-date {
  @apply text-xs text-gray-500 mt-1;
}
</style>