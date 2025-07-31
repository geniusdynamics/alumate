<template>
  <div class="learning-resources">
    <div class="resources-header">
      <h3 class="text-xl font-semibold text-gray-900">Learning Resources</h3>
      <button @click="showAddResourceModal = true" class="btn-primary">
        Share Resource
      </button>
    </div>

    <div class="resources-filters">
      <div class="filter-row">
        <div class="filter-group">
          <label>Resource Type</label>
          <select v-model="filters.type" @change="applyFilters" class="form-select">
            <option value="">All Types</option>
            <option value="Course">Courses</option>
            <option value="Article">Articles</option>
            <option value="Video">Videos</option>
            <option value="Book">Books</option>
            <option value="Workshop">Workshops</option>
            <option value="Certification">Certifications</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Skill</label>
          <select v-model="filters.skill_id" @change="applyFilters" class="form-select">
            <option value="">All Skills</option>
            <option v-for="skill in availableSkills" :key="skill.id" :value="skill.id">
              {{ skill.name }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label>Min Rating</label>
          <select v-model="filters.min_rating" @change="applyFilters" class="form-select">
            <option value="">Any Rating</option>
            <option value="3">3+ Stars</option>
            <option value="4">4+ Stars</option>
            <option value="4.5">4.5+ Stars</option>
          </select>
        </div>
      </div>
    </div>

    <div class="resources-grid">
      <div
        v-for="resource in resources"
        :key="resource.id"
        class="resource-card"
      >
        <div class="resource-header">
          <div class="resource-type" :class="resource.type.toLowerCase()">
            <Icon :name="getTypeIcon(resource.type)" class="w-4 h-4" />
            {{ resource.type }}
          </div>
          <div class="resource-rating">
            <div class="stars">
              <Icon
                v-for="star in 5"
                :key="star"
                name="star"
                :class="[
                  'w-4 h-4',
                  star <= Math.floor(resource.rating) ? 'text-yellow-400' : 'text-gray-300'
                ]"
              />
            </div>
            <span class="rating-text">{{ resource.rating.toFixed(1) }} ({{ resource.rating_count }})</span>
          </div>
        </div>

        <div class="resource-content">
          <h4 class="resource-title">
            <a :href="resource.url" target="_blank" class="resource-link">
              {{ resource.title }}
            </a>
          </h4>
          <p class="resource-description">{{ resource.description }}</p>
          
          <div class="resource-skills">
            <span
              v-for="skill in resource.skills"
              :key="skill.id"
              class="skill-tag"
            >
              {{ skill.name }}
            </span>
          </div>
        </div>

        <div class="resource-footer">
          <div class="resource-creator">
            <img
              :src="resource.creator.avatar_url"
              :alt="resource.creator.name"
              class="w-6 h-6 rounded-full"
            />
            <span class="creator-name">{{ resource.creator.name }}</span>
          </div>

          <div class="resource-actions">
            <button
              @click="rateResource(resource)"
              class="btn-secondary btn-sm"
            >
              Rate
            </button>
            <button
              @click="bookmarkResource(resource)"
              class="btn-secondary btn-sm"
              :class="{ 'bookmarked': resource.is_bookmarked }"
            >
              <Icon name="bookmark" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="hasMore" class="load-more">
      <button @click="loadMore" class="btn-secondary" :disabled="loading">
        {{ loading ? 'Loading...' : 'Load More Resources' }}
      </button>
    </div>

    <!-- Add Resource Modal -->
    <div v-if="showAddResourceModal" class="modal-overlay" @click="closeAddResourceModal">
      <div class="modal-content" @click.stop>
        <h3 class="modal-title">Share Learning Resource</h3>

        <form @submit.prevent="addResource">
          <div class="form-group">
            <label>Title *</label>
            <input
              v-model="newResource.title"
              type="text"
              class="form-input"
              placeholder="Resource title"
              required
            />
          </div>

          <div class="form-group">
            <label>Description *</label>
            <textarea
              v-model="newResource.description"
              class="form-textarea"
              rows="3"
              placeholder="Describe what this resource covers..."
              required
            ></textarea>
          </div>

          <div class="form-group">
            <label>Type *</label>
            <select v-model="newResource.type" class="form-select" required>
              <option value="">Select type</option>
              <option value="Course">Course</option>
              <option value="Article">Article</option>
              <option value="Video">Video</option>
              <option value="Book">Book</option>
              <option value="Workshop">Workshop</option>
              <option value="Certification">Certification</option>
            </select>
          </div>

          <div class="form-group">
            <label>URL *</label>
            <input
              v-model="newResource.url"
              type="url"
              class="form-input"
              placeholder="https://..."
              required
            />
          </div>

          <div class="form-group">
            <label>Related Skills *</label>
            <div class="skills-selector">
              <div
                v-for="skill in availableSkills"
                :key="skill.id"
                class="skill-checkbox"
              >
                <input
                  :id="`skill-${skill.id}`"
                  v-model="newResource.skill_ids"
                  :value="skill.id"
                  type="checkbox"
                  class="form-checkbox"
                />
                <label :for="`skill-${skill.id}`" class="skill-label">
                  {{ skill.name }}
                </label>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeAddResourceModal" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Sharing...' : 'Share Resource' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Rating Modal -->
    <div v-if="showRatingModal" class="modal-overlay" @click="closeRatingModal">
      <div class="modal-content" @click.stop>
        <h3 class="modal-title">Rate Resource</h3>
        
        <div class="resource-preview">
          <h4>{{ selectedResource?.title }}</h4>
          <p class="text-sm text-gray-600">{{ selectedResource?.description }}</p>
        </div>

        <div class="rating-selector">
          <label>Your Rating</label>
          <div class="stars-input">
            <button
              v-for="star in 5"
              :key="star"
              type="button"
              @click="selectedRating = star"
              class="star-button"
            >
              <Icon
                name="star"
                :class="[
                  'w-8 h-8',
                  star <= selectedRating ? 'text-yellow-400' : 'text-gray-300'
                ]"
              />
            </button>
          </div>
          <div class="rating-labels">
            <span class="text-sm text-gray-600">
              {{ getRatingLabel(selectedRating) }}
            </span>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" @click="closeRatingModal" class="btn-secondary">
            Cancel
          </button>
          <button @click="submitRating" class="btn-primary" :disabled="!selectedRating || loading">
            {{ loading ? 'Rating...' : 'Submit Rating' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Icon from './Icon.vue'

export default {
  name: 'LearningResources',
  components: {
    Icon
  },
  data() {
    return {
      resources: [],
      availableSkills: [],
      filters: {
        type: '',
        skill_id: '',
        min_rating: ''
      },
      showAddResourceModal: false,
      showRatingModal: false,
      selectedResource: null,
      selectedRating: 0,
      loading: false,
      hasMore: true,
      currentPage: 1,
      newResource: {
        title: '',
        description: '',
        type: '',
        url: '',
        skill_ids: []
      }
    }
  },
  mounted() {
    this.loadResources()
    this.loadSkills()
  },
  methods: {
    async loadResources(page = 1) {
      this.loading = true
      try {
        const params = new URLSearchParams({
          page: page.toString(),
          ...this.filters
        })

        const response = await fetch(`/api/learning-resources?${params}`)
        const data = await response.json()
        
        if (page === 1) {
          this.resources = data.data
        } else {
          this.resources.push(...data.data)
        }
        
        this.hasMore = data.current_page < data.last_page
        this.currentPage = data.current_page
      } catch (error) {
        console.error('Failed to load resources:', error)
      } finally {
        this.loading = false
      }
    },
    async loadSkills() {
      try {
        const response = await fetch('/api/skills/search?query=')
        const data = await response.json()
        this.availableSkills = data.skills
      } catch (error) {
        console.error('Failed to load skills:', error)
      }
    },
    applyFilters() {
      this.currentPage = 1
      this.loadResources(1)
    },
    loadMore() {
      this.loadResources(this.currentPage + 1)
    },
    async addResource() {
      this.loading = true
      try {
        const response = await fetch('/api/learning-resources', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(this.newResource)
        })

        if (response.ok) {
          this.closeAddResourceModal()
          this.loadResources(1)
        }
      } catch (error) {
        console.error('Failed to add resource:', error)
      } finally {
        this.loading = false
      }
    },
    rateResource(resource) {
      this.selectedResource = resource
      this.selectedRating = 0
      this.showRatingModal = true
    },
    async submitRating() {
      this.loading = true
      try {
        const response = await fetch(`/api/learning-resources/${this.selectedResource.id}/rate`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            rating: this.selectedRating
          })
        })

        if (response.ok) {
          this.closeRatingModal()
          this.loadResources(1)
        }
      } catch (error) {
        console.error('Failed to rate resource:', error)
      } finally {
        this.loading = false
      }
    },
    async bookmarkResource(resource) {
      // Implementation for bookmarking
      console.log('Bookmark resource:', resource.id)
    },
    closeAddResourceModal() {
      this.showAddResourceModal = false
      this.newResource = {
        title: '',
        description: '',
        type: '',
        url: '',
        skill_ids: []
      }
    },
    closeRatingModal() {
      this.showRatingModal = false
      this.selectedResource = null
      this.selectedRating = 0
    },
    getTypeIcon(type) {
      const icons = {
        Course: 'academic-cap',
        Article: 'document-text',
        Video: 'play',
        Book: 'book-open',
        Workshop: 'users',
        Certification: 'badge-check'
      }
      return icons[type] || 'document'
    },
    getRatingLabel(rating) {
      const labels = {
        1: 'Poor',
        2: 'Fair',
        3: 'Good',
        4: 'Very Good',
        5: 'Excellent'
      }
      return labels[rating] || 'Select rating'
    }
  }
}
</script>

