<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ mode === 'schedule' ? 'Schedule Session' : 'Availability Calendar' }}
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ mode === 'schedule' ? 'Find available time slots for mentorship sessions' : 'Manage your availability for mentorship' }}
          </p>
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="goToPreviousWeek"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <span class="text-sm font-medium text-gray-900 dark:text-white">
            {{ formatWeekRange(currentWeek) }}
          </span>
          <button
            @click="goToNextWeek"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Participants (for scheduling mode) -->
      <div v-if="mode === 'schedule' && participants.length > 0" class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Participants</h4>
        <div class="flex flex-wrap gap-2">
          <div
            v-for="participant in participants"
            :key="participant.id"
            class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
          >
            <img
              v-if="participant.avatar_url"
              :src="participant.avatar_url"
              :alt="participant.name"
              class="w-5 h-5 rounded-full mr-2"
            >
            <div v-else class="w-5 h-5 rounded-full bg-gray-300 dark:bg-gray-600 mr-2"></div>
            {{ participant.name }}
          </div>
        </div>
      </div>

      <!-- Session Duration Selector -->
      <div v-if="mode === 'schedule'" class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Session Duration
        </label>
        <select
          v-model="sessionDuration"
          @change="fetchAvailableSlots"
          class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option value="30">30 minutes</option>
          <option value="60">1 hour</option>
          <option value="90">1.5 hours</option>
          <option value="120">2 hours</option>
        </select>
      </div>

      <!-- Calendar Grid -->
      <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
        <!-- Header with days -->
        <div class="grid grid-cols-8 bg-gray-50 dark:bg-gray-700">
          <div class="p-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Time
          </div>
          <div
            v-for="day in weekDays"
            :key="day.date"
            class="p-3 text-center border-l border-gray-200 dark:border-gray-600"
          >
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
              {{ day.dayName }}
            </div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
              {{ day.dayNumber }}
            </div>
          </div>
        </div>

        <!-- Time slots -->
        <div class="divide-y divide-gray-200 dark:divide-gray-600">
          <div
            v-for="timeSlot in timeSlots"
            :key="timeSlot.time"
            class="grid grid-cols-8 hover:bg-gray-50 dark:hover:bg-gray-700/50"
          >
            <!-- Time column -->
            <div class="p-3 text-sm text-gray-500 dark:text-gray-400 font-medium">
              {{ timeSlot.time }}
            </div>

            <!-- Day columns -->
            <div
              v-for="day in weekDays"
              :key="`${day.date}-${timeSlot.time}`"
              class="border-l border-gray-200 dark:border-gray-600 p-1"
            >
              <div
                v-if="mode === 'schedule'"
                class="h-12 relative"
              >
                <!-- Available slot -->
                <button
                  v-if="isSlotAvailable(day.date, timeSlot.time)"
                  @click="selectTimeSlot(day.date, timeSlot.time)"
                  class="w-full h-full rounded bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 border border-green-300 dark:border-green-700 flex items-center justify-center text-xs text-green-800 dark:text-green-200 font-medium transition-colors"
                >
                  Available
                </button>

                <!-- Busy slot -->
                <div
                  v-else-if="isSlotBusy(day.date, timeSlot.time)"
                  class="w-full h-full rounded bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 flex items-center justify-center text-xs text-red-800 dark:text-red-200 font-medium"
                >
                  Busy
                </div>

                <!-- No data -->
                <div
                  v-else
                  class="w-full h-full rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"
                ></div>
              </div>

              <div
                v-else
                class="h-12 relative"
              >
                <!-- Availability management mode -->
                <button
                  @click="toggleAvailability(day.date, timeSlot.time)"
                  class="w-full h-full rounded border transition-colors"
                  :class="{
                    'bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 border-green-300 dark:border-green-700 text-green-800 dark:text-green-200': isUserAvailable(day.date, timeSlot.time),
                    'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400': !isUserAvailable(day.date, timeSlot.time)
                  }"
                >
                  <span class="text-xs font-medium">
                    {{ isUserAvailable(day.date, timeSlot.time) ? 'Available' : 'Unavailable' }}
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Selected Time Slot (for scheduling mode) -->
      <div v-if="mode === 'schedule' && selectedSlot" class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Selected Time Slot</h4>
        <p class="text-sm text-blue-800 dark:text-blue-200">
          {{ formatSelectedSlot(selectedSlot) }}
        </p>
        <div class="mt-4 flex items-center space-x-3">
          <button
            @click="scheduleSession"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Schedule Session
          </button>
          <button
            @click="selectedSlot = null"
            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
          >
            Cancel
          </button>
        </div>
      </div>

      <!-- Working Hours Settings (for availability mode) -->
      <div v-if="mode === 'availability'" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Working Hours</h4>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
            <select
              v-model="workingHours.start"
              @change="updateWorkingHours"
              class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
            >
              <option v-for="time in availableTimes" :key="time" :value="time">{{ time }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
            <select
              v-model="workingHours.end"
              @change="updateWorkingHours"
              class="block w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:text-white"
            >
              <option v-for="time in availableTimes" :key="time" :value="time">{{ time }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'

interface User {
  id: number
  name: string
  email: string
  avatar_url?: string
}

interface TimeSlot {
  date: string
  time: string
  available: boolean
  participants?: User[]
}

interface SelectedSlot {
  date: string
  time: string
  datetime: string
}

interface Props {
  mode?: 'availability' | 'schedule'
  participants?: User[]
  mentorId?: number
  menteeId?: number
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'availability',
  participants: () => [],
})

const emit = defineEmits<{
  sessionScheduled: [session: any]
}>()

const loading = ref(false)
const currentWeek = ref(new Date())
const sessionDuration = ref(60)
const selectedSlot = ref<SelectedSlot | null>(null)
const availableSlots = ref<TimeSlot[]>([])
const userAvailability = ref<Record<string, boolean>>({})

const workingHours = ref({
  start: '09:00',
  end: '17:00'
})

const timeSlots = computed(() => {
  const slots = []
  const start = parseInt(workingHours.value.start.split(':')[0])
  const end = parseInt(workingHours.value.end.split(':')[0])
  
  for (let hour = start; hour < end; hour++) {
    slots.push({
      time: `${hour.toString().padStart(2, '0')}:00`,
      hour
    })
    slots.push({
      time: `${hour.toString().padStart(2, '0')}:30`,
      hour
    })
  }
  
  return slots
})

const weekDays = computed(() => {
  const days = []
  const startOfWeek = new Date(currentWeek.value)
  startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay())
  
  for (let i = 0; i < 7; i++) {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + i)
    
    days.push({
      date: date.toISOString().split('T')[0],
      dayName: date.toLocaleDateString('en-US', { weekday: 'short' }),
      dayNumber: date.getDate(),
      fullDate: date
    })
  }
  
  return days
})

