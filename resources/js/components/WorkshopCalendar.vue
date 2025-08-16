<template>
  <div class="workshop-calendar">
    <div class="calendar-header">
      <h3 class="text-xl font-semibold text-gray-900">Alumni Workshops & Webinars</h3>
      <div class="header-actions">
        <button @click="showCreateModal = true" class="btn-primary">
          Host Workshop
        </button>
        <div class="view-toggle">
          <button
            @click="currentView = 'calendar'"
            :class="['view-btn', currentView === 'calendar' ? 'active' : '']"
          >
            <Icon name="calendar" class="w-4 h-4" />
            Calendar
          </button>
          <button
            @click="currentView = 'list'"
            :class="['view-btn', currentView === 'list' ? 'active' : '']"
          >
            <Icon name="list-bullet" class="w-4 h-4" />
            List
          </button>
        </div>
      </div>
    </div>

    <div class="calendar-filters">
      <div class="filter-row">
        <div class="filter-group">
          <select v-model="filters.category" @change="applyFilters" class="form-select">
            <option value="">All Categories</option>
            <option value="Technical">Technical Skills</option>
            <option value="Leadership">Leadership</option>
            <option value="Career">Career Development</option>
            <option value="Industry">Industry Insights</option>
            <option value="Networking">Networking</option>
          </select>
        </div>
        <div class="filter-group">
          <select v-model="filters.format" @change="applyFilters" class="form-select">
            <option value="">All Formats</option>
            <option value="workshop">Workshops</option>
            <option value="webinar">Webinars</option>
            <option value="panel">Panel Discussions</option>
            <option value="masterclass">Masterclasses</option>
          </select>
        </div>
        <div class="filter-group">
          <input
            v-model="filters.search"
            @input="applyFilters"
            type="text"
            placeholder="Search workshops..."
            class="form-input"
          />
        </div>
      </div>
    </div>

    <!-- Calendar View -->
    <div v-if="currentView === 'calendar'" class="calendar-view">
      <div class="calendar-navigation">
        <button @click="previousMonth" class="nav-btn">
          <Icon name="chevron-left" class="w-5 h-5" />
        </button>
        <h4 class="current-month">{{ formatMonth(currentDate) }}</h4>
        <button @click="nextMonth" class="nav-btn">
          <Icon name="chevron-right" class="w-5 h-5" />
        </button>
      </div>

      <div class="calendar-grid">
        <div class="calendar-header-row">
          <div v-for="day in weekDays" :key="day" class="calendar-header-cell">
            {{ day }}
          </div>
        </div>
        <div
          v-for="week in calendarWeeks"
          :key="week.weekNumber"
          class="calendar-week"
        >
          <div
            v-for="day in week.days"
            :key="day.date"
            class="calendar-day"
            :class="{
              'other-month': !day.isCurrentMonth,
              'today': day.isToday,
              'has-events': day.workshops.length > 0
            }"
          >
            <div class="day-number">{{ day.dayNumber }}</div>
            <div class="day-events">
              <div
                v-for="workshop in day.workshops.slice(0, 2)"
                :key="workshop.id"
                class="event-dot"
                :class="workshop.format"
                @click="selectWorkshop(workshop)"
                :title="workshop.title"
              ></div>
              <div
                v-if="day.workshops.length > 2"
                class="more-events"
                @click="showDayEvents(day)"
              >
                +{{ day.workshops.length - 2 }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-else class="list-view">
      <div class="workshops-list">
        <div
          v-for="workshop in filteredWorkshops"
          :key="workshop.id"
          class="workshop-card"
          @click="selectWorkshop(workshop)"
        >
          <div class="workshop-header">
            <div class="workshop-date">
              <div class="date-day">{{ formatDay(workshop.date) }}</div>
              <div class="date-month">{{ formatMonthShort(workshop.date) }}</div>
            </div>
            <div class="workshop-info">
              <h4 class="workshop-title">{{ workshop.title }}</h4>
              <p class="workshop-host">by {{ workshop.host.name }}</p>
              <div class="workshop-meta">
                <span class="workshop-format" :class="workshop.format">
                  {{ workshop.format }}
                </span>
                <span class="workshop-category">{{ workshop.category }}</span>
                <span class="workshop-time">
                  <Icon name="clock" class="w-4 h-4" />
                  {{ formatTime(workshop.start_time) }} - {{ formatTime(workshop.end_time) }}
                </span>
              </div>
            </div>
            <div class="workshop-actions">
              <div class="attendee-count">
                <Icon name="users" class="w-4 h-4" />
                {{ workshop.attendee_count }}/{{ workshop.max_attendees }}
              </div>
              <button
                @click.stop="toggleRegistration(workshop)"
                class="register-btn"
                :class="{ 'registered': workshop.is_registered }"
                :disabled="workshop.is_full && !workshop.is_registered"
              >
                {{ getRegistrationText(workshop) }}
              </button>
            </div>
          </div>
          <p class="workshop-description">{{ workshop.description }}</p>
        </div>
      </div>
    </div>

    <!-- Workshop Detail Modal -->
    <div v-if="selectedWorkshop" class="modal-overlay" @click="closeWorkshopModal">
      <div class="modal-content workshop-modal" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">{{ selectedWorkshop.title }}</h3>
          <button @click="closeWorkshopModal" class="close-btn">
            <Icon name="x-mark" class="w-6 h-6" />
          </button>
        </div>

        <div class="workshop-details">
          <div class="detail-section">
            <div class="host-info">
              <img
                :src="selectedWorkshop.host.avatar_url"
                :alt="selectedWorkshop.host.name"
                class="w-12 h-12 rounded-full"
              />
              <div>
                <h4 class="host-name">{{ selectedWorkshop.host.name }}</h4>
                <p class="host-title">{{ selectedWorkshop.host.title }}</p>
                <p class="host-company">{{ selectedWorkshop.host.company }}</p>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h5 class="section-title">Workshop Details</h5>
            <div class="details-grid">
              <div class="detail-item">
                <Icon name="calendar" class="w-5 h-5 text-gray-400" />
                <span>{{ formatFullDate(selectedWorkshop.date) }}</span>
              </div>
              <div class="detail-item">
                <Icon name="clock" class="w-5 h-5 text-gray-400" />
                <span>{{ formatTime(selectedWorkshop.start_time) }} - {{ formatTime(selectedWorkshop.end_time) }}</span>
              </div>
              <div class="detail-item">
                <Icon name="users" class="w-5 h-5 text-gray-400" />
                <span>{{ selectedWorkshop.attendee_count }}/{{ selectedWorkshop.max_attendees }} attendees</span>
              </div>
              <div class="detail-item">
                <Icon name="tag" class="w-5 h-5 text-gray-400" />
                <span>{{ selectedWorkshop.category }}</span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h5 class="section-title">Description</h5>
            <p class="workshop-description">{{ selectedWorkshop.description }}</p>
          </div>

          <div class="detail-section" v-if="selectedWorkshop.agenda">
            <h5 class="section-title">Agenda</h5>
            <ul class="agenda-list">
              <li v-for="item in selectedWorkshop.agenda" :key="item.id" class="agenda-item">
                <span class="agenda-time">{{ item.time }}</span>
                <span class="agenda-topic">{{ item.topic }}</span>
              </li>
            </ul>
          </div>

          <div class="detail-section" v-if="selectedWorkshop.prerequisites">
            <h5 class="section-title">Prerequisites</h5>
            <ul class="prerequisites-list">
              <li v-for="prereq in selectedWorkshop.prerequisites" :key="prereq">
                {{ prereq }}
              </li>
            </ul>
          </div>
        </div>

        <div class="modal-actions">
          <button
            @click="toggleRegistration(selectedWorkshop)"
            class="btn-primary"
            :disabled="selectedWorkshop.is_full && !selectedWorkshop.is_registered"
          >
            {{ getRegistrationText(selectedWorkshop) }}
          </button>
          <button @click="shareWorkshop(selectedWorkshop)" class="btn-secondary">
            <Icon name="share" class="w-4 h-4" />
            Share
          </button>
        </div>
      </div>
    </div>

    <!-- Create Workshop Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click="closeCreateModal">
      <div class="modal-content create-modal" @click.stop>
        <h3 class="modal-title">Host a Workshop</h3>

        <form @submit.prevent="createWorkshop">
          <div class="form-group">
            <label>Workshop Title *</label>
            <input
              v-model="newWorkshop.title"
              type="text"
              class="form-input"
              placeholder="e.g., Advanced React Patterns"
              required
            />
          </div>

          <div class="form-group">
            <label>Description *</label>
            <textarea
              v-model="newWorkshop.description"
              class="form-textarea"
              rows="4"
              placeholder="Describe what participants will learn..."
              required
            ></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Category *</label>
              <select v-model="newWorkshop.category" class="form-select" required>
                <option value="">Select category</option>
                <option value="Technical">Technical Skills</option>
                <option value="Leadership">Leadership</option>
                <option value="Career">Career Development</option>
                <option value="Industry">Industry Insights</option>
                <option value="Networking">Networking</option>
              </select>
            </div>
            <div class="form-group">
              <label>Format *</label>
              <select v-model="newWorkshop.format" class="form-select" required>
                <option value="">Select format</option>
                <option value="workshop">Workshop</option>
                <option value="webinar">Webinar</option>
                <option value="panel">Panel Discussion</option>
                <option value="masterclass">Masterclass</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Date *</label>
              <input
                v-model="newWorkshop.date"
                type="date"
                class="form-input"
                :min="today"
                required
              />
            </div>
            <div class="form-group">
              <label>Start Time *</label>
              <input
                v-model="newWorkshop.start_time"
                type="time"
                class="form-input"
                required
              />
            </div>
            <div class="form-group">
              <label>End Time *</label>
              <input
                v-model="newWorkshop.end_time"
                type="time"
                class="form-input"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label>Max Attendees</label>
            <input
              v-model.number="newWorkshop.max_attendees"
              type="number"
              min="1"
              max="500"
              class="form-input"
              placeholder="50"
            />
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeCreateModal" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Creating...' : 'Create Workshop' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import Icon from './Icon.vue'

export default {
  name: 'WorkshopCalendar',
  components: {
    Icon
  },
  data() {
    return {
      currentView: 'calendar',
      currentDate: new Date(),
      workshops: [],
      filteredWorkshops: [],
      selectedWorkshop: null,
      showCreateModal: false,
      loading: false,
      filters: {
        category: '',
        format: '',
        search: ''
      },
      newWorkshop: {
        title: '',
        description: '',
        category: '',
        format: '',
        date: '',
        start_time: '',
        end_time: '',
        max_attendees: 50
      },
      weekDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    }
  },
  computed: {
    today() {
      return new Date().toISOString().split('T')[0]
    },
    calendarWeeks() {
      const year = this.currentDate.getFullYear()
      const month = this.currentDate.getMonth()
      const firstDay = new Date(year, month, 1)
      const lastDay = new Date(year, month + 1, 0)
      const startDate = new Date(firstDay)
      startDate.setDate(startDate.getDate() - firstDay.getDay())
      
      const weeks = []
      let currentWeek = []
      const currentDate = new Date(startDate)
      
      for (let i = 0; i < 42; i++) {
        const dayWorkshops = this.workshops.filter(w => 
          new Date(w.date).toDateString() === currentDate.toDateString()
        )
        
        currentWeek.push({
          date: new Date(currentDate),
          dayNumber: currentDate.getDate(),
          isCurrentMonth: currentDate.getMonth() === month,
          isToday: currentDate.toDateString() === new Date().toDateString(),
          workshops: dayWorkshops
        })
        
        if (currentWeek.length === 7) {
          weeks.push({
            weekNumber: weeks.length + 1,
            days: currentWeek
          })
          currentWeek = []
        }
        
        currentDate.setDate(currentDate.getDate() + 1)
      }
      
      return weeks
    }
  },
  mounted() {
    this.loadWorkshops()
  },
  methods: {
    async loadWorkshops() {
      this.loading = true
      try {
        // Mock data - replace with actual API call
        this.workshops = [
          {
            id: 1,
            title: 'Advanced React Patterns',
            description: 'Learn advanced React patterns including render props, higher-order components, and hooks.',
            host: {
              name: 'Sarah Johnson',
              title: 'Senior Frontend Developer',
              company: 'Tech Corp',
              avatar_url: '/api/placeholder/40/40'
            },
            date: '2024-02-15',
            start_time: '14:00',
            end_time: '16:00',
            category: 'Technical',
            format: 'workshop',
            max_attendees: 30,
            attendee_count: 18,
            is_registered: false,
            is_full: false,
            agenda: [
              { id: 1, time: '14:00', topic: 'Introduction and Setup' },
              { id: 2, time: '14:30', topic: 'Render Props Pattern' },
              { id: 3, time: '15:15', topic: 'Higher-Order Components' },
              { id: 4, time: '15:45', topic: 'Custom Hooks' }
            ],
            prerequisites: ['Basic React knowledge', 'JavaScript ES6+']
          },
          {
            id: 2,
            title: 'Leadership in Tech',
            description: 'Develop leadership skills for technical professionals.',
            host: {
              name: 'Michael Chen',
              title: 'Engineering Manager',
              company: 'StartupXYZ',
              avatar_url: '/api/placeholder/40/40'
            },
            date: '2024-02-20',
            start_time: '18:00',
            end_time: '19:30',
            category: 'Leadership',
            format: 'webinar',
            max_attendees: 100,
            attendee_count: 45,
            is_registered: true,
            is_full: false
          }
        ]
        this.applyFilters()
      } catch (error) {
        console.error('Failed to load workshops:', error)
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      let filtered = [...this.workshops]
      
      if (this.filters.category) {
        filtered = filtered.filter(w => w.category === this.filters.category)
      }
      
      if (this.filters.format) {
        filtered = filtered.filter(w => w.format === this.filters.format)
      }
      
      if (this.filters.search) {
        const search = this.filters.search.toLowerCase()
        filtered = filtered.filter(w => 
          w.title.toLowerCase().includes(search) ||
          w.description.toLowerCase().includes(search) ||
          w.host.name.toLowerCase().includes(search)
        )
      }
      
      this.filteredWorkshops = filtered
    },
    previousMonth() {
      this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1)
    },
    nextMonth() {
      this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1)
    },
    selectWorkshop(workshop) {
      this.selectedWorkshop = workshop
    },
    closeWorkshopModal() {
      this.selectedWorkshop = null
    },
    async toggleRegistration(workshop) {
      try {
        const action = workshop.is_registered ? 'unregister' : 'register'
        // API call would go here
        workshop.is_registered = !workshop.is_registered
        workshop.attendee_count += workshop.is_registered ? 1 : -1
      } catch (error) {
        console.error('Failed to toggle registration:', error)
      }
    },
    getRegistrationText(workshop) {
      if (workshop.is_registered) return 'Registered'
      if (workshop.is_full) return 'Full'
      return 'Register'
    },
    shareWorkshop(workshop) {
      // Implementation for sharing workshop
      console.log('Share workshop:', workshop.title)
    },
    closeCreateModal() {
      this.showCreateModal = false
      this.resetNewWorkshop()
    },
    async createWorkshop() {
      this.loading = true
      try {
        // API call would go here
        console.log('Creating workshop:', this.newWorkshop)
        this.closeCreateModal()
        await this.loadWorkshops()
      } catch (error) {
        console.error('Failed to create workshop:', error)
      } finally {
        this.loading = false
      }
    },
    resetNewWorkshop() {
      this.newWorkshop = {
        title: '',
        description: '',
        category: '',
        format: '',
        date: '',
        start_time: '',
        end_time: '',
        max_attendees: 50
      }
    },
    formatMonth(date) {
      return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    },
    formatDay(date) {
      return new Date(date).getDate()
    },
    formatMonthShort(date) {
      return new Date(date).toLocaleDateString('en-US', { month: 'short' })
    },
    formatTime(time) {
      return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
      })
    },
    formatFullDate(date) {
      return new Date(date).toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.workshop-calendar {
  @apply space-y-6;
}

.calendar-header {
  @apply flex justify-between items-center;
}

.header-actions {
  @apply flex items-center space-x-4;
}

.view-toggle {
  @apply flex bg-gray-100 rounded-lg p-1;
}

.view-btn {
  @apply flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium transition-colors;
}

.view-btn.active {
  @apply bg-white text-blue-600 shadow-sm;
}

.calendar-filters {
  @apply bg-white p-4 rounded-lg border;
}

.filter-row {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.calendar-navigation {
  @apply flex justify-between items-center mb-4;
}

.nav-btn {
  @apply p-2 hover:bg-gray-100 rounded-lg transition-colors;
}

.current-month {
  @apply text-lg font-semibold text-gray-900;
}

.calendar-grid {
  @apply bg-white rounded-lg border overflow-hidden;
}

.calendar-header-row {
  @apply grid grid-cols-7 bg-gray-50;
}

.calendar-header-cell {
  @apply p-3 text-center text-sm font-medium text-gray-700 border-r border-gray-200;
}

.calendar-week {
  @apply grid grid-cols-7 border-b border-gray-200;
}

.calendar-day {
  @apply min-h-24 p-2 border-r border-gray-200 cursor-pointer hover:bg-gray-50;
}

.calendar-day.other-month {
  @apply text-gray-400 bg-gray-50;
}

.calendar-day.today {
  @apply bg-blue-50;
}

.calendar-day.has-events {
  @apply bg-blue-50;
}

.day-number {
  @apply text-sm font-medium mb-1;
}

.day-events {
  @apply space-y-1;
}

.event-dot {
  @apply w-2 h-2 rounded-full cursor-pointer;
}

.event-dot.workshop {
  @apply bg-blue-500;
}

.event-dot.webinar {
  @apply bg-green-500;
}

.event-dot.panel {
  @apply bg-purple-500;
}

.event-dot.masterclass {
  @apply bg-orange-500;
}

.more-events {
  @apply text-xs text-blue-600 cursor-pointer;
}

.workshops-list {
  @apply space-y-4;
}

.workshop-card {
  @apply bg-white p-6 rounded-lg border hover:shadow-md transition-shadow cursor-pointer;
}

.workshop-header {
  @apply flex items-start space-x-4 mb-3;
}

.workshop-date {
  @apply text-center bg-blue-50 rounded-lg p-3 min-w-16;
}

.date-day {
  @apply text-2xl font-bold text-blue-600;
}

.date-month {
  @apply text-sm text-blue-500;
}

.workshop-info {
  @apply flex-1;
}

.workshop-title {
  @apply text-lg font-semibold text-gray-900 mb-1;
}

.workshop-host {
  @apply text-sm text-gray-600 mb-2;
}

.workshop-meta {
  @apply flex items-center space-x-4 text-sm text-gray-500;
}

.workshop-format {
  @apply px-2 py-1 rounded-full text-xs font-medium;
}

.workshop-format.workshop {
  @apply bg-blue-100 text-blue-700;
}

.workshop-format.webinar {
  @apply bg-green-100 text-green-700;
}

.workshop-format.panel {
  @apply bg-purple-100 text-purple-700;
}

.workshop-format.masterclass {
  @apply bg-orange-100 text-orange-700;
}

.workshop-actions {
  @apply text-right;
}

.attendee-count {
  @apply flex items-center space-x-1 text-sm text-gray-600 mb-2;
}

.register-btn {
  @apply px-4 py-2 rounded-lg text-sm font-medium transition-colors;
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.register-btn.registered {
  @apply bg-green-600 hover:bg-green-700;
}

.register-btn:disabled {
  @apply bg-gray-300 text-gray-500 cursor-not-allowed;
}

.workshop-description {
  @apply text-gray-600 text-sm;
}

.workshop-modal {
  @apply max-w-2xl;
}

.modal-header {
  @apply flex justify-between items-center mb-6;
}

.close-btn {
  @apply p-1 hover:bg-gray-100 rounded-lg;
}

.workshop-details {
  @apply space-y-6 mb-6;
}

.detail-section {
  @apply space-y-3;
}

.host-info {
  @apply flex items-center space-x-4;
}

.host-name {
  @apply font-semibold text-gray-900;
}

.host-title {
  @apply text-sm text-gray-600;
}

.host-company {
  @apply text-sm text-gray-500;
}

.section-title {
  @apply font-semibold text-gray-900;
}

.details-grid {
  @apply grid grid-cols-2 gap-4;
}

.detail-item {
  @apply flex items-center space-x-2 text-sm text-gray-600;
}

.agenda-list {
  @apply space-y-2;
}

.agenda-item {
  @apply flex items-center space-x-4 p-2 bg-gray-50 rounded-lg;
}

.agenda-time {
  @apply text-sm font-medium text-blue-600 min-w-16;
}

.agenda-topic {
  @apply text-sm text-gray-700;
}

.prerequisites-list {
  @apply space-y-1;
}

.prerequisites-list li {
  @apply text-sm text-gray-600;
}

.prerequisites-list li::before {
  content: "•";
  @apply text-blue-500 mr-2;
}

.create-modal {
  @apply max-w-lg;
}

.form-row {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}
</style>