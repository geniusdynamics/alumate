import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import InstitutionalCaseStudy from '@/components/homepage/InstitutionalCaseStudy.vue'
import type { InstitutionalCaseStudy as CaseStudyType } from '@/types/homepage'

const mockCaseStudy: CaseStudyType = {
  id: '1',
  title: 'Transforming Alumni Engagement at MIT',
  institutionName: 'Massachusetts Institute of Technology',
  institutionType: 'university',
  challenge: 'MIT struggled with low alumni engagement rates and outdated communication methods.',
  solution: 'Implemented our comprehensive alumni platform with branded mobile app and advanced analytics.',
  implementation: [
    {
      phase: 'Discovery & Planning',
      duration: '4 weeks',
      activities: ['Stakeholder interviews', 'Requirements gathering', 'Technical assessment'],
      deliverables: ['Project plan', 'Technical specifications'],
      milestones: ['Kickoff meeting', 'Requirements sign-off']
    },
    {
      phase: 'Platform Setup',
      duration: '6 weeks',
      activities: ['Platform configuration', 'Data migration', 'Custom branding'],
      deliverables: ['Configured platform', 'Migrated data'],
      milestones: ['Platform ready', 'Data validation complete']
    },
    {
      phase: 'Launch & Training',
      duration: '3 weeks',
      activities: ['Staff training', 'Soft launch', 'Full deployment'],
      deliverables: ['Trained staff', 'Live platform'],
      milestones: ['Training complete', 'Go-live']
    }
  ],
  results: [
    {
      metric: 'engagement',
      beforeValue: 15,
      afterValue: 65,
      improvementPercentage: 333,
      timeframe: '12 months',
      verified: true
    },
    {
      metric: 'event_attendance',
      beforeValue: 200,
      afterValue: 800,
      improvementPercentage: 300,
      timeframe: '12 months',
      verified: true
    },
    {
      metric: 'app_downloads',
      beforeValue: 0,
      afterValue: 15000,
      improvementPercentage: 100,
      timeframe: '6 months',
      verified: true
    }
  ],
  timeline: '13 weeks',
  alumniCount: 150000,
  engagementIncrease: 333,
  featured: true
}