const availableTimes = computed(() => {
  const times = []
  for (let hour = 6; hour <= 22; hour++) {
    times.push(`${hour.toString().padStart(2, '0')}:00`)
    times.push(`${hour.toString().padStart(2, '0')}:30`)
  }
  return times
})

const formatWeekRange = (date: Date): string => {
  const startOfWeek = new Date(date)
  startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay())
  
  const endOfWeek = new Date(startOfWeek)
  endOfWeek.setDate(startOfWeek.getDate() + 6)
  
  return `${startOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`
}

const formatSelectedSlot = (slot: SelectedSlot): string => {
  const date = new Date(slot.datetime)
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const goToPreviousWeek = (): void => {
  const newWeek = new Date(currentWeek.value)
  newWeek.setDate(newWeek.getDate() - 7)
  currentWeek.value = newWeek
}

const goToNextWeek = (): void => {
  const newWeek = new Date(currentWeek.value)
  newWeek.setDate(newWeek.getDate() + 7)
  currentWeek.value = newWeek
}

const isSlotAvailable = (date: string, time: string): boolean => {
  return availableSlots.value.some(slot => 
    slot.date === date && slot.time === time && slot.available
  )
}

const isSlotBusy = (date: string, time: string): boolean => {
  return availableSlots.value.some(slot => 
    slot.date === date && slot.time === time && !slot.available
  )
}

const isUserAvailable = (date: string, time: string): boolean => {
  const key = `${date}-${time}`
  return userAvailability.value[key] || false
}

const selectTimeSlot = (date: string, time: string): void => {
  const datetime = `${date}T${time}:00`
  selectedSlot.value = {
    date,
    time,
    datetime
  }
}

const toggleAvailability = (date: string, time: string): void => {
  const key = `${date}-${time}`
  userAvailability.value[key] = !userAvailability.value[key]
  
  // Save to backend
  saveAvailability(date, time, userAvailability.value[key])
}

const fetchAvailableSlots = async (): Promise<void> => {
  if (props.mode !== 'schedule' || props.participants.length === 0) return
  
  try {
    loading.value = true
    
    const startDate = weekDays.value[0].date
    const endDate = weekDays.value[6].date
    const userIds = props.participants.map(p => p.id)
    
    const response = await fetch('/api/calendar/find-slots', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        user_ids: userIds,
        start_date: startDate,
        end_date: endDate,
        duration_minutes: sessionDuration.value,
        working_hours: [workingHours.value.start, workingHours.value.end]
      })
    })
    
    if (response.ok) {
      const data = await response.json()
      availableSlots.value = data.available_slots.map((slot: any) => ({
        date: slot.date,
        time: slot.time,
        available: true
      }))
    }
  } catch (error) {
    console.error('Failed to fetch available slots:', error)
  } finally {
    loading.value = false
  }
}

