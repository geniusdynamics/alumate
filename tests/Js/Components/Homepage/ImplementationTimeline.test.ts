import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ImplementationTimeline from '@/components/homepage/ImplementationTimeline.vue'
import type { DevelopmentPhase } from '@/types/homepage'

const mockPhases: DevelopmentPhase[] = [
  {
    id: '1',
    name: 'Discovery & Planning',
    description: 'Initial assessment and project planning phase',
    duration: '4 weeks',
    deliverables: ['Project plan', 'Technical specifications', 'Resource allocation'],
    dependencies: [],
    milestones: [
      {
        id: '1-1',
        name: 'Kickoff meeting',
        description: 'Project initiation meeting',
        dueDate: 'Week 1',
        status: 'completed'
      },
      {
        id: '1-2',
        name: 'Requirements sign-off',
        description: 'Stakeholder approval of requirements',
        dueDate: 'Week 3',
        status: 'completed'
      }
    ],
    status: 'completed'
  },
  {
    id: '2',
    name: 'Platform Setup',
    description: 'Technical implementation and configuration',
    duration: '6 weeks',
    deliverables: ['Configured platform', 'Data migration', 'Custom branding'],
    dependencies: ['Discovery & Planning'],
    milestones: [
      {
        id: '2-1',
        name: 'Platform deployment',
        description: 'Initial platform setup',
        dueDate: 'Week 6',
        status: 'in_progress'
      },
      {
        id: '2-2',
        name: 'Data validation',
        description: 'Verify migrated data integrity',
        dueDate: 'Week 8',
        status: 'pending'
      }
    ],
    status: 'in_progress'
  },
  {
    id: '3',
    name: 'Launch & Training',
    description: 'Go-live and user training phase',
    duration: '3 weeks',
    deliverables: ['Trained staff', 'Live platform', 'Documentation'],
    dependencies: ['Platform Setup'],
    milestones: [
      {
        id: '3-1',
        name: 'Staff training complete',
        description: 'All staff trained on new platform',
        dueDate: 'Week 11',
        status: 'pending'
      }
    ],
    status: 'pending'
  }
]

const mockProps = {
  title: 'Implementation Timeline',
  subtitle: 'Step-by-step project execution plan',
  phases: mockPhases,
  totalDuration: '13 weeks'
}

describe('ImplementationTimeline', () => {
  it('renders component with title and subtitle', () => {
    const wrapper = mount(ImplementationTimeline, {
      props: mockProps
    })

    expect(wrapper.text(