<style scoped>
.learning-resources {
  @apply space-y-6;
}

.resources-header {
  @apply flex justify-between items-center;
}

.resources-filters {
  @apply bg-white p-4 rounded-lg border;
}

.filter-row {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.filter-group label {
  @apply block text-sm font-medium text-gray-700 mb-1;
}

.resources-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.resource-card {
  @apply bg-white p-6 rounded-lg border hover:shadow-md transition-shadow;
}

.resource-header {
  @apply flex justify-between items-start mb-4;
}

.resource-type {
  @apply inline-flex items-center space-x-1 px-2 py-1 rounded-full text-xs font-medium;
}

.resource-type.course {
  @apply bg-blue-100 text-blue-700;
}

.resource-type.article {
  @apply bg-green-100 text-green-700;
}

.resource-type.video {
  @apply bg-red-100 text-red-700;
}

.resource-type.book {
  @apply bg-purple-100 text-purple-700;
}

.resource-type.workshop {
  @apply bg-yellow-100 text-yellow-700;
}

.resource-type.certification {
  @apply bg-indigo-100 text-indigo-700;
}

.resource-rating {
  @apply text-right;
}

.stars {
  @apply flex space-x-1;
}

.rating-text {
  @apply text-xs text-gray-600 mt-1;
}

.resource-title {
  @apply text-lg font-semibold mb-2;
}

.resource-link {
  @apply text-blue-600 hover:text-blue-800;
}

.resource-description {
  @apply text-gray-600 text-sm mb-3;
}

.resource-skills {
  @apply flex flex-wrap gap-1 mb-4;
}

.skill-tag {
  @apply px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs;
}

.resource-footer {
  @apply flex justify-between items-center pt-4 border-t;
}

.resource-creator {
  @apply flex items-center space-x-2;
}

.creator-name {
  @apply text-sm text-gray-600;
}

.resource-actions {
  @apply flex space-x-2;
}

.resource-actions .bookmarked {
  @apply bg-blue-100 text-blue-700;
}

.load-more {
  @apply text-center;
}

.skills-selector {
  @apply grid grid-cols-2 gap-2 max-h-48 overflow-y-auto p-2 border rounded;
}

.skill-checkbox {
  @apply flex items-center space-x-2;
}

.skill-label {
  @apply text-sm cursor-pointer;
}

.resource-preview {
  @apply p-4 bg-gray-50 rounded-lg mb-4;
}

.rating-selector {
  @apply text-center mb-6;
}

.stars-input {
  @apply flex justify-center space-x-1 my-4;
}

.star-button {
  @apply hover:scale-110 transition-transform;
}

.rating-labels {
  @apply text-center;
}
</style>