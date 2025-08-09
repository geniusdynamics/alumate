import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import SecurityPrivacy from '@/components/homepage/SecurityPrivacy.vue'
import type { SecurityPrivacyProps } from '@/types/homepage'

describe('SecurityPrivacy', () => {
  let wrapper: any
  let mockProps: SecurityPrivacyProps

  beforeEach(() => {
    mockProps = {
      audience: 'individual',
      privacyHighlights: [
        {
          id: '1',
          title: 'Data Minimization',
          description: 'We only collect necessary data',
          icon: 'fas fa-shield-alt',
          details: ['No unnecessary data', 'Optional fields'],
          learnMoreUrl: '/privacy'
        },
        {
          id: '2',
          title: 'User Control',
          description: 'Complete control over your data',
          icon: 'fas fa-user-cog',
          details: ['Privacy settings', 'Data export']
        }
      ],
      securityCertifications: [
        {
          id: '1',
          name: 'SOC 2 Type II',
          badge: '/images/soc2.png',
          description: 'Security controls audit',
          verificationUrl: 'https://verify.com',
          category: 'security'
        },
        {
          id: '2',
          name: 'ISO 27001',
          badge: '/images/iso.png',
          description: 'Information security standard',
          category: 'security'
        }
      ],
      verificationProcess: {
        title: 'Alumni Verification',
        description: 'Multi-step verification process',
        steps: [
          {
            id: '1',
            stepNumber: 1,
            title: 'Institution Check',
            description: 'Verify educational background',
            icon: 'fas fa-university',
            estimatedTime: '2-3 days',
            required: true
          },
          {
            id: '2',
            stepNumber: 2,
            title: 'Identity Confirmation',
            description: 'Confirm your identity',
            icon: 'fas fa-id-card',
            estimatedTime: '1-2 days',
            required: true
          }
        ],
        benefits: ['Verified network access', 'Enhanced credibility'],
        requirements: ['Valid credentials', 'Photo ID']
      },
      dataProtection: {
        title: 'Data Protection',
        description: 'Comprehensive protection measures',
        principles: [
          {
            id: '1',
            title: 'Transparency',
            description: 'Clear privacy policies',
            icon: 'fas fa-eye',
            implementation: ['Plain language notices', 'Regular updates']
          }
        ],
        userRights: [
          {
            id: '1',
            right: 'Right to Access',
            description: 'Request your data copy',
            howToExercise: 'Contact privacy team',
            responseTime: '30 days'
          }
        ],
        contactInfo: {
          email: 'privacy@test.com',
          phone: '+1-800-TEST',
          hours: 'Mon-Fri 9-6'
        }
      },
      complianceInfo: [
        {
          id: '1',
          standard: 'GDPR',
          description: 'European data protection compliance',
          badge: '/images/gdpr.png',
          certificationDate: new Date('2023-05-25'),
          scope: ['Data processing', 'User consent'],
          auditFrequency: 'Annual'
        }
      ]
    }
  })

  describe('Component Rendering', () => {
    it('renders the component with correct structure', () => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
      
      expect(wrapper.find('.security-privacy').exists()).toBe(true)
      expect(wrapper.find('h2').text()).toContain('Your Privacy & Security Matter')
    })

    it('renders institutional audience content correctly', () => {
      mockProps.audience = 'institutional'
      wrapper = mount(SecurityPrivacy, { props: mockProps })
      
      expect(wrapper.find('h2').text()).toContain('Enterprise Security & Compliance')
    })

    it('displays all privacy highlights', () => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
      
      const highlights = wrapper.findAll('.bg-white.rounded-lg.p-6.shadow-sm')
      expect(highlights.length).toBeGreaterThanOrEqual(mockProps.privacyHighlights.length)
      
      expect(wrapper.text()).toContain('Data Minimization')
      expect(wrapper.text()).toContain('User Control')
    })

    it('displays security certifications grid', () => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
      
      expect(wrapper.text()).toContain('Security Certifications')
      expect(wrapper.text()).toContain('SOC 2 Type II')
      expect(wrapper.text()).toContain('ISO 27001')
    })

    it('renders verification process steps', () => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
      
      expect(wrapper.text()).toContain('Alumni Verification')
      expect(wrapper.text()).toContain('Institution Check')
      expect(wrapper.text()).toContain('Identity Confirmation')
      expect(wrapper.text()).toContain('2-3 days')
    })

    it('displays data protection information', () => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
      
      expect(wrapper.text()).toContain('Data Protection')
      expect(wrapper.text()).toContain('Transparency')
      expect(wrapper.text()).toContain('Right to Access')
    })

    it('shows compliance standards', () => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
      
      expect(wrapper.text()).toContain('Compliance Standards')
      expect(wrapper.text()).toContain('GDPR')
      expect(wrapper.text()).toContain('European data protection compliance')
    })

    it('displays contact information', () => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
      
      expect(wrapper.text()).toContain('Questions About Privacy or Security?')
      expect(wrapper.text()).toContain('privacy@test.com')
      expect(wrapper.text()).toContain('+1-800-TEST')
      expect(wrapper.text()).toContain('Mon-Fri 9-6')
    })
  })

  describe('Interactive Features', () => {
    beforeEach(() => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
    })

    it('opens certification modal when certification is clicked', async () => {
      const certificationCard = wrapper.find('.group.cursor-pointer')
      await certificationCard.trigger('click')
      
      expect(wrapper.vm.selectedCertification).toBeTruthy()
      expect(wrapper.find('.fixed.inset-0.bg-black.bg-opacity-50').exists()).toBe(true)
    })

    it('closes certification modal when close button is clicked', async () => {
      // Open modal first
      const certificationCard = wrapper.find('.group.cursor-pointer')
      await certificationCard.trigger('click')
      
      // Close modal
      const closeButton = wrapper.find('.fa-times').element.parentElement
      await closeButton?.click()
      
      expect(wrapper.vm.selectedCertification).toBeNull()
    })

    it('closes certification modal when backdrop is clicked', async () => {
      // Open modal first
      const certificationCard = wrapper.find('.group.cursor-pointer')
      await certificationCard.trigger('click')
      
      // Click backdrop
      const backdrop = wrapper.find('.fixed.inset-0.bg-black.bg-opacity-50')
      await backdrop.trigger('click')
      
      expect(wrapper.vm.selectedCertification).toBeNull()
    })

    it('displays learn more links for privacy highlights', () => {
      const learnMoreLink = wrapper.find('a[href="/privacy"]')
      expect(learnMoreLink.exists()).toBe(true)
      expect(learnMoreLink.text()).toContain('Learn more')
    })

    it('displays verification URL for certifications with links', async () => {
      const certificationCard = wrapper.find('.group.cursor-pointer')
      await certificationCard.trigger('click')
      
      const verifyButton = wrapper.find('a[href="https://verify.com"]')
      expect(verifyButton.exists()).toBe(true)
      expect(verifyButton.text()).toContain('Verify Certification')
    })
  })

  describe('Data Formatting', () => {
    beforeEach(() => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
    })

    it('formats dates correctly', () => {
      expect(wrapper.text()).toContain('May 25, 2023')
    })

    it('displays certification categories correctly', () => {
      expect(wrapper.text()).toContain('security')
    })

    it('shows estimated times for verification steps', () => {
      expect(wrapper.text()).toContain('2-3 days')
      expect(wrapper.text()).toContain('1-2 days')
    })
  })

  describe('Accessibility', () => {
    beforeEach(() => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
    })

    it('has proper heading hierarchy', () => {
      const h2 = wrapper.find('h2')
      const h3s = wrapper.findAll('h3')
      const h4s = wrapper.findAll('h4')
      
      expect(h2.exists()).toBe(true)
      expect(h3s.length).toBeGreaterThan(0)
      expect(h4s.length).toBeGreaterThan(0)
    })

    it('has alt text for certification badges', () => {
      const certificationImages = wrapper.findAll('img[alt]')
      expect(certificationImages.length).toBeGreaterThan(0)
      
      certificationImages.forEach(img => {
        expect(img.attributes('alt')).toBeTruthy()
      })
    })

    it('has proper ARIA labels for interactive elements', () => {
      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        // Should have either text content or aria-label
        expect(button.text().length > 0 || button.attributes('aria-label')).toBeTruthy()
      })
    })

    it('has keyboard navigation support for modal', async () => {
      const certificationCard = wrapper.find('.group.cursor-pointer')
      await certificationCard.trigger('click')
      
      const modal = wrapper.find('.fixed.inset-0')
      expect(modal.exists()).toBe(true)
      
      // Modal should be focusable
      const modalContent = wrapper.find('.bg-white.rounded-xl')
      expect(modalContent.exists()).toBe(true)
    })
  })

  describe('Responsive Design', () => {
    beforeEach(() => {
      wrapper = mount(SecurityPrivacy, { props: mockProps })
    })

    it('has responsive grid classes', () => {
      const grids = wrapper.findAll('[class*="md:grid-cols"]')
      expect(grids.length).toBeGreaterThan(0)
    })

    it('has responsive text sizing', () => {
      const responsiveText = wrapper.findAll('[class*="md:text"]')
      expect(responsiveText.length).toBeGreaterThan(0)
    })

    it('has responsive spacing classes', () => {
      const responsiveSpacing = wrapper.findAll('[class*="md:space"]')
      expect(responsiveSpacing.length).toBeGreaterThan(0)
    })
  })

  describe('Error Handling', () => {
    it('handles missing optional props gracefully', () => {
      const minimalProps = {
        audience: 'individual' as const,
        privacyHighlights: [],
        securityCertifications: [],
        verificationProcess: {
          title: 'Test',
          description: 'Test',
          steps: [],
          benefits: [],
          requirements: []
        },
        dataProtection: {
          title: 'Test',
          description: 'Test',
          principles: [],
          userRights: [],
          contactInfo: {
            email: 'test@test.com',
            hours: 'Test hours'
          }
        },
        complianceInfo: []
      }
      
      expect(() => {
        wrapper = mount(SecurityPrivacy, { props: minimalProps })
      }).not.toThrow()
    })

    it('handles certifications without verification URLs', () => {
      const propsWithoutUrls = {
        ...mockProps,
        securityCertifications: [
          {
            id: '1',
            name: 'Test Cert',
            badge: '/test.png',
            description: 'Test description',
            category: 'security' as const
          }
        ]
      }
      
      wrapper = mount(SecurityPrivacy, { props: propsWithoutUrls })
      expect(wrapper.text()).toContain('Test Cert')
    })

    it('handles privacy highlights without learn more URLs', () => {
      const propsWithoutUrls = {
        ...mockProps,
        privacyHighlights: [
          {
            id: '1',
            title: 'Test Highlight',
            description: 'Test description',
            icon: 'fas fa-test',
            details: ['Test detail']
          }
        ]
      }
      
      wrapper = mount(SecurityPrivacy, { props: propsWithoutUrls })
      expect(wrapper.text()).toContain('Test Highlight')
      expect(wrapper.find('a[href="/privacy"]').exists()).toBe(false)
    })
  })

  describe('Performance', () => {
    it('renders efficiently with large datasets', () => {
      const largeProps = {
        ...mockProps,
        privacyHighlights: Array.from({ length: 20 }, (_, i) => ({
          id: `highlight-${i}`,
          title: `Highlight ${i}`,
          description: `Description ${i}`,
          icon: 'fas fa-test',
          details: [`Detail ${i}`]
        })),
        securityCertifications: Array.from({ length: 20 }, (_, i) => ({
          id: `cert-${i}`,
          name: `Certification ${i}`,
          badge: `/cert-${i}.png`,
          description: `Description ${i}`,
          category: 'security' as const
        }))
      }
      
      const startTime = performance.now()
      wrapper = mount(SecurityPrivacy, { props: largeProps })
      const endTime = performance.now()
      
      expect(endTime - startTime).toBeLessThan(100) // Should render in under 100ms
      expect(wrapper.exists()).toBe(true)
    })
  })
})