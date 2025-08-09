import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SuccessStoryFilters from '@/components/homepage/SuccessStoryFilters.vue'
import type { StoryFilter } from '@/types/homepage'

// Mock filter data
const mockFilters: StoryFilter[] = [
  {
    key: 'industry',
    label: 'Industry',
    type: 'select',
    options: [
      { value: 'technology', label: 'Technology', count: 15 },
      { value: 'finance', label: 'Finance', count: 8 },
      { value: 'healthcare', label: 'Healthcare', count: 6 },
      { value: 'education', label: 'Education', count: 4 }
    ]
  },
  {
    key: 'graduationYear',
    label: 'Graduation Year',
    type: 'select',
    options: [
      { value: '2023', label: '2023', count: 5 },
      { value: '2022', label: '2022', count: 8 },
      { value: '2021', label: '2021', count: 12 },
      { value: '2020', label: '2020', count: 8 }
    ]
  },
  {
    key: 'careerStage',
    label: 'Career Stage',
    type: 'select',
    options: [
      { value: 'recent_grad', label: 'Recent Graduate', count: 10 },
      { value: 'mid_career', label: 'Mid-Career', count: 15 },
      { value: 'senior', label: 'Senior Professional', count: 8 },
      { value: 'executive', label: 'Executive', count: 3 }
    ]
  },
  {
    key: 'successType',
    label: 'Success Type',
    type: 'select',
    options: [
      { value: 'salary_increase', label: 'Salary Increase', count: 20 },
      { value: 'promotion', label: 'Promotion', count: 18 },
      { value: 'job_placement', label: 'Job Placement', count: 12 },
      { value: 'business_growth', label: 'Business Growth', count: 6 }
    ]
  }
]

