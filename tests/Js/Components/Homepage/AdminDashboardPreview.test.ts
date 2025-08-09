import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AdminDashboardPreview from '../../../../resources/js/components/homepage/AdminDashboardPreview.vue'
import DemoRequestModal from '../../../../resources/js/components/homepage/DemoRequestModal.vue'
import HotspotDetailModal from '../../../../resources/js/components/homepage/HotspotDetailModal.vue'
import FeatureAvailability from '../../../../resources/js/components/homepage/FeatureAvailability.vue'
import LoadingSpinner from '../../../../resources/js/components/ui/LoadingSpinner.vue'

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  ChartBarIcon: { template: '<div data-testid="chart-bar-icon"></div>' },
  UsersIcon: { template: '<div data-testid="users-icon"></div>' },
  CalendarIcon: { template: '<div data-testid="calendar-icon"></div>' },
  CogIcon: { template: '<div data-testid="cog-icon"></div>' },
  CheckIcon: { template: '<div data-testid="check-icon"></div>' },
  DevicePhoneMobileIcon: { template: '<div data-testid="device-phone-mobile-icon"></div>' },
  ChatBubbleLeftRightIcon: { template: '<div data-testid="chat-bubble-left-right-icon"></div>' },
  AcademicCapIcon: { template: '<div data-testid="academic-cap-icon"></div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon"></div>' },
  PlayIcon: { template: '<div data-testid="play-icon"></div>' },
  DocumentTextIcon: { template: '<div data-testid="document-text-icon"></div>' },
  MinusIcon: { template: '<div data-testid="minus-icon"></div>' },
  StarIcon: { template: '<div data-testid="star-icon"></div>' },
  ExclamationTriangleIcon: { template: '<div data-testid="exclamation-triangle-icon"></div>' }
}))

describe('AdminDashboardPreview', () => {
  const defaultProps = {
    institutionName: 'Test University',
    demoData: {
      features: [],
      analytics: {
        totalAlumni: 15420,
        activeUsers: 8934,
        engagementRate: 67,
        eventsThisMonth: 24
      },
      managementTools: [],
      customization: []
    }
  }

  it('renders correctly with default props', () => {
    const wrapper = mount(AdminDashboardPreview, {
      props: defaultProps,
      global: {
        stubs: {
          AnimatedCounter: true,
          DemoRequestModal: true,
          HotspotDetailModal: true,
          FeatureAvailability: true,
          LoadingSpinner: true
        }
      }
    })

    expect(wrapper.find('h2').text()).toBe('Powerful Admin Dashboard')
    expect(wrapper.find('.browser-url span').text()).toContain('Test University.alumni-platform.com/admin')
  })

  it('opens demo request modal when demo button is clicked', async () => {
    const wrapper = mount(AdminDashboardPreview, {
      props: defaultProps,
      global: {
        stubs: {
          AnimatedCounter: true,
          DemoRequestModal: true,
          HotspotDetailModal: true,
          FeatureAvailability: true,
          LoadingSpinner: true
        }
      }
    })

    const demoButton = wrapper.find('.demo-cta-button')
    await demoButton.trigger('click')

    expect(wrapper.vm.showDemoModal).toBe(true)
  })

  it('emits demo-request event when demo is requested', async () => {
    const wrapper = mount(AdminDashboardPreview, {
      props: defaultProps,
      global: {
        stubs: {
          AnimatedCounter: true,
          DemoRequestModal: true,
          HotspotDetailModal: true,
          FeatureAvailability: true,
          LoadingSpinner: true
        }
      }
    })

    const demoRequestData = {
      institutionName: 'Test University',
      contactName: 'John Doe',
      email: 'john@test.edu',
      timestamp: new Date().toISOString()
    }

    await wrapper.vm.handleDemoRequest(demoRequestData)

    expect(wrapper.emitted('demo-request')).toBeTruthy()
    expect(wrapper.emitted('demo-request')[0]).toEqual([demoRequestData])
  })

  it('displays key metrics correctly', () => {
    const wrapper = mount(AdminDashboardPreview, {
      props: defaultProps,
      global: {
        stubs: {
          AnimatedCounter: true,
          DemoRequestModal: true,
          HotspotDetailModal: true,
          FeatureAvailability: true,
          LoadingSpinner: true
        }
      }
    })

    const metricCards = wrapper.findAll('.metric-card')
    expect(metricCards).toHaveLength(4)
    
    // Check that metrics are displayed
    expect(wrapper.text()).toContain('Total Alumni')
    expect(wrapper.text()).toContain('Active Users')
    expect(wrapper.text()).toContain('Engagement Rate')
    expect(wrapper.text()).toContain('Events This Month')
  })

  it('switches between feature tabs correctly', async () => {
    const wrapper = mount(AdminDashboardPreview, {
      props: defaultProps,
      global: {
        stubs: {
          AnimatedCounter: true,
          DemoRequestModal: true,
          HotspotDetailModal: true,
          FeatureAvailability: true,
          LoadingSpinner: true
        }
      }
    })

    // Initially should be on analytics tab
    expect(wrapper.vm.activeTab).toBe('analytics')

    // Find and click management tab
    const managementTab = wrapper.find('[data-testid="management-tab"]')
    if (managementTab.exists()) {
      await managementTab.trigger('click')
      expect(wrapper.vm.activeTab).toBe('management')
    }
  })

  it('shows hotspot detail when hotspot is clicked', async () => {
    const wrapper = mount(AdminDashboardPreview, {
      props: defaultProps,
      global: {
        stubs: {
          AnimatedCounter: true,
          DemoRequestModal: true,
          HotspotDetailModal: true,
          FeatureAvailability: true,
          LoadingSpinner: true
        }
      }
    })

    const mockHotspot = {
      id: 'test-hotspot',
      x: 50,
      y: 50,
      description: 'Test hotspot'
    }

    await wrapper.vm.showHotspotDetail(mockHotspot)

    expect(wrapper.vm.selectedHotspot).toEqual(mockHotspot)
    expect(wrapper.emitted('hotspot-click')).toBeTruthy()
    expect(wrapper.emitted('hotspot-click')[0]).toEqual(['test-hotspot'])
  })

  it('displays feature comparison table', () => {
    const wrapper = mount(AdminDashboardPreview, {
      props: defaultProps,
      global: {
        stubs: {
          AnimatedCounter: true,
          DemoRequestModal: true,
          HotspotDetailModal: true,
          FeatureAvailability: true,
          LoadingSpinner: true
        }
      }
    })

    expect(wrapper.find('.comparison-section').exists()).toBe(true)
    expect(wrapper.text()).toContain('Individual vs Institutional Features')
    expect(wrapper.text()).toContain('Individual Alumni')
    expect(wrapper.text()).toContain('Institutional Admin')
  })
})

