<template>
  <div class="career-timeline">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Career Timeline</h2>
      <div class="flex space-x-3" v-if="canEdit">
        <button
          @click="showAddCareerModal = true"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Add Position
        </button>
        <button
          @click="showMilestoneModal = true"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
        >
          <StarIcon class="w-4 h-4 mr-2" />
          Add Milestone
        </button>
      </div>
    </div>

    <!-- Career Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8" v-if="progression">
      <div class="bg-white p-4 rounded-lg border border-gray-200">
        <div class="text-2xl font-bold text-blue-600">{{ progression.total_experience_years }}y</div>
        <div class="text-sm text-gray-600">Total Experience</div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-gray-200">
        <div class="text-2xl font-bold text-green-600">{{ progression.companies_count }}</div>
        <div class="text-sm text-gray-600">Companies</div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-gray-200">
        <div class="text-2xl font-bold text-purple-600">{{ progression.promotions_count }}</div>
        <div class="text-sm text-gray-600">Promotions</div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-gray-200">
        <div class="text-2xl font-bold text-orange-600">{{ stats.total_milestones }}</div>
        <div class="text-sm text-gray-600">Milestones</div>
      </div>
    </div>

    <!-- Career Goals -->
    <CareerGoals 
      v-if="canEdit && suggestions.length > 0" 
      :suggestions="suggestions" 
      class="mb-8"
    />

    <!-- Timeline -->
    <div class="relative">
      <!-- Timeline line -->
      <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200"></div>

      <!-- Timeline items -->
      <div class="space-y-8">
        <div
          v-for="(item, index) in timeline"
          :key="`${item.type}-${item.data.id}-${index}`"
          class="relative flex items-start"
        >
          <!-- Timeline dot -->
          <div class="flex-shrink-0 w-16 flex justify-center">
            <div 
              :class="[
                'w-4 h-4 rounded-full border-4 bg-white',
                getTimelineDotColor(item)
              ]"
            ></div>
          </div>

          <!-- Content -->
          <div class="flex-1 ml-4">
            <CareerEntry
              v-if="item.type === 'career_entry'"
              :entry="item.data"
              :can-edit="canEdit"
              @edit="editCareerEntry"
              @delete="deleteCareerEntry"
            />
            
            <MilestoneCard
              v-else-if="item.type === 'milestone'"
              :milestone="item.data"
              :can-edit="canEdit"
              @edit="editMilestone"
              @delete="deleteMilestone"
            />

            <div
              v-else-if="item.type === 'career_end'"
              class="text-sm text-gray-500 italic"
            >
              Ended position at {{ item.data.company }}
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="timeline.length === 0" class="text-center py-12">
        <BriefcaseIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">No career information yet</h3>
        <p class="text-gray-600 mb-4">
          {{ canEdit ? 'Add your first position to get started' : 'This user hasn\'t added any career information yet' }}
        </p>
        <button
          v-if="canEdit"
          @click="showAddCareerModal = true"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Add First Position
        </button>
      </div>
    </div>

    <!-- Modals -->
    <AddCareerModal
      v-if="showAddCareerModal"
      :career-entry="editingCareerEntry"
      @close="closeAddCareerModal"
      @saved="handleCareerEntrySaved"
    />

    <MilestoneModal
      v-if="showMilestoneModal"
      :milestone="editingMilestone"
      @close="closeMilestoneModal"
      @saved="handleMilestoneSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusIcon, StarIcon, BriefcaseIcon } from '@heroicons/vue/24/outline'
import CareerEntry from './CareerEntry.vue'
import MilestoneCard from './MilestoneCard.vue'
import CareerGoals from './CareerGoals.vue'
import AddCareerModal from './AddCareerModal.vue'
import MilestoneModal from './MilestoneModal.vue'

const props = defineProps({
  userId: {
    type: Number,
    required: true
  },
  canEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['updated'])

// Reactive data
const timeline = ref([])
const careerEntries = ref([])
const milestones = ref([])
const progression = ref(null)
const stats = ref({})
const suggestions = ref([])
const loading = ref(true)
const error = ref(null)

// Modal states
const showAddCareerModal = ref(false)
const showMilestoneModal = ref(false)
const editingCareerEntry = ref(null)
const editingMilestone = ref(null)

// Computed
const canEdit = computed(() => props.canEdit)

// Methods
const loadTimeline = async () => {
  try {
    loading.value = true
    const response = await fetch(`/api/users/${props.userId}/career`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error('Failed to load career timeline')
    }

    const data = await response.json()
    
    timeline.value = data.data.timeline
    careerEntries.value = data.data.career_entries
    milestones.value = data.data.milestones
    progression.value = data.data.progression
    stats.value = data.data.stats

    // Load suggestions if user can edit
    if (canEdit.value) {
      await loadSuggestions()
    }
  } catch (err) {
    error.value = err.message
    console.error('Error loading career timeline:', err)
  } finally {
    loading.value = false
  }
}

const loadSuggestions = async () => {
  try {
    const response = await fetch('/api/career/suggestions', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      suggestions.value = data.data
    }
  } catch (err) {
    console.error('Error loading career suggestions:', err)
  }
}

const getTimelineDotColor = (item) => {
  switch (item.type) {
    case 'career_entry':
      return 'border-blue-500'
    case 'milestone':
      return 'border-green-500'
    case 'career_end':
      return 'border-gray-400'
    default:
      return 'border-gray-400'
  }
}

const editCareerEntry = (entry) => {
  editingCareerEntry.value = entry
  showAddCareerModal.value = true
}

const deleteCareerEntry = async (entryId) => {
  if (!confirm('Are you sure you want to delete this career entry?')) {
    return
  }

  try {
    const response = await fetch(`/api/career/${entryId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      await loadTimeline()
      emit('updated')
    } else {
      throw new Error('Failed to delete career entry')
    }
  } catch (err) {
    console.error('Error deleting career entry:', err)
    alert('Failed to delete career entry')
  }
}

const editMilestone = (milestone) => {
  editingMilestone.value = milestone
  showMilestoneModal.value = true
}

const deleteMilestone = async (milestoneId) => {
  if (!confirm('Are you sure you want to delete this milestone?')) {
    return
  }

  try {
    const response = await fetch(`/api/milestones/${milestoneId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      await loadTimeline()
      emit('updated')
    } else {
      throw new Error('Failed to delete milestone')
    }
  } catch (err) {
    console.error('Error deleting milestone:', err)
    alert('Failed to delete milestone')
  }
}

const closeAddCareerModal = () => {
  showAddCareerModal.value = false
  editingCareerEntry.value = null
}

const closeMilestoneModal = () => {
  showMilestoneModal.value = false
  editingMilestone.value = null
}

const handleCareerEntrySaved = () => {
  closeAddCareerModal()
  loadTimeline()
  emit('updated')
}

const handleMilestoneSaved = () => {
  closeMilestoneModal()
  loadTimeline()
  emit('updated')
}

// Lifecycle
onMounted(() => {
  loadTimeline()
})
</script>

<style scoped>
.career-timeline {
  @apply max-w-4xl mx-auto;
}
</style>