const fetchUserAvailability = async (): Promise<void> => {
  if (props.mode !== 'availability') return
  
  try {
    const startDate = weekDays.value[0].date
    const endDate = weekDays.value[6].date
    
    const response = await fetch(`/api/calendar/availability?start_date=${startDate}&end_date=${endDate}`)
    
    if (response.ok) {
      const data = await response.json()
      // Process availability data
      userAvailability.value = {}
      data.availability.forEach((slot: any) => {
        const date = slot.start.split('T')[0]
        const time = slot.start.split('T')[1].substring(0, 5)
        const key = `${date}-${time}`
        userAvailability.value[key] = false // Busy time
      })
    }
  } catch (error) {
    console.error('Failed to fetch user availability:', error)
  }
}

const saveAvailability = async (date: string, time: string, available: boolean): Promise<void> => {
  try {
    // This would save availability to the backend
    console.log(`Saving availability: ${date} ${time} = ${available}`)
  } catch (error) {
    console.error('Failed to save availability:', error)
  }
}

const updateWorkingHours = (): void => {
  // Update working hours and refresh data
  if (props.mode === 'schedule') {
    fetchAvailableSlots()
  } else {
    fetchUserAvailability()
  }
}

const scheduleSession = async (): Promise<void> => {
  if (!selectedSlot.value || !props.mentorId || !props.menteeId) return
  
  try {
    loading.value = true
    
    const response = await fetch('/api/calendar/schedule-mentorship', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        mentor_id: props.mentorId,
        mentee_id: props.menteeId,
        start_time: selectedSlot.value.datetime,
        duration_minutes: sessionDuration.value,
        topic: 'Mentorship Session'
      })
    })
    
    if (response.ok) {
      const data = await response.json()
      emit('sessionScheduled', data.session)
      selectedSlot.value = null
      await fetchAvailableSlots() // Refresh available slots
    } else {
      console.error('Failed to schedule session')
    }
  } catch (error) {
    console.error('Error scheduling session:', error)
  } finally {
    loading.value = false
  }
}

// Watch for week changes
watch(currentWeek, () => {
  if (props.mode === 'schedule') {
    fetchAvailableSlots()
  } else {
    fetchUserAvailability()
  }
})

// Watch for participants changes
watch(() => props.participants, () => {
  if (props.mode === 'schedule') {
    fetchAvailableSlots()
  }
}, { deep: true })

onMounted(() => {
  if (props.mode === 'schedule') {
    fetchAvailableSlots()
  } else {
    fetchUserAvailability()
  }
})
</script>