import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SuccessStories from '@/components/homepage/SuccessStories.vue'
import SuccessStoryCard from '@/components/homepage/SuccessStoryCard.vue'
import SuccessStoryFilters from '@/components/homepage/SuccessStoryFilters.vue'
import VideoTestimonialModal from '@/components/homepage/VideoTestimonialModal.vue'
import type { SuccessStory, StoryFilter } from '@/types/homepage'

// Mock success stories data
const mockSuccessStories: SuccessStory[] = [
  {
    id: '1',
    title: 'From Junior Developer to Tech Lead',
    summary: 'How networking through our platform helped me advance my career in just 18 months.',
    alumniProfile: {
      id: 'alumni-1',
      name: 'John Smith',
      graduationYear: 2018,
      degree: 'Computer Science',
      currentRole: 'Senior Tech Lead',
      currentCompany: 'TechCorp Inc.',
      industry: 'Technology',
      location: 'San Francisco, CA',
      profileImage: '/images/john-smith.jpg',
      linkedinUrl: 'https://linkedin.com/in/johnsmith',
      careerStage: 'mid_career',
      specialties: ['JavaScript', 'React', 'Node.js'],
      mentorshipAvailable: true
    },
    careerProgression: {
      before: {
        role: 'Junior Developer',
        company: 'StartupCo',
        salary: 65000,
        level: 'Junior',
        responsibilities: ['Bug fixes', 'Basic features']
      },
      after: {
        role: 'Senior Tech Lead',
        company: 'TechCorp Inc.',
        salary: 145000,
        level: 'Senior',
        responsibilities: ['Team leadership', 'Architecture decisions', 'Mentoring']
      },
      timeframe: '18 months',
      keyMilestones: []
    },
    platformImpact: {
      connectionsMade: 45,
      mentorsWorkedWith: 3,
      referralsReceived: 7,
      eventsAttended: 12,
      skillsAcquired: ['Leadership', 'System Design', 'Team Management'],
      networkGrowth: 200
    },
    testimonialVideo: 'https://youtube.com/watch?v=example123',
    metrics: [
      {
        type: 'salary_increase',
        value: 123,
        unit: 'percentage',
        timeframe: '18 months',
        verified: true
      }
    ],
    industry: 'Technology',
    graduationYear: 2018,
    featured: false,
    tags: ['career-advancement', 'networking', 'technology']
  },
  {
    id: '2',
    title: 'Breaking into Finance',
    summary: 'Transitioned from education to finance with help from alumni mentors.',
    alumniProfile: {
      id: 'alumni-2',
      name: 'Sarah Johnson',
      graduationYear: 2019,
      degree: 'Business Administration',
      currentRole: 'Financial Analyst',
      currentCompany: 'Goldman Sachs',
      industry: 'Finance',
      location: 'New York, NY',
      profileImage: '/images/sarah-johnson.jpg',
      careerStage: 'mid_career',
      specialties: ['Financial Analysis', 'Risk Management'],
      mentorshipAvailable: false
    },
    careerProgression: {
      before: {
        role: 'High School Teacher',
        company: 'Public School District',
        salary: 45000,
        level: 'Entry',
        responsibilities: ['Teaching', 'Curriculum development']
      },
      after: {
        role: 'Financial Analyst',
        company: 'Goldman Sachs',
        salary: 95000,
        level: 'Mid',
        responsibilities: ['Financial modeling', 'Risk analysis']
      },
      timeframe: '2 years',
      keyMilestones: []
    },
    platformImpact: {
      connectionsMade: 32,
      mentorsWorkedWith: 2,
      referralsReceived: 4,
      eventsAttended: 8,
      skillsAcquired: ['Financial Modeling', 'Excel', 'Python'],
      networkGrowth: 150
    },
    metrics: [
      {
        type: 'job_placement',
        value: 1,
        unit: 'count',
        timeframe: '2 years',
        verified: true
      }
    ],
    industry: 'Finance',
    graduationYear: 2019,
    featured: true,
    tags: ['career-change', 'mentorship', 'finance']
  }
]