describe('InstitutionalCaseStudy', () => {
  it('renders case study header information correctly', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    expect(wrapper.text()).toContain('Transforming Alumni Engagement at MIT')
    expect(wrapper.text()).toContain('Massachusetts Institute of Technology')
    expect(wrapper.text()).toContain('university')
    expect(wrapper.text()).toContain('150K')
    expect(wrapper.text()).toContain('Alumni')
  })

  it('displays challenge section with correct icon and content', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    expect(wrapper.text()).toContain('Challenge')
    expect(wrapper.text()).toContain('MIT struggled with low alumni engagement rates and outdated communication methods.')
    
    // Check for challenge icon (red warning icon)
    const challengeSection = wrapper.findAll('h4').filter(node => node.text().includes('Challenge'))
    expect(challengeSection.length).toBeGreaterThan(0)
  })

  it('displays solution section with correct icon and content', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    expect(wrapper.text()).toContain('Solution')
    expect(wrapper.text()).toContain('Implemented our comprehensive alumni platform with branded mobile app and advanced analytics.')
  })

  it('renders implementation timeline correctly', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    expect(wrapper.text()).toContain('Implementation (13 weeks)')
    expect(wrapper.text()).toContain('Discovery & Planning')
    expect(wrapper.text()).toContain('4 weeks')
    expect(wrapper.text()).toContain('Platform Setup')
    expect(wrapper.text()).toContain('6 weeks')
    expect(wrapper.text()).toContain('Launch & Training')
    expect(wrapper.text()).toContain('3 weeks')

    // Check for activities
    expect(wrapper.text()).toContain('Stakeholder interviews')
    expect(wrapper.text()).toContain('Platform configuration')
  })

  it('displays results metrics in grid format', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    expect(wrapper.text()).toContain('Results')
    expect(wrapper.text()).toContain('+333%')
    expect(wrapper.text()).toContain('+300%')
    expect(wrapper.text()).toContain('+100%')

    // Check formatted metric labels
    expect(wrapper.text()).toContain('Engagement')
    expect(wrapper.text()).toContain('Event Attendance')
    expect(wrapper.text()).toContain('App Downloads')

    // Check before/after values
    expect(wrapper.text()).toContain('15 → 65')
    expect(wrapper.text()).toContain('200 → 800')
    expect(wrapper.text()).toContain('0 → 15000')
  })

  it('shows verification badges for verified results', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    const verifiedBadges = wrapper.findAll('.text-green-600').filter(node => node.text().includes('Verified'))
    expect(verifiedBadges.length).toBe(3) // All results are verified
  })

  it('displays key success factor highlight', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    expect(wrapper.text()).toContain('Key Success Factor:')
    expect(wrapper.text()).toContain('333% increase in alumni engagement within 13 weeks')
  })

  it('shows request demo button in footer', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    const demoButton = wrapper.findAll('button').filter(node => node.text().includes('Request Similar Demo'))[0]
    expect(demoButton.exists()).toBe(true)
  })

  it('emits request-demo event when demo button is clicked', async () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    const demoButton = wrapper.findAll('button').filter(node => node.text().includes('Request Similar Demo'))[0]
    await demoButton.trigger('click')

    expect(wrapper.emitted('request-demo')).toBeTruthy()
    expect(wrapper.emitted('request-demo')?.[0]).toEqual([mockCaseStudy])
  })

  it('formats alumni count correctly', () => {
    const testCases = [
      { count: 500, expected: '500' },
      { count: 1500, expected: '1.5K' },
      { count: 150000, expected: '150.0K' },
      { count: 2500000, expected: '2.5M' }
    ]

    testCases.forEach(({ count, expected }) => {
      const caseStudyWithCount = {
        ...mockCaseStudy,
        alumniCount: count
      }

      const wrapper = mount(InstitutionalCaseStudy, {
        props: {
          caseStudy: caseStudyWithCount
        }
      })

      expect(wrapper.text()).toContain(expected)
    })
  })

  it('formats metric labels correctly', () => {
    const caseStudyWithVariousMetrics = {
      ...mockCaseStudy,
      results: [
        {
          metric: 'alumni_participation',
          beforeValue: 20,
          afterValue: 80,
          improvementPercentage: 300,
          timeframe: '12 months',
          verified: true
        },
        {
          metric: 'job_placements',
          beforeValue: 50,
          afterValue: 200,
          improvementPercentage: 300,
          timeframe: '12 months',
          verified: false
        }
      ]
    }

    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: caseStudyWithVariousMetrics
      }
    })

    expect(wrapper.text()).toContain('Alumni Participation')
    expect(wrapper.text()).toContain('Job Placements')
  })

  it('handles case study without implementation phases', () => {
    const caseStudyWithoutImplementation = {
      ...mockCaseStudy,
      implementation: []
    }

    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: caseStudyWithoutImplementation
      }
    })

    // Should still render other sections
    expect(wrapper.text()).toContain('Challenge')
    expect(wrapper.text()).toContain('Solution')
    expect(wrapper.text()).toContain('Results')
    
    // Implementation section should not be shown
    expect(wrapper.text()).not.toContain('Implementation (13 weeks)')
  })

  it('limits implementation phases to first 3', () => {
    const caseStudyWithManyPhases = {
      ...mockCaseStudy,
      implementation: [
        ...mockCaseStudy.implementation,
        {
          phase: 'Phase 4',
          duration: '2 weeks',
          activities: ['Activity 1', 'Activity 2'],
          deliverables: ['Deliverable 1'],
          milestones: ['Milestone 1']
        },
        {
          phase: 'Phase 5',
          duration: '1 week',
          activities: ['Activity 1'],
          deliverables: ['Deliverable 1'],
          milestones: ['Milestone 1']
        }
      ]
    }

    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: caseStudyWithManyPhases
      }
    })

    // Should only show first 3 phases
    expect(wrapper.text()).toContain('Discovery & Planning')
    expect(wrapper.text()).toContain('Platform Setup')
    expect(wrapper.text()).toContain('Launch & Training')
    expect(wrapper.text()).not.toContain('Phase 4')
    expect(wrapper.text()).not.toContain('Phase 5')
  })

  it('limits activities to first 2 per phase', () => {
    const caseStudyWithManyActivities = {
      ...mockCaseStudy,
      implementation: [
        {
          phase: 'Test Phase',
          duration: '4 weeks',
          activities: ['Activity 1', 'Activity 2', 'Activity 3', 'Activity 4'],
          deliverables: ['Deliverable 1'],
          milestones: ['Milestone 1']
        }
      ]
    }

    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: caseStudyWithManyActivities
      }
    })

    expect(wrapper.text()).toContain('Activity 1')
    expect(wrapper.text()).toContain('Activity 2')
    expect(wrapper.text()).not.toContain('Activity 3')
    expect(wrapper.text()).not.toContain('Activity 4')
  })

  it('applies correct styling classes', () => {
    const wrapper = mount(InstitutionalCaseStudy, {
      props: {
        caseStudy: mockCaseStudy
      }
    })

    const container = wrapper.find('.institutional-case-study')
    expect(container.exists()).toBe(true)
    expect(container.classes()).toContain('bg-white')
    expect(container.classes()).toContain('rounded-lg')
    expect(container.classes()).toContain('shadow-lg')
  })
})