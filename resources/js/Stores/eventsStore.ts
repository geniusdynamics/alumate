import { defineStore } from 'pinia'
import axios from 'axios'

interface Event {
  id: number
  title: string
  description: string
  short_description?: string
  type: string
  format: string
  start_date: string
  end_date: string
  venue_name?: string
  venue_address?: string
  virtual_link?: string
  max_capacity?: number
  current_attendees: number
  organizer: {
    id: number
    name: string
    avatar_url?: string
  }
  institution?: {
    id: number
    name: string
  }
  user_data?: {
    is_registered: boolean
    registration?: any
    is_checked_in: boolean
    can_edit: boolean
  }
}

interface EventsResponse {
  success: boolean
  data: Event[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

interface SingleEventResponse {
  success: boolean
  data: Event
}

interface EventFilters {
  type?: string
  format?: string
  date_range?: string
  location?: { lat: number; lng: number }
  radius?: number
  tags?: string[]
  search?: string
  page?: number
  per_page?: number
}

export const useEventsStore = defineStore('events', {
  state: () => ({
    events: [] as Event[],
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async getEvents(filters: EventFilters = {}): Promise<EventsResponse> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get('/api/events', {
          params: filters
        })

        if (response.data.success) {
          return response.data
        } else {
          throw new Error(response.data.message || 'Failed to fetch events')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to fetch events'
        throw error
      } finally {
        this.loading = false
      }
    },

    async getEvent(eventId: number): Promise<Event> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get(`/api/events/${eventId}`)

        if (response.data.success) {
          return response.data.data
        } else {
          throw new Error(response.data.message || 'Failed to fetch event')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to fetch event'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createEvent(eventData: any): Promise<Event> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post('/api/events', eventData)

        if (response.data.success) {
          return response.data.data
        } else {
          throw new Error(response.data.message || 'Failed to create event')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to create event'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateEvent(eventId: number, eventData: any): Promise<Event> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.put(`/api/events/${eventId}`, eventData)

        if (response.data.success) {
          return response.data.data
        } else {
          throw new Error(response.data.message || 'Failed to update event')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to update event'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteEvent(eventId: number): Promise<void> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.delete(`/api/events/${eventId}`)

        if (!response.data.success) {
          throw new Error(response.data.message || 'Failed to delete event')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to delete event'
        throw error
      } finally {
        this.loading = false
      }
    },

    async registerForEvent(eventId: number, registrationData: any = {}): Promise<any> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post(`/api/events/${eventId}/register`, registrationData)

        if (response.data.success) {
          return response.data.data
        } else {
          throw new Error(response.data.message || 'Failed to register for event')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to register for event'
        throw error
      } finally {
        this.loading = false
      }
    },

    async cancelRegistration(eventId: number, reason?: string): Promise<void> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.delete(`/api/events/${eventId}/register`, {
          data: { reason }
        })

        if (!response.data.success) {
          throw new Error(response.data.message || 'Failed to cancel registration')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to cancel registration'
        throw error
      } finally {
        this.loading = false
      }
    },

    async checkInToEvent(eventId: number, checkInData: any = {}): Promise<any> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post(`/api/events/${eventId}/checkin`, checkInData)

        if (response.data.success) {
          return response.data.data
        } else {
          throw new Error(response.data.message || 'Failed to check in to event')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to check in to event'
        throw error
      } finally {
        this.loading = false
      }
    },

    async getEventAttendees(eventId: number, status: string = 'all'): Promise<any[]> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get(`/api/events/${eventId}/attendees`, {
          params: { status }
        })

        if (response.data.success) {
          return response.data.data
        } else {
          throw new Error(response.data.message || 'Failed to fetch attendees')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to fetch attendees'
        throw error
      } finally {
        this.loading = false
      }
    },

    async getEventAnalytics(eventId: number): Promise<any> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get(`/api/events/${eventId}/analytics`)

        if (response.data.success) {
          return response.data.data
        } else {
          throw new Error(response.data.message || 'Failed to fetch analytics')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to fetch analytics'
        throw error
      } finally {
        this.loading = false
      }
    },

    async getUpcomingEvents(limit: number = 5): Promise<{ data: Event[] }> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get('/api/events-upcoming', {
          params: { limit }
        })

        if (response.data.success) {
          return response.data
        } else {
          throw new Error(response.data.message || 'Failed to fetch upcoming events')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to fetch upcoming events'
        throw error
      } finally {
        this.loading = false
      }
    },

    async getRecommendedEvents(limit: number = 10): Promise<{ data: Event[] }> {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get('/api/events-recommended', {
          params: { limit }
        })

        if (response.data.success) {
          return response.data
        } else {
          throw new Error(response.data.message || 'Failed to fetch recommended events')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Failed to fetch recommended events'
        throw error
      } finally {
        this.loading = false
      }
    },

    clearError() {
      this.error = null
    }
  }
})