import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ValueCalculator from '@/components/homepage/ValueCalculator.vue'

// Mock the step components
vi.mock('@/components/homepage/calculator/CalculatorStep1.vue', () => ({
  default: {
    name: 'CalculatorStep1',
    template: '<div data-testid="step-1">Step 1</div>',
    emits: ['update', 'next'],
    props: ['formData', 'errors', 'isLoading']
  }
}))

vi.mock('@/components/homepage/calculator/CalculatorStep2.vue', () => ({
  default: {
    name: 'CalculatorStep2',
    template: '<div data-testid="step-2">Step 2</div>',
    emits: ['update', 'next', 'previous'],
    props: ['formData', 'errors', 'isLoading']
  }
}))

vi.mock('@/components/homepage/calculator/CalculatorStep3.vue', () => ({
  default: {
    name: 'CalculatorStep3',
    template: '<div data-testid="step-3">Step 3</div>',
    emits: ['update', 'next', 'previous'],
    props: ['formData', 'errors', 'isLoading']
  }
}))

vi.mock('@/components/homepage/calculator/CalculatorStep4.vue', () => ({
  default: {
    name: 'CalculatorStep4',
    template: '<div data-testid="step-4">Step 4</div>',
    emits: ['update', 'submit', 'previous'],
    props: ['formData', 'errors', 'isLoading']
  }
}))

vi.mock('@/components/homepage/calculator/CalculatorResults.vue', () => ({
  default: {
    name: 'CalculatorResults',
    template: '<div data-testid="results">Results</div>',
    emits: ['restart', 'email-report'],
    props: ['result', 'formData']
  }
}))

// Mock fetch
global.fetch = vi.fn()

