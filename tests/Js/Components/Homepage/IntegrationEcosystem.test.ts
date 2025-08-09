import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import IntegrationEcosystem from '@/components/homepage/IntegrationEcosystem.vue'
import type { IntegrationEcosystemProps } from '@/types/homepage'

describe('IntegrationEcosystem', () => {
  let wrapper: any
  let mockProps: IntegrationEcosystemProps

  beforeEach(() => {
    mockProps = {
      audience: 'individual',
      integrations: [
        {
          id: '1',
          name: 'Salesforce',
          category: 'crm',
          logo: '/salesforce.png',
          description: 'CRM integration',
          features: ['Data sync', 'Lead scoring', 'Event tracking'],
          setupComplexity: 'medium',
          documentation: '/docs/salesforce',
          supportLevel: 'premium',
          pricing: {
            type: 'paid',
            cost: 99,
            billingPeriod: 'monthly'
          },
          screenshots: ['/sf1.png', '/sf2.png']
        },
        {
          id: '2',
          name: 'Mailchimp',
          category: 'email',
          logo: '/mailchimp.png',
          description: 'Email marketing integration',
          features: ['List sync', 'Campaigns', 'Analytics'],
          setupComplexity: 'easy',
          documentation: '/docs/mailchimp',
          supportLevel: 'standard',
          pricing: {
            type: 'free'
          }
        }
      ],
      apiDocumentation: {
        title: 'Developer API',
        description: 'REST API for integrations',
        version: 'v2.1',
        baseUrl: 'https://api.test.com',
        authentication: [
          {
            type: 'api_key',
            description: 'API key authentication',
            implementation: 'Header-based',
            security: ['Rate limiting']
          }
        ],
        endpoints: [
          {
            id: '1',
            method: 'GET',
            path: '/alumni',
            description: 'Get alumni list',
            parameters: [
              { name: 'page', type: 'integer', required: false, description: 'Page number' }
            ],
            responses: [
              { status: 200, description: 'Success', schema: 'AlumniList', example: '{}' }
            ],
            examples: ['curl example']
          }
        ],
        sdks: [
          {
            language: 'JavaScript',
            name: 'js-sdk',
            version: '2.1.0',
            documentation: '/docs/js',
            repository: 'https://github.com/test/js-sdk',
            examples: ['npm install']
          }
        ],
        examples: [
          {
            id: '1',
            title: 'Get Alumni',
            description: 'Retrieve alumni',
            language: 'javascript',
            code: 'const alumni = await client.alumni.list();',
            explanation: ['Initialize client']
          }
        ],
        rateLimits: [
          {
            endpoint: '/alumni',
            limit: 1000,
            period: 'hour',
            headers: ['X-RateLimit-Limit']
          }
        ]
      },
      migrationSupport: {
        title: 'Migration Support',
        description: 'Platform migration assistance',
        supportedPlatforms: [
          {
            id: '1',
            name: 'Legacy System',
            logo: '/legacy.png',
            description: 'Legacy database migration',
            migrationComplexity: 'medium',
            dataMapping: [
              { sourceField: 'name', targetField: 'full_name' }
            ],
            estimatedTime: '2-4 weeks'
          }
        ],
        migrationProcess: [
          {
            id: '1',
            stepNumber: 1,
            title: 'Assessment',
            description: 'Data analysis',
            duration: '1 week',
            deliverables: ['Report'],
            prerequisites: ['Data access']
          }
        ],
        timeline: '4-8 weeks',
        support: {
          type: 'full_service',
          description: 'Complete migration support',
          included: ['Data migration', 'Training'],
          timeline: '4-8 weeks'
        },
        tools: [
          {
            id: '1',
            name: 'Migration Tool',
            description: 'Automated migration',
            type: 'automated',
            supportedFormats: ['CSV', 'Excel'],
            limitations: ['10k records max']
          }
        ]
      },
      trainingPrograms: [
        {
          id: '1',
          title: 'Admin Training',
          description: 'Administrator training program',
          audience: 'administrators',
          format: 'online',
          duration: '2 days',
          modules: [
            {
              id: '1',
              title: 'Platform Overview',
              description: 'Introduction to platform',
              duration: '2 hours',
              topics: ['Navigation', 'Configuration'],
              materials: ['Videos'],
              assessment: true
            }
          ],
          certification: true,
          cost: {
            type: 'included'
          },
          schedule: [
            {
              id: '1',
              date: new Date('2024-03-15'),
              time: '9:00 AM',
              timezone: 'EST',
              capacity: 20,
              registrationUrl: '/register'
            }
          ]
        }
      ],
      scalabilityInfo: [
        {
          id: '1',
          institutionSize: 'small',
          alumniRange: '1,000-5,000',
          features: [
            { name: 'Basic networking', availability: true },
            { name: 'Advanced analytics', availability: false }
          ],
          performance: [
            { metric: 'Response time', value: '<200ms', description: 'API response', benchmark: 'Standard' }
          ],
          support: {
            type: 'Standard',
            description: 'Email support',
            responseTime: '24 hours',
            channels: ['Email'],
            dedicatedManager: false
          },
          pricing: {
            model: 'tiered',
            basePrice: 299,
            volumeDiscounts: [
              { minUsers: 1000, discountPercentage: 10, description: '10% off' }
            ]
          },
          caseStudies: ['Small College']
        }
      ]
    }
  })

  describe('Component Rendering', () => {
    it('renders the component with correct structure', () => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
      
      expect(wrapper.find('.integration-ecosystem').exists()).toBe(true)
      expect(wrapper.find('h2').text()).toContain('Seamless Integrations')
    })

    it('renders institutional audience content correctly', () => {
      mockProps.audience = 'institutional'
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
      
      expect(wrapper.find('h2').text()).toContain('Enterprise Integration Ecosystem')
    })

    it('displays platform integrations', () => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
      
      expect(wrapper.text()).toContain('Platform Integrations')
      expect(wrapper.text()).toContain('Salesforce')
      expect(wrapper.text()).toContain('Mailchimp')
    })

    it('shows integration categories filter', () => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
      
      const categoryButtons = wrapper.findAll('button')
      const categoryTexts = categoryButtons.map(btn => btn.text())
      
      expect(categoryTexts).toContain('All')
      expect(categoryTexts).toContain('CRM')
      expect(categoryTexts).toContain('Email')
    })

    it('displays API documentation section', () => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
      
      expect(wrapper.text()).toContain('Developer API')
      expect(wrapper.text()).toContain('REST API')
      expect(wrapper.text()).toContain('v2.1')
    })

    it('shows migration support information', () => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
      
      expect(wrapper.text()).toContain('Migration Support')
      expect(wrapper.text()).toContain('Legacy System')
      expect(wrapper.text()).toContain('2-4 weeks')
    })

    it('displays training programs', () => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
      
      expect(wrapper.text()).toContain('Training & Support')
      expect(wrapper.text()).toContain('Admin Training')
      expect(wrapper.text()).toContain('2 days')
    })

    it('shows scalability information', () => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
      
      expect(wrapper.text()).toContain('Scalability for Every Institution Size')
      expect(wrapper.text()).toContain('Small')
      expect(wrapper.text()).toContain('1,000-5,000')
    })
  })

  describe('Integration Filtering', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('shows all integrations by default', () => {
      expect(wrapper.text()).toContain('Salesforce')
      expect(wrapper.text()).toContain('Mailchimp')
    })

    it('filters integrations by category', async () => {
      const crmButton = wrapper.findAll('button').find(btn => btn.text() === 'CRM')
      await crmButton?.trigger('click')
      
      expect(wrapper.text()).toContain('Salesforce')
      expect(wrapper.text()).not.toContain('Mailchimp')
    })

    it('updates active category styling', async () => {
      const crmButton = wrapper.findAll('button').find(btn => btn.text() === 'CRM')
      await crmButton?.trigger('click')
      
      expect(crmButton?.classes()).toContain('bg-blue-600')
      expect(crmButton?.classes()).toContain('text-white')
    })
  })

  describe('Interactive Features', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('opens integration detail modal', async () => {
      const viewDetailsButton = wrapper.find('button:contains("View Details")')
      await viewDetailsButton.trigger('click')
      
      expect(wrapper.vm.selectedIntegration).toBeTruthy()
      expect(wrapper.find('.fixed.inset-0.bg-black.bg-opacity-50').exists()).toBe(true)
    })

    it('closes integration modal', async () => {
      // Open modal first
      const viewDetailsButton = wrapper.find('button:contains("View Details")')
      await viewDetailsButton.trigger('click')
      
      // Close modal
      const closeButton = wrapper.find('.fa-times').element.parentElement
      await closeButton?.click()
      
      expect(wrapper.vm.selectedIntegration).toBeNull()
    })

    it('opens API documentation modal', async () => {
      const apiDocsButton = wrapper.find('button:contains("View API Documentation")')
      await apiDocsButton.trigger('click')
      
      expect(wrapper.vm.showApiDocsModal).toBe(true)
    })

    it('opens training program modal', async () => {
      const trainingButton = wrapper.find('button:contains("View Program Details")')
      await trainingButton.trigger('click')
      
      expect(wrapper.vm.selectedTrainingProgram).toBeTruthy()
    })
  })

  describe('Integration Display', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('displays integration pricing correctly', () => {
      expect(wrapper.text()).toContain('$99/monthly')
      expect(wrapper.text()).toContain('Free')
    })

    it('shows setup complexity badges', () => {
      expect(wrapper.text()).toContain('medium')
      expect(wrapper.text()).toContain('easy')
    })

    it('displays integration features', () => {
      expect(wrapper.text()).toContain('Data sync')
      expect(wrapper.text()).toContain('Lead scoring')
      expect(wrapper.text()).toContain('List sync')
    })

    it('shows documentation links', () => {
      const docLinks = wrapper.findAll('a[href*="/docs/"]')
      expect(docLinks.length).toBeGreaterThan(0)
    })
  })

  describe('API Documentation Display', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('displays API overview information', () => {
      expect(wrapper.text()).toContain('REST API')
      expect(wrapper.text()).toContain('v2.1')
      expect(wrapper.text()).toContain('Secure Auth')
      expect(wrapper.text()).toContain('1 methods')
    })

    it('shows SDK information', () => {
      expect(wrapper.text()).toContain('SDKs')
      expect(wrapper.text()).toContain('1 languages')
    })

    it('displays code examples count', () => {
      expect(wrapper.text()).toContain('Examples')
      expect(wrapper.text()).toContain('1 code samples')
    })
  })

  describe('Migration Support Display', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('shows supported platforms', () => {
      expect(wrapper.text()).toContain('Supported Platforms')
      expect(wrapper.text()).toContain('Legacy System')
    })

    it('displays migration complexity', () => {
      expect(wrapper.text()).toContain('medium')
      expect(wrapper.text()).toContain('2-4 weeks')
    })

    it('shows migration process steps', () => {
      expect(wrapper.text()).toContain('Migration Process')
      expect(wrapper.text()).toContain('Assessment')
      expect(wrapper.text()).toContain('1 week')
    })

    it('displays support information', () => {
      expect(wrapper.text()).toContain('full_service')
      expect(wrapper.text()).toContain('4-8 weeks')
    })
  })

  describe('Training Programs Display', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('shows training program details', () => {
      expect(wrapper.text()).toContain('Admin Training')
      expect(wrapper.text()).toContain('administrators')
      expect(wrapper.text()).toContain('2 days')
      expect(wrapper.text()).toContain('online')
    })

    it('displays certification status', () => {
      expect(wrapper.text()).toContain('Certificate included')
    })

    it('shows training modules count', () => {
      expect(wrapper.text()).toContain('Modules (1)')
      expect(wrapper.text()).toContain('Platform Overview')
    })

    it('displays cost information', () => {
      expect(wrapper.text()).toContain('included')
    })
  })

  describe('Scalability Information Display', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('shows institution size categories', () => {
      expect(wrapper.text()).toContain('Small')
      expect(wrapper.text()).toContain('1,000-5,000 alumni')
    })

    it('displays feature availability', () => {
      expect(wrapper.text()).toContain('Basic networking')
      expect(wrapper.text()).toContain('Advanced analytics')
    })

    it('shows performance metrics', () => {
      expect(wrapper.text()).toContain('Response time')
      expect(wrapper.text()).toContain('<200ms')
    })

    it('displays pricing information', () => {
      expect(wrapper.text()).toContain('$299')
      expect(wrapper.text()).toContain('starting price')
    })
  })

  describe('Modal Functionality', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('displays integration details in modal', async () => {
      const viewDetailsButton = wrapper.find('button:contains("View Details")')
      await viewDetailsButton.trigger('click')
      
      expect(wrapper.text()).toContain('Salesforce')
      expect(wrapper.text()).toContain('CRM integration')
      expect(wrapper.text()).toContain('Data sync')
    })

    it('shows API documentation in modal', async () => {
      const apiDocsButton = wrapper.find('button:contains("View API Documentation")')
      await apiDocsButton.trigger('click')
      
      expect(wrapper.text()).toContain('API Documentation')
      expect(wrapper.text()).toContain('Authentication Methods')
      expect(wrapper.text()).toContain('Available SDKs')
    })

    it('displays training program details in modal', async () => {
      const trainingButton = wrapper.find('button:contains("View Program Details")')
      await trainingButton.trigger('click')
      
      expect(wrapper.text()).toContain('Admin Training')
      expect(wrapper.text()).toContain('Program Details')
      expect(wrapper.text()).toContain('Training Modules')
    })
  })

  describe('Accessibility', () => {
    beforeEach(() => {
      wrapper = mount(IntegrationEcosystem, { props: mockProps })
    })

    it('has proper heading hierarchy', () => {
      const h2 = wrapper.find('h2')
      const h3s = wrapper.findAll('h3')
      const h4s = wrapper.findAll('h4')
      
      expect(h2.exists()).toBe(true)
      expect(h3s.length).toBeGreaterThan(0)
      expect(h4s.length).toBeGreaterThan(0)
    })

    it('has alt text for integration logos', () => {
      const integrationImages = wrapper.findAll('img[alt]')
      expect(integrationImages.length).toBeGreaterThan(0)
      
      integrationImages.forEach(img => {
        expect(img.attributes('alt')).toBeTruthy()
      })
    })

    it('has keyboard accessible buttons', () => {
      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.text().length > 0 || button.attributes('aria-label')).toBeTruthy()
      })
    })
  })

  describe('Error Handling', () => {
    it('handles empty integration arrays', () => {
      const emptyProps = {
        ...mockProps,
        integrations: [],
        trainingPrograms: [],
        scalabilityInfo: []
      }
      
      expect(() => {
        wrapper = mount(IntegrationEcosystem, { props: emptyProps })
      }).not.toThrow()
    })

    it('handles missing optional properties', () => {
      const minimalProps = {
        audience: 'individual' as const,
        integrations: [{
          id: '1',
          name: 'Test',
          category: 'crm' as const,
          logo: '/test.png',
          description: 'Test',
          features: [],
          setupComplexity: 'easy' as const,
          documentation: '/docs',
          supportLevel: 'standard' as const,
          pricing: { type: 'free' as const }
        }],
        apiDocumentation: {
          title: 'API',
          description: 'Test API',
          version: '1.0',
          baseUrl: 'https://api.test.com',
          authentication: [],
          endpoints: [],
          sdks: [],
          examples: [],
          rateLimits: []
        },
        migrationSupport: {
          title: 'Migration',
          description: 'Test migration',
          supportedPlatforms: [],
          migrationProcess: [],
          timeline: '1 week',
          support: {
            type: 'self_service' as const,
            description: 'Self service',
            included: [],
            timeline: '1 week'
          },
          tools: []
        },
        trainingPrograms: [],
        scalabilityInfo: []
      }
      
      expect(() => {
        wrapper = mount(IntegrationEcosystem, { props: minimalProps })
      }).not.toThrow()
    })
  })

  describe('Performance', () => {
    it('renders efficiently with large datasets', () => {
      const largeProps = {
        ...mockProps,
        integrations: Array.from({ length: 50 }, (_, i) => ({
          id: `integration-${i}`,
          name: `Integration ${i}`,
          category: 'crm' as const,
          logo: `/logo-${i}.png`,
          description: `Description ${i}`,
          features: [`Feature ${i}`],
          setupComplexity: 'easy' as const,
          documentation: `/docs/${i}`,
          supportLevel: 'standard' as const,
          pricing: { type: 'free' as const }
        }))
      }
      
      const startTime = performance.now()
      wrapper = mount(IntegrationEcosystem, { props: largeProps })
      const endTime = performance.now()
      
      expect(endTime - startTime).toBeLessThan(200) // Should render in under 200ms
      expect(wrapper.exists()).toBe(true)
    })
  })
})