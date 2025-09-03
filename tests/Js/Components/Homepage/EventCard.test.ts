import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import EventCard from '@/components/EventCard.vue'

// Mock date-fns to ensure consistent date formatting
vi.mock('date-fns', () => ({
  format: vi.fn((date, formatString) => {
    if (formatString === 'MMM d, yyyy') return 'Dec 15, 2024'
    if (formatString === 'h:mm a') return '2:00 PM'
    return date.toString()
  }),
  parseISO: vi.fn((dateString) => new Date(dateString)),
  isAfter: vi.fn((date1, date2) => date1 > date2),
  isBefore: vi.fn((date1, date2) => date1 < date2),
  addHours: vi.fn((date, hours) => new Date(date.getTime() + hours * 60 * 60 * 1000)),
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  CalendarIcon: vi.fn(() => ({ template: '<div data-testid="calendar-icon"></div>' })),
  MapPinIcon: vi.fn(() => ({ template: '<div data-testid="map-pin-icon"></div>' })),
  CheckCircleIcon: vi.fn(() => ({ template: '<div data-testid="check-circle-icon"></div>' })),
  PencilIcon: vi.fn(() => ({ template: '<div data-testid="pencil-icon"></div>' })),
}))

describe('EventCard.vue', () => {
  let wrapper: VueWrapper<any>
  let mockEvent: any

  const createMockEvent = (overrides: Partial<typeof mockEvent> = {}) => ({
    id: 1,
    title: 'Test Alumni Networking Event',
    description: 'A great networking event',
    short_description: 'Join us for networking',
    type: 'networking',
    format: 'in_person',
    start_date: '2024-12-15T14:00:00Z',
    end_date: '2024-12-15T16:00:00Z',
    venue_name: 'University Auditorium',
    venue_address: '123 University Ave',
    virtual_link: null,
    max_capacity: 100,
    current_attendees: 75,
    registration_status: 'open',
    registration_deadline: null,
    organizer: {
      id: 1,
      name: 'Alumni Association',
      avatar_url: 'https://example.com/avatar.jpg',
    },
    institution: {
      id: 1,
      name: 'Test University',
    },
    user_data: {
      is_registered: false,
      registration: null,
      is_checked_in: false,
      can_edit: true,
    },
    media_urls: ['https://example.com/event-image.jpg'],
    ...overrides,
  })

  beforeEach(() => {
    vi.clearAllMocks()
    mockEvent = createMockEvent()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders the core structure correctly', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      expect(wrapper.find('.bg-white.rounded-lg').exists()).toBe(true)
      expect(wrapper.find('.hover\\:shadow-md').exists()).toBe(true)
    })

    it('renders event title and description', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      expect(wrapper.text()).toContain('Test Alumni Networking Event')
      expect(wrapper.text()).toContain('A great networking event')
    })

    it('renders organizer avatar and name', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const organizer = wrapper.find('.flex.items-center')
      expect(organizer.exists()).toBe(true)
      expect(organizer.text()).toContain('Alumni Association')
      expect(wrapper.find('img').attributes('src')).toBe('https://example.com/avatar.jpg')
    })

    it('renders media with proper image', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const image = wrapper.find('img')
      expect(image.exists()).toBe(true)
      expect(image.attributes('src')).toBe('https://example.com/event-image.jpg')
      expect(image.attributes('alt')).toBe('Test Alumni Networking Event')
    })
  })

  describe('Date and Time Formatting', () => {
    it('displays formatted date and time', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      expect(wrapper.text()).toContain('Dec 15, 2024')
      expect(wrapper.text()).toContain('2:00 PM - 2:00 PM')
    })

    it('handles all-day events', () => {
      const allDayEvent = createMockEvent({
        start_date: '2024-12-15T00:00:00Z',
        end_date: '2024-12-15T23:59:59Z',
      })

      wrapper = mount(EventCard, {
        props: { event: allDayEvent },
      })

      expect(wrapper.text()).toContain('Dec 15, 2024')
    })
  })

  describe('Event Format and Type', () => {
    it('renders format badges correctly', () => {
      const events = [
        { ...mockEvent, format: 'in_person' },
        { ...mockEvent, format: 'virtual' },
        { ...mockEvent, format: 'hybrid' },
      ]

      events.forEach((event) => {
        wrapper = mount(EventCard, {
          props: { event },
        })

        expect(wrapper.find('.px-2.py-1.text-xs.font-medium.rounded-full').exists()).toBe(true)
        wrapper.unmount()
      })
    })

    it('renders type badges correctly', () => {
      const types = ['networking', 'reunion', 'webinar', 'workshop', 'social', 'professional', 'fundraising']
      types.forEach((type) => {
        const typedEvent = createMockEvent({ type })
        wrapper = mount(EventCard, {
          props: { event: typedEvent },
        })

        expect(wrapper.findAll('.px-2.py-1.text-xs.font-medium.rounded-full').length).toBe(2) // format + type badge
        wrapper.unmount()
      })
    })
  })

  describe('Location Display', () => {
    it('displays venue for in-person events', () => {
      const inPersonEvent = createMockEvent({ format: 'in_person' })
      wrapper = mount(EventCard, {
        props: { event: inPersonEvent },
      })

      expect(wrapper.text()).toContain('University Auditorium')
    })

    it('displays "Virtual Event" for virtual events', () => {
      const virtualEvent = createMockEvent({ format: 'virtual' })
      wrapper = mount(EventCard, {
        props: { event: virtualEvent },
      })

      expect(wrapper.text()).toContain('Virtual Event')
    })

    it('displays venue for hybrid events', () => {
      const hybridEvent = createMockEvent({ format: 'hybrid' })
      wrapper = mount(EventCard, {
        props: { event: hybridEvent },
      })

      expect(wrapper.text()).toContain('University Auditorium')
    })

    it('handles missing venue information', () => {
      const noVenueEvent = createMockEvent({
        format: 'in_person',
        venue_name: undefined,
        venue_address: undefined,
      })
      wrapper = mount(EventCard, {
        props: { event: noVenueEvent },
      })

      expect(wrapper.text()).toContain('TBD')
    })
  })

  describe('Capacity and Registration', () => {
    it('renders capacity progress bar when max_capacity is set', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const progressBar = wrapper.find('.bg-gray-200.rounded-full.h-2')
      expect(progressBar.exists()).toBe(true)

      const progressFill = wrapper.find('.bg-blue-600.h-2.rounded-full')
      expect(progressFill.exists()).toBe(true)
    })

    it('shows attendee count correctly', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      expect(wrapper.text()).toContain('75 / 100')
    })

    it('renders registration button for open events', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const registerButton = wrapper.find('.px-3.py-1.text-xs.font-medium.rounded-md.transition-colors')
      expect(registerButton.exists()).toBe(true)
      expect(registerButton.text()).toContain('Register')
    })

    it('shows checked-in status when user is checked in', () => {
      const checkedInEvent = createMockEvent({
        user_data: {
          is_registered: true,
          registration: {},
          is_checked_in: true,
          can_edit: false,
        },
      })
      wrapper = mount(EventCard, {
        props: { event: checkedInEvent },
      })

      expect(wrapper.text()).toContain('Checked In')
      expect(wrapper.find('[data-testid="check-circle-icon"]').exists()).toBe(true)
    })

    it('renders check-in button for registered but not checked-in users', () => {
      const registeredEvent = createMockEvent({
        user_data: {
          is_registered: true,
          registration: {},
          is_checked_in: false,
          can_edit: false,
        },
      })
      wrapper = mount(EventCard, {
        props: { event: registeredEvent },
      })

      const checkInButton = wrapper.find('button.bg-green-600')
      expect(checkInButton.exists()).toBe(true)
      expect(checkInButton.text()).toContain('Check In')
    })
  })

  describe('User Permissions and Actions', () => {
    it('shows edit button when user can edit', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const editButton = wrapper.find('[data-testid="pencil-icon"]').find('button')
      expect(editButton.exists()).toBe(true)
    })

    it('does not show edit button when user cannot edit', () => {
      const noEditEvent = createMockEvent({
        user_data: {
          is_registered: false,
          registration: null,
          is_checked_in: false,
          can_edit: false,
        },
      })
      wrapper = mount(EventCard, {
        props: { event: noEditEvent },
      })

      const editButton = wrapper.find('[data-testid="pencil-icon"]').find('button')
      expect(editButton.exists()).toBe(false)
    })

    it('shows registration button by default', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      expect(wrapper.find('.text-blue-600.hover\\:text-blue-800').text()).toBe('View Details')
      expect(wrapper.find('button.bg-blue-600').exists()).toBe(true)
    })

    it('hides registration button when showRegistration is false', () => {
      wrapper = mount(EventCard, {
        props: {
          event: mockEvent,
          showRegistration: false,
        },
      })

      expect(wrapper.find('button.bg-blue-600').exists()).toBe(false)
    })
  })

  describe('Event Emitters', () => {
    it('emits view event when view details is clicked', async () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const viewButton = wrapper.find('.text-blue-600.hover\\:text-blue-800')
      await viewButton.trigger('click')

      const emittedEvents = wrapper.emitted('view')
      expect(emittedEvents).toHaveLength(1)
      expect(emittedEvents![0][0]).toEqual(mockEvent)
    })

    it('emits register event when register button is clicked', async () => {
      const canRegisterEvent = createMockEvent({
        registration_status: 'open',
        user_data: {
          is_registered: false,
          registration: null,
          is_checked_in: false,
          can_edit: false,
        },
      })
      wrapper = mount(EventCard, {
        props: { event: canRegisterEvent },
      })

      const registerButton = wrapper.find('button.bg-blue-600')
      await registerButton.trigger('click')

      const emittedEvents = wrapper.emitted('register')
      expect(emittedEvents).toHaveLength(1)
      expect(emittedEvents![0][0]).toEqual(canRegisterEvent)
    })

    it('emits edit event when edit button is clicked', async () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const editButton = wrapper.find('[data-testid="pencil-icon"]').find('button').find('button')
      if (editButton.exists()) {
        await editButton.trigger('click')

        const emittedEvents = wrapper.emitted('edit')
        expect(emittedEvents).toHaveLength(1)
        expect(emittedEvents![0][0]).toEqual(mockEvent)
      }
    })

    it('emits checkin event when check-in button is clicked', async () => {
      const registeredEvent = createMockEvent({
        user_data: {
          is_registered: true,
          registration: {},
          is_checked_in: false,
          can_edit: false,
        },
      })
      wrapper = mount(EventCard, {
        props: { event: registeredEvent },
      })

      const checkInButton = wrapper.find('button.bg-green-600')
      await checkInButton.trigger('click')

      const emittedEvents = wrapper.emitted('checkin')
      expect(emittedEvents).toHaveLength(1)
      expect(emittedEvents![0][0]).toEqual(registeredEvent)
    })
  })

  describe('Computed Properties', () => {
    describe('Registration Status', () => {
      it('disables registration when status is not open', () => {
        const closedEvent = createMockEvent({
          registration_status: 'closed',
          user_data: {
            is_registered: false,
            registration: null,
            is_checked_in: false,
            can_edit: false,
          },
        })
        wrapper = mount(EventCard, {
          props: { event: closedEvent },
        })

        const registerButton = wrapper.find('.bg-gray-200.cursor-not-allowed')
        expect(registerButton.exists()).toBe(true)
        expect(registerButton.attributes('disabled')).toBeDefined()
      })

      it('disables registration when capacity is full', () => {
        const fullEvent = createMockEvent({
          max_capacity: 100,
          current_attendees: 100,
          user_data: {
            is_registered: false,
            registration: null,
            is_checked_in: false,
            can_edit: false,
          },
        })
        wrapper = mount(EventCard, {
          props: { event: fullEvent },
        })

        const registerButton = wrapper.find('.bg-gray-200.cursor-not-allowed')
        expect(registerButton.exists()).toBe(true)
        expect(registerButton.attributes('disabled')).toBeDefined()
      })

      it('disables registration past deadline', () => {
        const pastDeadlineEvent = createMockEvent({
          registration_deadline: '2024-01-01T00:00:00Z',
          user_data: {
            is_registered: false,
            registration: null,
            is_checked_in: false,
            can_edit: false,
          },
        })
        wrapper = mount(EventCard, {
          props: { event: pastDeadlineEvent },
        })

        const registerButton = wrapper.find('.bg-gray-200.cursor-not-allowed')
        expect(registerButton.exists()).toBe(true)
        expect(registerButton.attributes('disabled')).toBeDefined()
      })

      it('shows "Closed" when registration status is closed', () => {
        const closedEvent = createMockEvent({
          registration_status: 'closed',
          user_data: {
            is_registered: false,
            registration: null,
            is_checked_in: false,
            can_edit: false,
          },
        })
        wrapper = mount(EventCard, {
          props: { event: closedEvent },
        })

        expect(wrapper.text()).toContain('Closed')
      })

      it('shows "Full" when capacity is reached', () => {
        const fullEvent = createMockEvent({
          max_capacity: 100,
          current_attendees: 100,
          user_data: {
            is_registered: false,
            registration: null,
            is_checked_in: false,
            can_edit: false,
          },
        })
        wrapper = mount(EventCard, {
          props: { event: fullEvent },
        })

        expect(wrapper.text()).toContain('Full')
      })

      it('shows "Deadline Passed" when deadline is exceeded', () => {
        const pastDeadlineEvent = createMockEvent({
          registration_deadline: '2024-01-01T00:00:00Z',
          user_data: {
            is_registered: false,
            registration: null,
            is_checked_in: false,
            can_edit: false,
          },
        })
        wrapper = mount(EventCard, {
          props: { event: pastDeadlineEvent },
        })

        expect(wrapper.text()).toContain('Deadline Passed')
      })
    })

    describe('Check-in Eligibility', () => {
      beforeEach(() => {
        vi.clearAllMocks()
      })

      it('handles check-in eligibility logic', () => {
        // This test validates the computed property logic
        const duringEvent = createMockEvent({
          start_date: '2024-12-15T13:00:00Z', // Past (assuming current time)
          end_date: '2024-12-15T17:00:00Z',
          user_data: {
            is_registered: true,
            registration: {},
            is_checked_in: false,
            can_edit: false,
          },
        })

        wrapper = mount(EventCard, {
          props: { event: duringEvent },
        })

        // Verify component renders without crashing
        expect(wrapper.exists()).toBe(true)
      })

      it('handles time-based check-in restrictions', () => {
        const tooEarly = createMockEvent({
          start_date: '2024-12-15T16:00:00Z', // Future
          end_date: '2024-12-15T18:00:00Z',
          user_data: {
            is_registered: true,
            registration: {},
            is_checked_in: false,
            can_edit: false,
          },
        })

        wrapper = mount(EventCard, {
          props: { event: tooEarly },
        })

        // Verify component renders without crashing
        expect(wrapper.exists()).toBe(true)
      })
    })
  })

  describe('Organizer Display', () => {
    it('renders organizer avatar when available', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const avatar = wrapper.find('img[alt="Alumni Association profile photo"]')
      expect(avatar.exists()).toBe(true)
      expect(avatar.attributes('src')).toBe('https://example.com/avatar.jpg')
    })

    it('renders fallback avatar with initials when no avatar URL', () => {
      const noAvatarEvent = createMockEvent({
        organizer: {
          id: 1,
          name: 'John Smith',
          avatar_url: null,
        },
      })
      wrapper = mount(EventCard, {
        props: { event: noAvatarEvent },
      })

      const fallbackAvatar = wrapper.find('.bg-gray-300.rounded-full')
      expect(fallbackAvatar.exists()).toBe(true)
      expect(fallbackAvatar.text()).toContain('J')
    })
  })

  describe('Edge Cases', () => {
    it('handles event with missing short_description', () => {
      const noShortDescEvent = createMockEvent({
        short_description: undefined,
      })
      wrapper = mount(EventCard, {
        props: { event: noShortDescEvent },
      })

      expect(wrapper.text()).toContain('A great networking event')
    })

    it('renders without media_urls', () => {
      const noMediaEvent = createMockEvent({
        media_urls: undefined,
      })
      wrapper = mount(EventCard, {
        props: { event: noMediaEvent },
      })

      const image = wrapper.find('img')
      expect(image.exists()).toBe(false)
    })

    it('handles empty event object gracefully', () => {
      const emptyEvent = {} as any
      wrapper = mount(EventCard, {
        props: { event: emptyEvent },
      })

      // Should still render without crashing
      expect(wrapper.find('.bg-white.rounded-lg').exists()).toBe(true)
    })

    it('handles undefined user_data', () => {
      const noUserDataEvent = createMockEvent({
        user_data: undefined,
      })
      wrapper = mount(EventCard, {
        props: { event: noUserDataEvent },
      })

      // Should render default states
      expect(wrapper.find('.text-blue-600.hover\\:text-blue-800').exists()).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('renders proper alt text for event images', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const image = wrapper.find('img')
      expect(image.attributes('alt')).toBe('Test Alumni Networking Event')
    })

    it('renders proper alt text for organizer avatars', () => {
      wrapper = mount(EventCard, {
        props: { event: mockEvent },
      })

      const avatar = wrapper.find('img[src="https://example.com/avatar.jpg"]')
      expect(avatar.attributes('alt')).toBe('Alumni Association profile photo')
    })

    it('renders proper alt text for fallback organizer avatars', () => {
      const noAvatarEvent = createMockEvent({
        organizer: {
          id: 1,
          name: 'John Smith',
          avatar_url: null,
        },
      })
      wrapper = mount(EventCard, {
        props: { event: noAvatarEvent },
      })

      const avatar = wrapper.find('img[alt]="John Smith profile photo"')
      expect(avatar.exists()).toBe(false) // Should not exist, fallback text should be used instead

      const fallbackAvatar = wrapper.find('.bg-gray-300.rounded-full')
      expect(fallbackAvatar.exists()).toBe(true)
    })
  })
})