describe('SuccessStoryFilters', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(SuccessStoryFilters, {
      props: {
        filters: mockFilters,
        totalCount: 50,
        filteredCount: 33
      }
    })
  })

  describe('Basic Rendering', () => {
    it('renders the filters component', () => {
      expect(wrapper.find('.success-story-filters').exists()).toBe(true)
    })

    it('displays the filters title', () => {
      expect(wrapper.text()).toContain('Filter Success Stories')
    })

    it('displays results count', () => {
      expect(wrapper.text()).toContain('Showing 33 of 50 success stories')
    })

    it('renders all filter groups on desktop', () => {
      expect(wrapper.find('#industry-filter').exists()).toBe(true)
      expect(wrapper.find('#graduation-year-filter').exists()).toBe(true)
      expect(wrapper.find('#career-stage-filter').exists()).toBe(true)
      expect(wrapper.find('#success-type-filter').exists()).toBe(true)
    })

    it('renders mobile filter toggle', () => {
      expect(wrapper.find('.mobile-toggle-button').exists()).toBe(true)
      expect(wrapper.text()).toContain('Filters')
    })
  })

  describe('Filter Options', () => {
    it('displays industry filter options with counts', () => {
      const industrySelect = wrapper.find('#industry-filter')
      const options = industrySelect.findAll('option')
      
      expect(options).toHaveLength(5) // Including "All Industries" option
      expect(options[0].text()).toBe('All Industries')
      expect(options[1].text()).toBe('Technology (15)')
      expect(options[2].text()).toBe('Finance (8)')
    })

    it('displays graduation year filter options', () => {
      const yearSelect = wrapper.find('#graduation-year-filter')
      const options = yearSelect.findAll('option')
      
      expect(options).toHaveLength(5) // Including "All Years" option
      expect(options[0].text()).toBe('All Years')
      expect(options[1].text()).toBe('2023 (5)')
      expect(options[2].text()).toBe('2022 (8)')
    })

    it('displays career stage filter options', () => {
      const stageSelect = wrapper.find('#career-stage-filter')
      const options = stageSelect.findAll('option')
      
      expect(options).toHaveLength(5) // Including "All Stages" option
      expect(options[0].text()).toBe('All Stages')
      expect(options[1].text()).toBe('Recent Graduate (10)')
      expect(options[2].text()).toBe('Mid-Career (15)')
    })

    it('displays success type filter options', () => {
      const typeSelect = wrapper.find('#success-type-filter')
      const options = typeSelect.findAll('option')
      
      expect(options).toHaveLength(5) // Including "All Types" option
      expect(options[0].text()).toBe('All Types')
      expect(options[1].text()).toBe('Salary Increase (20)')
      expect(options[2].text()).toBe('Promotion (18)')
    })
  })

  describe('Filter Selection', () => {
    it('emits filter-change event when industry is selected', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      await industrySelect.setValue('technology')
      
      expect(wrapper.emitted('filter-change')).toBeTruthy()
      expect(wrapper.emitted('filter-change')[0][0]).toEqual({
        industry: 'technology',
        graduationYear: '',
        careerStage: '',
        successType: ''
      })
    })

    it('emits filter-change event when graduation year is selected', async () => {
      const yearSelect = wrapper.find('#graduation-year-filter')
      await yearSelect.setValue('2022')
      
      expect(wrapper.emitted('filter-change')).toBeTruthy()
      expect(wrapper.emitted('filter-change')[0][0]).toEqual({
        industry: '',
        graduationYear: '2022',
        careerStage: '',
        successType: ''
      })
    })

    it('emits filter-change event when career stage is selected', async () => {
      const stageSelect = wrapper.find('#career-stage-filter')
      await stageSelect.setValue('mid_career')
      
      expect(wrapper.emitted('filter-change')).toBeTruthy()
      expect(wrapper.emitted('filter-change')[0][0]).toEqual({
        industry: '',
        graduationYear: '',
        careerStage: 'mid_career',
        successType: ''
      })
    })

    it('emits filter-change event when success type is selected', async () => {
      const typeSelect = wrapper.find('#success-type-filter')
      await typeSelect.setValue('salary_increase')
      
      expect(wrapper.emitted('filter-change')).toBeTruthy()
      expect(wrapper.emitted('filter-change')[0][0]).toEqual({
        industry: '',
        graduationYear: '',
        careerStage: '',
        successType: 'salary_increase'
      })
    })

    it('combines multiple filter selections', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      const yearSelect = wrapper.find('#graduation-year-filter')
      
      await industrySelect.setValue('technology')
      await yearSelect.setValue('2022')
      
      // Should have emitted twice
      expect(wrapper.emitted('filter-change')).toHaveLength(2)
      
      // Last emission should include both filters
      const lastEmission = wrapper.emitted('filter-change')[1][0]
      expect(lastEmission).toEqual({
        industry: 'technology',
        graduationYear: '2022',
        careerStage: '',
        successType: ''
      })
    })
  })

  describe('Active Filters Display', () => {
    it('does not show active filters initially', () => {
      expect(wrapper.find('.active-filters').exists()).toBe(false)
      expect(wrapper.find('.clear-filters-button').exists()).toBe(false)
    })

    it('shows active filters when filters are applied', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      await industrySelect.setValue('technology')
      
      expect(wrapper.find('.active-filters').exists()).toBe(true)
      expect(wrapper.text()).toContain('Active Filters:')
      expect(wrapper.text()).toContain('Industry: Technology')
    })

    it('shows multiple active filters', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      const yearSelect = wrapper.find('#graduation-year-filter')
      
      await industrySelect.setValue('technology')
      await yearSelect.setValue('2022')
      
      expect(wrapper.text()).toContain('Industry: Technology')
      expect(wrapper.text()).toContain('Year: 2022')
    })

    it('shows clear all button when filters are active', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      await industrySelect.setValue('technology')
      
      expect(wrapper.find('.clear-filters-button').exists()).toBe(true)
    })

    it('allows removing individual filters', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      await industrySelect.setValue('technology')
      
      const removeButton = wrapper.find('.remove-filter-button')
      expect(removeButton.exists()).toBe(true)
      
      await removeButton.trigger('click')
      
      // Should emit filter-change with empty industry
      const emissions = wrapper.emitted('filter-change')
      const lastEmission = emissions[emissions.length - 1][0]
      expect(lastEmission.industry).toBe('')
    })
  })

  describe('Clear All Filters', () => {
    it('clears all filters when clear all is clicked', async () => {
      // Set some filters first
      const industrySelect = wrapper.find('#industry-filter')
      const yearSelect = wrapper.find('#graduation-year-filter')
      
      await industrySelect.setValue('technology')
      await yearSelect.setValue('2022')
      
      // Click clear all
      const clearButton = wrapper.find('.clear-filters-button')
      await clearButton.trigger('click')
      
      // Should emit filter-change with all empty values
      const emissions = wrapper.emitted('filter-change')
      const lastEmission = emissions[emissions.length - 1][0]
      expect(lastEmission).toEqual({
        industry: '',
        graduationYear: '',
        careerStage: '',
        successType: ''
      })
    })

    it('hides active filters after clearing all', async () => {
      // Set a filter first
      const industrySelect = wrapper.find('#industry-filter')
      await industrySelect.setValue('technology')
      
      expect(wrapper.find('.active-filters').exists()).toBe(true)
      
      // Clear all filters
      const clearButton = wrapper.find('.clear-filters-button')
      await clearButton.trigger('click')
      
      // Wait for reactivity
      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.active-filters').exists()).toBe(false)
    })
  })

  describe('Mobile Filters', () => {
    it('shows mobile filters panel when toggle is clicked', async () => {
      expect(wrapper.find('.mobile-filters-panel').isVisible()).toBe(false)
      
      const toggleButton = wrapper.find('.mobile-toggle-button')
      await toggleButton.trigger('click')
      
      expect(wrapper.find('.mobile-filters-panel').isVisible()).toBe(true)
    })

    it('hides mobile filters panel when toggle is clicked again', async () => {
      const toggleButton = wrapper.find('.mobile-toggle-button')
      
      // Open
      await toggleButton.trigger('click')
      expect(wrapper.find('.mobile-filters-panel').isVisible()).toBe(true)
      
      // Close
      await toggleButton.trigger('click')
      expect(wrapper.find('.mobile-filters-panel').isVisible()).toBe(false)
    })

    it('shows filter count badge on mobile toggle', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      await industrySelect.setValue('technology')
      
      expect(wrapper.find('.filter-count').exists()).toBe(true)
      expect(wrapper.find('.filter-count').text()).toBe('1')
    })

    it('updates filter count badge with multiple filters', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      const yearSelect = wrapper.find('#graduation-year-filter')
      
      await industrySelect.setValue('technology')
      await yearSelect.setValue('2022')
      
      expect(wrapper.find('.filter-count').text()).toBe('2')
    })

    it('has mobile-specific filter controls', async () => {
      const toggleButton = wrapper.find('.mobile-toggle-button')
      await toggleButton.trigger('click')
      
      expect(wrapper.find('#mobile-industry-filter').exists()).toBe(true)
      expect(wrapper.find('#mobile-graduation-year-filter').exists()).toBe(true)
      expect(wrapper.find('#mobile-career-stage-filter').exists()).toBe(true)
      expect(wrapper.find('#mobile-success-type-filter').exists()).toBe(true)
    })

    it('has mobile filter action buttons', async () => {
      const toggleButton = wrapper.find('.mobile-toggle-button')
      await toggleButton.trigger('click')
      
      expect(wrapper.find('.mobile-clear-button').exists()).toBe(true)
      expect(wrapper.find('.mobile-apply-button').exists()).toBe(true)
    })

    it('closes mobile panel when apply is clicked', async () => {
      const toggleButton = wrapper.find('.mobile-toggle-button')
      await toggleButton.trigger('click')
      
      const applyButton = wrapper.find('.mobile-apply-button')
      await applyButton.trigger('click')
      
      expect(wrapper.find('.mobile-filters-panel').isVisible()).toBe(false)
    })
  })

  describe('Accessibility', () => {
    it('has proper labels for all filter selects', () => {
      expect(wrapper.find('label[for="industry-filter"]').text()).toBe('Industry')
      expect(wrapper.find('label[for="graduation-year-filter"]').text()).toBe('Graduation Year')
      expect(wrapper.find('label[for="career-stage-filter"]').text()).toBe('Career Stage')
      expect(wrapper.find('label[for="success-type-filter"]').text()).toBe('Success Type')
    })

    it('has proper ARIA attributes for mobile toggle', () => {
      const toggleButton = wrapper.find('.mobile-toggle-button')
      expect(toggleButton.attributes('aria-expanded')).toBe('false')
      expect(toggleButton.attributes('aria-controls')).toBe('mobile-filters')
    })

    it('updates ARIA expanded state when mobile panel is opened', async () => {
      const toggleButton = wrapper.find('.mobile-toggle-button')
      await toggleButton.trigger('click')
      
      expect(toggleButton.attributes('aria-expanded')).toBe('true')
    })

    it('has proper aria-labels for remove filter buttons', async () => {
      const industrySelect = wrapper.find('#industry-filter')
      await industrySelect.setValue('technology')
      
      const removeButton = wrapper.find('.remove-filter-button')
      expect(removeButton.attributes('aria-label')).toBe('Remove industry filter')
    })
  })

  describe('Results Count Updates', () => {
    it('updates results count when props change', async () => {
      expect(wrapper.text()).toContain('Showing 33 of 50 success stories')
      
      await wrapper.setProps({
        filteredCount: 15,
        totalCount: 50
      })
      
      expect(wrapper.text()).toContain('Showing 15 of 50 success stories')
    })

    it('handles zero results', async () => {
      await wrapper.setProps({
        filteredCount: 0,
        totalCount: 50
      })
      
      expect(wrapper.text()).toContain('Showing 0 of 50 success stories')
    })
  })

  describe('Error Handling', () => {
    it('handles empty filters array gracefully', () => {
      wrapper = mount(SuccessStoryFilters, {
        props: {
          filters: [],
          totalCount: 0,
          filteredCount: 0
        }
      })
      
      expect(wrapper.find('.success-story-filters').exists()).toBe(true)
      expect(wrapper.text()).toContain('Showing 0 of 0 success stories')
    })

    it('handles missing filter options gracefully', () => {
      const filtersWithoutOptions = mockFilters.map(filter => ({
        ...filter,
        options: []
      }))
      
      wrapper = mount(SuccessStoryFilters, {
        props: {
          filters: filtersWithoutOptions,
          totalCount: 50,
          filteredCount: 50
        }
      })
      
      const industrySelect = wrapper.find('#industry-filter')
      const options = industrySelect.findAll('option')
      expect(options).toHaveLength(1) // Only "All Industries" option
    })
  })
})