const mockFilters: StoryFilter[] = [
  {
    key: 'industry',
    label: 'Industry',
    type: 'select',
    options: [
      { value: 'technology', label: 'Technology', count: 1 },
      { value: 'finance', label: 'Finance', count: 1 }
    ]
  },
  {
    key: 'graduationYear',
    label: 'Graduation Year',
    type: 'select',
    options: [
      { value: '2018', label: '2018', count: 1 },
      { value: '2019', label: '2019', count: 1 }
    ]
  },
  {
    key: 'careerStage',
    label: 'Career Stage',
    type: 'select',
    options: [
      { value: 'mid_career', label: 'Mid-Career', count: 2 }
    ]
  },
  {
    key: 'successType',
    label: 'Success Type',
    type: 'select',
    options: [
      { value: 'salary_increase', label: 'Salary Increase', count: 1 },
      { value: 'job_placement', label: 'Job Placement', count: 1 }
    ]
  }
]

// Mock window.open for social sharing tests
Object.defineProperty(window, 'open', {
  writable: true,
  value: vi.fn()
})

// Mock navigator.clipboard for copy functionality
Object.defineProperty(navigator, 'clipboard', {
  writable: true,
  value: {
    writeText: vi.fn().mockResolvedValue(undefined)
  }
})

describe('SuccessStories', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(SuccessStories, {
      props: {
        stories: mockSuccessStories,
        filters: mockFilters,
        featuredStory: mockSuccessStories[1], // Sarah's story is featured
        itemsPerPage: 9
      }
    })
  })

  describe('Basic Rendering', () => {
    it('renders the success stories section', () => {
      expect(wrapper.find('.success-stories').exists()).toBe(true)
      expect(wrapper.find('#success-stories').exists()).toBe(true)
    })

    it('displays section header', () => {
      expect(wrapper.text()).toContain('Alumni Success Stories')
      expect(wrapper.text()).toContain('Discover how our platform has transformed careers')
    })

    it('renders featured story when provided', () => {
      expect(wrapper.text()).toContain('Featured Success Story')
      expect(wrapper.text()).toContain('Breaking into Finance')
    })

    it('renders filters component', () => {
      expect(wrapper.findComponent(SuccessStoryFilters).exists()).toBe(true)
    })

    it('renders stories grid', () => {
      expect(wrapper.find('.stories-grid').exists()).toBe(true)
      expect(wrapper.findAllComponents(SuccessStoryCard)).toHaveLength(2)
    })

    it('renders call to action section', () => {
      expect(wrapper.text()).toContain('Ready to Write Your Success Story?')
      expect(wrapper.text()).toContain('Join Now')
      expect(wrapper.text()).toContain('Learn More')
    })
  })

  describe('Featured Story', () => {
    it('displays featured story separately', () => {
      const featuredCard = wrapper.find('.featured-story-card')
      expect(featuredCard.exists()).toBe(true)
    })

    it('does not show featured story section when no featured story provided', () => {
      wrapper = mount(SuccessStories, {
        props: {
          stories: mockSuccessStories,
          filters: mockFilters
        }
      })
      
      expect(wrapper.find('.featured-story-container').exists()).toBe(false)
    })
  })

  describe('Filtering', () => {
    it('filters stories by industry', async () => {
      const filtersComponent = wrapper.findComponent(SuccessStoryFilters)
      await filtersComponent.vm.$emit('filter-change', { industry: 'technology' })
      
      await wrapper.vm.$nextTick()
      
      // Should only show technology story (John Smith)
      const storyCards = wrapper.findAllComponents(SuccessStoryCard)
      expect(storyCards).toHaveLength(1)
      expect(wrapper.text()).toContain('John Smith')
      expect(wrapper.text()).not.toContain('Sarah Johnson')
    })

    it('filters stories by graduation year', async () => {
      const filtersComponent = wrapper.findComponent(SuccessStoryFilters)
      await filtersComponent.vm.$emit('filter-change', { graduationYear: '2019' })
      
      await wrapper.vm.$nextTick()
      
      // Should only show 2019 graduate (Sarah Johnson)
      const storyCards = wrapper.findAllComponents(SuccessStoryCard)
      expect(storyCards).toHaveLength(1)
      expect(wrapper.text()).toContain('Sarah Johnson')
      expect(wrapper.text()).not.toContain('John Smith')
    })

    it('filters stories by career stage', async () => {
      const filtersComponent = wrapper.findComponent(SuccessStoryFilters)
      await filtersComponent.vm.$emit('filter-change', { careerStage: 'mid_career' })
      
      await wrapper.vm.$nextTick()
      
      // Should show both stories (both are mid_career)
      const storyCards = wrapper.findAllComponents(SuccessStoryCard)
      expect(storyCards).toHaveLength(2)
    })

    it('filters stories by success type', async () => {
      const filtersComponent = wrapper.findComponent(SuccessStoryFilters)
      await filtersComponent.vm.$emit('filter-change', { successType: 'salary_increase' })
      
      await wrapper.vm.$nextTick()
      
      // Should only show John Smith's story
      const storyCards = wrapper.findAllComponents(SuccessStoryCard)
      expect(storyCards).toHaveLength(1)
      expect(wrapper.text()).toContain('John Smith')
    })

    it('combines multiple filters', async () => {
      const filtersComponent = wrapper.findComponent(SuccessStoryFilters)
      await filtersComponent.vm.$emit('filter-change', { 
        industry: 'technology',
        graduationYear: '2018'
      })
      
      await wrapper.vm.$nextTick()
      
      // Should only show John Smith's story (matches both filters)
      const storyCards = wrapper.findAllComponents(SuccessStoryCard)
      expect(storyCards).toHaveLength(1)
      expect(wrapper.text()).toContain('John Smith')
    })

    it('shows no results when filters match no stories', async () => {
      const filtersComponent = wrapper.findComponent(SuccessStoryFilters)
      await filtersComponent.vm.$emit('filter-change', { 
        industry: 'technology',
        graduationYear: '2019' // No tech stories from 2019
      })
      
      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.no-results-container').exists()).toBe(true)
      expect(wrapper.text()).toContain('No success stories found')
      expect(wrapper.text()).toContain('Try adjusting your filters')
    })
  })

  describe('Pagination', () => {
    beforeEach(() => {
      // Create more stories to test pagination
      const manyStories = Array.from({ length: 15 }, (_, i) => ({
        ...mockSuccessStories[0],
        id: `story-${i}`,
        title: `Story ${i + 1}`
      }))
      
      wrapper = mount(SuccessStories, {
        props: {
          stories: manyStories,
          filters: mockFilters,
          itemsPerPage: 5
        }
      })
    })

    it('shows pagination when there are multiple pages', () => {
      expect(wrapper.find('.pagination-container').exists()).toBe(true)
      expect(wrapper.find('.pagination-nav').exists()).toBe(true)
    })

    it('displays correct number of stories per page', () => {
      const storyCards = wrapper.findAllComponents(SuccessStoryCard)
      expect(storyCards).toHaveLength(5) // itemsPerPage = 5
    })

    it('shows pagination info', () => {
      expect(wrapper.text()).toContain('Showing 1-5 of 15 stories')
    })

    it('navigates to next page', async () => {
      const nextButton = wrapper.find('.next-button')
      await nextButton.trigger('click')
      
      expect(wrapper.text()).toContain('Showing 6-10 of 15 stories')
    })

    it('navigates to previous page', async () => {
      // Go to page 2 first
      const nextButton = wrapper.find('.next-button')
      await nextButton.trigger('click')
      
      // Then go back to page 1
      const prevButton = wrapper.find('.prev-button')
      await prevButton.trigger('click')
      
      expect(wrapper.text()).toContain('Showing 1-5 of 15 stories')
    })

    it('disables previous button on first page', () => {
      const prevButton = wrapper.find('.prev-button')
      expect(prevButton.attributes('disabled')).toBeDefined()
    })

    it('disables next button on last page', async () => {
      // Navigate to last page
      const pageNumbers = wrapper.findAll('.pagination-number')
      const lastPageButton = pageNumbers[pageNumbers.length - 1]
      await lastPageButton.trigger('click')
      
      const nextButton = wrapper.find('.next-button')
      expect(nextButton.attributes('disabled')).toBeDefined()
    })

    it('navigates to specific page when page number is clicked', async () => {
      const pageNumbers = wrapper.findAll('.pagination-number')
      const page2Button = pageNumbers.find(btn => btn.text() === '2')
      
      if (page2Button) {
        await page2Button.trigger('click')
        expect(wrapper.text()).toContain('Showing 6-10 of 15 stories')
      }
    })
  })

  describe('Video Modal', () => {
    it('opens video modal when play video is emitted', async () => {
      const storyCard = wrapper.findComponent(SuccessStoryCard)
      await storyCard.vm.$emit('play-video', 'https://youtube.com/watch?v=example123')
      
      const videoModal = wrapper.findComponent(VideoTestimonialModal)
      expect(videoModal.props('isOpen')).toBe(true)
      expect(videoModal.props('videoUrl')).toBe('https://youtube.com/watch?v=example123')
    })

    it('closes video modal when close is emitted', async () => {
      // Open modal first
      const storyCard = wrapper.findComponent(SuccessStoryCard)
      await storyCard.vm.$emit('play-video', 'https://youtube.com/watch?v=example123')
      
      // Close modal
      const videoModal = wrapper.findComponent(VideoTestimonialModal)
      await videoModal.vm.$emit('close')
      
      expect(videoModal.props('isOpen')).toBe(false)
    })

    it('sets correct video modal props', async () => {
      const storyCard = wrapper.findComponent(SuccessStoryCard)
      await storyCard.vm.$emit('play-video', 'https://youtube.com/watch?v=example123')
      
      const videoModal = wrapper.findComponent(VideoTestimonialModal)
      expect(videoModal.props('title')).toContain('John Smith\'s Success Story')
      expect(videoModal.props('alumniName')).toBe('John Smith')
      expect(videoModal.props('alumniRole')).toBe('Senior Tech Lead')
      expect(videoModal.props('alumniCompany')).toBe('TechCorp Inc.')
    })
  })

  describe('Social Sharing', () => {
    it('handles LinkedIn sharing', async () => {
      const storyCard = wrapper.findComponent(SuccessStoryCard)
      await storyCard.vm.$emit('share', 'linkedin', mockSuccessStories[0])
      
      expect(wrapper.emitted('share-story')).toBeTruthy()
      expect(wrapper.emitted('share-story')[0]).toEqual(['linkedin', mockSuccessStories[0]])
    })

    it('handles Twitter sharing', async () => {
      const storyCard = wrapper.findComponent(SuccessStoryCard)
      await storyCard.vm.$emit('share', 'twitter', mockSuccessStories[0])
      
      expect(wrapper.emitted('share-story')).toBeTruthy()
      expect(wrapper.emitted('share-story')[0]).toEqual(['twitter', mockSuccessStories[0]])
    })

    it('handles copy link sharing', async () => {
      const storyCard = wrapper.findComponent(SuccessStoryCard)
      await storyCard.vm.$emit('share', 'copy', mockSuccessStories[0])
      
      expect(navigator.clipboard.writeText).toHaveBeenCalled()
    })
  })

  describe('Call to Action', () => {
    it('emits join-now event when Join Now button is clicked', async () => {
      const joinButton = wrapper.find('.cta-button.primary')
      await joinButton.trigger('click')
      
      expect(wrapper.emitted('join-now')).toBeTruthy()
    })

    it('emits learn-more event when Learn More button is clicked', async () => {
      const learnMoreButton = wrapper.find('.cta-button.secondary')
      await learnMoreButton.trigger('click')
      
      expect(wrapper.emitted('learn-more')).toBeTruthy()
    })
  })

  describe('Loading and Error States', () => {
    it('shows loading state', async () => {
      await wrapper.setData({ loading: true })
      
      expect(wrapper.find('.loading-container').exists()).toBe(true)
      expect(wrapper.text()).toContain('Loading success stories...')
      expect(wrapper.find('.loading-spinner').exists()).toBe(true)
    })

    it('shows error state', async () => {
      await wrapper.setData({ 
        loading: false,
        error: 'Failed to load stories'
      })
      
      expect(wrapper.find('.error-container').exists()).toBe(true)
      expect(wrapper.text()).toContain('Unable to load success stories')
      expect(wrapper.text()).toContain('Failed to load stories')
      expect(wrapper.find('.retry-button').exists()).toBe(true)
    })

    it('handles retry functionality', async () => {
      await wrapper.setData({ 
        loading: false,
        error: 'Failed to load stories'
      })
      
      const retryButton = wrapper.find('.retry-button')
      await retryButton.trigger('click')
      
      // Should clear error and show loading
      expect(wrapper.vm.error).toBe('')
      expect(wrapper.vm.loading).toBe(true)
    })
  })

  describe('Empty States', () => {
    it('handles empty stories array', () => {
      wrapper = mount(SuccessStories, {
        props: {
          stories: [],
          filters: mockFilters
        }
      })
      
      expect(wrapper.find('.no-results-container').exists()).toBe(true)
      expect(wrapper.text()).toContain('No success stories found')
    })

    it('shows clear filters option in no results state', async () => {
      const filtersComponent = wrapper.findComponent(SuccessStoryFilters)
      await filtersComponent.vm.$emit('filter-change', { industry: 'nonexistent' })
      
      await wrapper.vm.$nextTick()
      
      const clearFiltersLink = wrapper.find('.clear-filters-link')
      expect(clearFiltersLink.exists()).toBe(true)
      
      await clearFiltersLink.trigger('click')
      
      // Should clear filters and show all stories
      expect(wrapper.findAllComponents(SuccessStoryCard)).toHaveLength(2)
    })
  })

  describe('Accessibility', () => {
    it('has proper section id for navigation', () => {
      expect(wrapper.find('#success-stories').exists()).toBe(true)
    })

    it('has proper pagination ARIA labels', () => {
      // Create wrapper with pagination
      const manyStories = Array.from({ length: 15 }, (_, i) => ({
        ...mockSuccessStories[0],
        id: `story-${i}`,
        title: `Story ${i + 1}`
      }))
      
      wrapper = mount(SuccessStories, {
        props: {
          stories: manyStories,
          filters: mockFilters,
          itemsPerPage: 5
        }
      })
      
      const paginationNav = wrapper.find('.pagination-nav')
      expect(paginationNav.attributes('aria-label')).toBe('Success stories pagination')
      
      const prevButton = wrapper.find('.prev-button')
      expect(prevButton.attributes('aria-label')).toBe('Previous page')
      
      const nextButton = wrapper.find('.next-button')
      expect(nextButton.attributes('aria-label')).toBe('Next page')
    })

    it('has proper heading hierarchy', () => {
      expect(wrapper.find('h2').text()).toBe('Alumni Success Stories')
      expect(wrapper.find('h3').text()).toBe('Featured Success Story')
    })
  })

  describe('Responsive Behavior', () => {
    it('renders mobile-optimized layout', () => {
      // Test that mobile-specific classes are present
      expect(wrapper.find('.stories-grid').classes()).toContain('md:grid-cols-2')
      expect(wrapper.find('.stories-grid').classes()).toContain('lg:grid-cols-3')
    })
  })
})