describe('DemoRequestModal', () => {
  it('renders form fields correctly', () => {
    const wrapper = mount(DemoRequestModal, {
      props: {
        institutionName: 'Test University'
      },
      global: {
        stubs: {
          LoadingSpinner: true
        }
      }
    })

    expect(wrapper.find('#institutionName').exists()).toBe(true)
    expect(wrapper.find('#contactName').exists()).toBe(true)
    expect(wrapper.find('#email').exists()).toBe(true)
    expect(wrapper.find('#title').exists()).toBe(true)
    expect(wrapper.find('#phone').exists()).toBe(true)
  })

  it('validates required fields', async () => {
    const wrapper = mount(DemoRequestModal, {
      props: {
        institutionName: 'Test University'
      },
      global: {
        stubs: {
          LoadingSpinner: true
        }
      }
    })

    // Try to submit without filling required fields
    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.vm.errors.institutionName).toBeTruthy()
    expect(wrapper.vm.errors.contactName).toBeTruthy()
    expect(wrapper.vm.errors.email).toBeTruthy()
  })

  it('emits submit event with form data when valid', async () => {
    const wrapper = mount(DemoRequestModal, {
      props: {
        institutionName: 'Test University'
      },
      global: {
        stubs: {
          LoadingSpinner: true
        }
      }
    })

    // Fill in required fields
    await wrapper.find('#institutionName').setValue('Test University')
    await wrapper.find('#contactName').setValue('John Doe')
    await wrapper.find('#email').setValue('john@test.edu')

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')).toBeTruthy()
    const emittedData = wrapper.emitted('submit')[0][0]
    expect(emittedData.institutionName).toBe('Test University')
    expect(emittedData.contactName).toBe('John Doe')
    expect(emittedData.email).toBe('john@test.edu')
  })
})

describe('FeatureAvailability', () => {
  it('renders full access correctly', () => {
    const wrapper = mount(FeatureAvailability, {
      props: {
        level: 'full'
      }
    })

    expect(wrapper.find('.full-access').exists()).toBe(true)
    expect(wrapper.text()).toContain('Full Access')
  })

  it('renders enhanced access correctly', () => {
    const wrapper = mount(FeatureAvailability, {
      props: {
        level: 'enhanced'
      }
    })

    expect(wrapper.find('.enhanced-access').exists()).toBe(true)
    expect(wrapper.text()).toContain('Enhanced')
  })

  it('renders no access correctly', () => {
    const wrapper = mount(FeatureAvailability, {
      props: {
        level: 'none'
      }
    })

    expect(wrapper.find('.no-access').exists()).toBe(true)
    expect(wrapper.text()).toContain('Not Available')
  })

  it('shows tooltip when showTooltip is true', () => {
    const wrapper = mount(FeatureAvailability, {
      props: {
        level: 'full',
        showTooltip: true
      }
    })

    expect(wrapper.find('.availability-tooltip').exists()).toBe(true)
  })
})

describe('LoadingSpinner', () => {
  it('renders with default props', () => {
    const wrapper = mount(LoadingSpinner)

    expect(wrapper.find('.loading-spinner').exists()).toBe(true)
    expect(wrapper.find('.spinner-circle').exists()).toBe(true)
  })

  it('applies correct size class', () => {
    const wrapper = mount(LoadingSpinner, {
      props: {
        size: 'lg'
      }
    })

    expect(wrapper.find('.w-8').exists()).toBe(true)
  })

  it('applies correct color class', () => {
    const wrapper = mount(LoadingSpinner, {
      props: {
        color: 'white'
      }
    })

    expect(wrapper.find('.border-white').exists()).toBe(true)
  })

  it('sets correct aria-label', () => {
    const wrapper = mount(LoadingSpinner, {
      props: {
        ariaLabel: 'Custom loading message'
      }
    })

    expect(wrapper.attributes('aria-label')).toBe('Custom loading message')
  })
})