describe('ValueCalculator', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Mock CSRF token
    document.head.innerHTML = '<meta name="csrf-token" content="test-token">'
  })

  it('renders the calculator trigger button', () => {
    const wrapper = mount(ValueCalculator)
    
    expect(wrapper.find('button').text()).toContain('Start Your Career Assessment')
  })

  it('displays preview statistics', () => {
    const wrapper = mount(ValueCalculator)
    
    expect(wrapper.text()).toContain('40%')
    expect(wrapper.text()).toContain('Average Salary Increase')
    expect(wrapper.text()).toContain('6 months')
    expect(wrapper.text()).toContain('Average Time to Promotion')
    expect(wrapper.text()).toContain('85%')
    expect(wrapper.text()).toContain('Job Placement Success Rate')
  })

  it('opens calculator modal when button is clicked', async () => {
    const wrapper = mount(ValueCalculator)
    
    expect(wrapper.find('[data-testid="step-1"]').exists()).toBe(false)
    
    await wrapper.find('button').trigger('click')
    
    expect(wrapper.find('.fixed').exists()).toBe(true)
    expect(wrapper.find('[data-testid="step-1"]').exists()).toBe(true)
  })

  it('closes calculator modal when close button is clicked', async () => {
    const wrapper = mount(ValueCalculator)
    
    // Open modal
    await wrapper.find('button').trigger('click')
    expect(wrapper.find('.fixed').exists()).toBe(true)
    
    // Close modal
    await wrapper.find('svg').trigger('click')
    expect(wrapper.find('.fixed').exists()).toBe(false)
  })

  it('displays correct progress bar percentage', async () => {
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    
    // Step 1 of 4 = 25%
    const progressBar = wrapper.find('.bg-white.rounded-full.h-2')
    expect(progressBar.attributes('style')).toContain('width: 25%')
  })

  it('updates form data when step component emits update', async () => {
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    
    const step1 = wrapper.findComponent({ name: 'CalculatorStep1' })
    await step1.vm.$emit('update', { currentRole: 'software_engineer' })
    
    expect(step1.props('formData').currentRole).toBe('software_engineer')
  })

  it('advances to next step when step component emits next', async () => {
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    expect(wrapper.find('[data-testid="step-1"]').exists()).toBe(true)
    
    const step1 = wrapper.findComponent({ name: 'CalculatorStep1' })
    await step1.vm.$emit('next')
    
    expect(wrapper.find('[data-testid="step-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="step-1"]').exists()).toBe(false)
  })

  it('goes back to previous step when step component emits previous', async () => {
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    
    // Go to step 2
    const step1 = wrapper.findComponent({ name: 'CalculatorStep1' })
    await step1.vm.$emit('next')
    
    expect(wrapper.find('[data-testid="step-2"]').exists()).toBe(true)
    
    // Go back to step 1
    const step2 = wrapper.findComponent({ name: 'CalculatorStep2' })
    await step2.vm.$emit('previous')
    
    expect(wrapper.find('[data-testid="step-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="step-2"]').exists()).toBe(false)
  })

  it('validates step 1 form data correctly', async () => {
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    
    // Try to advance without required fields
    const step1 = wrapper.findComponent({ name: 'CalculatorStep1' })
    await step1.vm.$emit('next')
    
    // Should still be on step 1 due to validation
    expect(wrapper.find('[data-testid="step-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="step-2"]').exists()).toBe(false)
  })

  it('validates step 2 form data correctly', async () => {
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    
    // Fill step 1 data
    const step1 = wrapper.findComponent({ name: 'CalculatorStep1' })
    await step1.vm.$emit('update', {
      currentRole: 'software_engineer',
      industry: 'technology',
      experienceYears: 5
    })
    await step1.vm.$emit('next')
    
    // Try to advance step 2 without career goals
    const step2 = wrapper.findComponent({ name: 'CalculatorStep2' })
    await step2.vm.$emit('next')
    
    // Should still be on step 2 due to validation
    expect(wrapper.find('[data-testid="step-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="step-3"]').exists()).toBe(false)
  })

  it('submits calculator and shows results', async () => {
    const mockResponse = {
      ok: true,
      json: () => Promise.resolve({
        data: {
          projectedSalaryIncrease: 25000,
          networkingValue: 'High networking potential',
          careerAdvancementTimeline: '12-18 months',
          personalizedRecommendations: [],
          successProbability: 85,
          roiEstimate: 5.2
        }
      })
    }
    
    ;(global.fetch as any).mockResolvedValueOnce(mockResponse)
    
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    
    // Navigate to step 4 and submit
    const step1 = wrapper.findComponent({ name: 'CalculatorStep1' })
    await step1.vm.$emit('update', {
      currentRole: 'software_engineer',
      industry: 'technology',
      experienceYears: 5
    })
    await step1.vm.$emit('next')
    
    const step2 = wrapper.findComponent({ name: 'CalculatorStep2' })
    await step2.vm.$emit('update', { careerGoals: ['salary_increase'] })
    await step2.vm.$emit('next')
    
    const step3 = wrapper.findComponent({ name: 'CalculatorStep3' })
    await step3.vm.$emit('update', {
      location: 'San Francisco, CA',
      educationLevel: 'bachelor'
    })
    await step3.vm.$emit('next')
    
    const step4 = wrapper.findComponent({ name: 'CalculatorStep4' })
    await step4.vm.$emit('submit')
    
    // Wait for async operation
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))
    
    expect(global.fetch).toHaveBeenCalledWith('/api/calculator/calculate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': 'test-token'
      },
      body: JSON.stringify({
        currentRole: 'software_engineer',
        industry: 'technology',
        experienceYears: 5,
        careerGoals: ['salary_increase'],
        location: 'San Francisco, CA',
        educationLevel: 'bachelor'
      })
    })
    
    expect(wrapper.find('[data-testid="results"]').exists()).toBe(true)
  })

  it('handles calculator submission error', async () => {
    const mockResponse = {
      ok: false,
      status: 500
    }
    
    ;(global.fetch as any).mockResolvedValueOnce(mockResponse)
    
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    
    // Fill required data and submit
    const step1 = wrapper.findComponent({ name: 'CalculatorStep1' })
    await step1.vm.$emit('update', {
      currentRole: 'software_engineer',
      industry: 'technology',
      experienceYears: 5
    })
    await step1.vm.$emit('next')
    
    const step2 = wrapper.findComponent({ name: 'CalculatorStep2' })
    await step2.vm.$emit('update', { careerGoals: ['salary_increase'] })
    await step2.vm.$emit('next')
    
    const step3 = wrapper.findComponent({ name: 'CalculatorStep3' })
    await step3.vm.$emit('update', {
      location: 'San Francisco, CA',
      educationLevel: 'bachelor'
    })
    await step3.vm.$emit('next')
    
    const step4 = wrapper.findComponent({ name: 'CalculatorStep4' })
    await step4.vm.$emit('submit')
    
    // Wait for async operation
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 0))
    
    // Should show error and stay on step 4
    expect(wrapper.find('[data-testid="step-4"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="results"]').exists()).toBe(false)
  })

  it('restarts calculator when results component emits restart', async () => {
    const wrapper = mount(ValueCalculator)
    
    // Set up calculator with results
    await wrapper.find('button').trigger('click')
    wrapper.vm.calculationResult = {
      projectedSalaryIncrease: 25000,
      networkingValue: 'High',
      careerAdvancementTimeline: '12 months',
      personalizedRecommendations: [],
      successProbability: 85,
      roiEstimate: 5.2
    }
    wrapper.vm.currentStep = 5 // Results step
    
    await wrapper.vm.$nextTick()
    
    const results = wrapper.findComponent({ name: 'CalculatorResults' })
    await results.vm.$emit('restart')
    
    // Should reset to step 1
    expect(wrapper.vm.currentStep).toBe(1)
    expect(wrapper.vm.calculationResult).toBeNull()
    expect(wrapper.find('[data-testid="step-1"]').exists()).toBe(true)
  })

  it('handles email report request', async () => {
    const mockResponse = { ok: true }
    ;(global.fetch as any).mockResolvedValueOnce(mockResponse)
    
    const wrapper = mount(ValueCalculator)
    
    // Set up calculator with results
    wrapper.vm.calculationResult = {
      projectedSalaryIncrease: 25000,
      networkingValue: 'High',
      careerAdvancementTimeline: '12 months',
      personalizedRecommendations: [],
      successProbability: 85,
      roiEstimate: 5.2
    }
    wrapper.vm.currentStep = 5
    
    await wrapper.vm.$nextTick()
    
    const results = wrapper.findComponent({ name: 'CalculatorResults' })
    await results.vm.$emit('email-report', 'test@example.com')
    
    expect(global.fetch).toHaveBeenCalledWith('/api/calculator/email-report', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': 'test-token'
      },
      body: JSON.stringify({
        email: 'test@example.com',
        formData: expect.any(Object),
        result: expect.any(Object)
      })
    })
  })

  it('clears errors when form data is updated', async () => {
    const wrapper = mount(ValueCalculator)
    
    await wrapper.find('button').trigger('click')
    
    // Set an error
    wrapper.vm.errors = { currentRole: 'Current role is required' }
    
    // Update form data
    const step1 = wrapper.findComponent({ name: 'CalculatorStep1' })
    await step1.vm.$emit('update', { currentRole: 'software_engineer' })
    
    // Error should be cleared
    expect(wrapper.vm.errors.currentRole).toBeUndefined()
  })

  it('tracks analytics events', async () => {
    // Mock gtag
    ;(window as any).gtag = vi.fn()
    
    const wrapper = mount(ValueCalculator)
    
    // Open calculator
    await wrapper.find('button').trigger('click')
    
    expect((window as any).gtag).toHaveBeenCalledWith('event', 'calculator_opened', {
      custom_parameter: { source: 'homepage_cta' }
    })
    
    // Close calculator
    await wrapper.find('svg').trigger('click')
    
    expect((window as any).gtag).toHaveBeenCalledWith('event', 'calculator_closed', {
      custom_parameter: { step: 1, completed: false }
    })
  })
})