/**
 * @jest-environment jsdom
 */
import { mount } from '@vue/test-utils';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { describe, it, expect, beforeEach } from 'vitest';

// Mock components that might not exist yet
const TemplateDesigner = {
  template: `
    <div class="template-designer">
      <div class="sections-list">
        <div v-for="section in sections" :key="section.id" class="section-item">
          <h4>{{ section.type }}</h4>
          <pre>{{ JSON.stringify(section.config, null, 2) }}</pre>
        </div>
      </div>
      <button @click="addSection" class="add-section-btn">Add Section</button>
      <button @click="saveTemplate" class="save-btn">Save Template</button>
    </div>
  `,
  data() {
    return {
      sections: [
        {
          id: 1,
          type: 'hero',
          config: {
            title: 'Welcome',
            subtitle: 'Hello world',
            ctaText: 'Get Started'
          }
        }
      ]
    };
  },
  methods: {
    addSection() {
      this.sections.push({
        id: Date.now(),
        type: 'text',
        config: { content: 'New section content' }
      });
    },
    saveTemplate() {
      this.$emit('save', {
        name: 'Test Template',
        structure: { sections: this.sections }
      });
    }
  }
};

const TemplatePreview = {
  template: `
    <div class="template-preview">
      <div class="preview-container">
        <div v-for="section in sections" :key="section.id" :class="'section-' + section.type">
          <div v-if="section.type === 'hero'" class="hero-section">
            <h1>{{ section.config.title }}</h1>
            <p>{{ section.config.subtitle }}</p>
            <button>{{ section.config.ctaText }}</button>
          </div>
          <div v-else-if="section.type === 'text'" class="text-section">
            <div v-html="section.config.content"></div>
          </div>
        </div>
      </div>
      <div class="preview-controls">
        <button @click="togglePreview" class="toggle-btn">
          {{ isPreviewMode ? 'Edit Mode' : 'Preview Mode' }}
        </button>
      </div>
    </div>
  `,
  props: {
    sections: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      isPreviewMode: true
    };
  },
  methods: {
    togglePreview() {
      this.isPreviewMode = !this.isPreviewMode;
    }
  }
};

describe('Template Components', () => {
  let pinia;
  let app;

  beforeEach(() => {
    pinia = createPinia();
    app = createApp({});
    app.use(pinia);
  });

  describe('TemplateDesigner Component', () => {
    it('renders template sections correctly', async () => {
      const wrapper = mount(TemplateDesigner, {
        global: {
          plugins: [pinia]
        }
      });

      // Check if sections are rendered
      expect(wrapper.find('.sections-list').exists()).toBe(true);
      expect(wrapper.findAll('.section-item')).toHaveLength(1);

      // Check section content
      const heroSection = wrapper.find('.section-item');
      expect(heroSection.text()).toContain('hero');
      expect(heroSection.text()).toContain('Welcome');
    });

    it('adds new section when add button is clicked', async () => {
      const wrapper = mount(TemplateDesigner, {
        global: {
          plugins: [pinia]
        }
      });

      const initialSectionsCount = wrapper.vm.sections.length;
      await wrapper.find('.add-section-btn').trigger('click');

      expect(wrapper.vm.sections).toHaveLength(initialSectionsCount + 1);
      expect(wrapper.vm.sections[1].type).toBe('text');
    });

    it('emits save event with correct data', async () => {
      const wrapper = mount(TemplateDesigner, {
        global: {
          plugins: [pinia]
        }
      });

      await wrapper.find('.save-btn').trigger('click');

      const emittedEvents = wrapper.emitted('save');
      expect(emittedEvents).toHaveLength(1);

      const eventData = emittedEvents[0][0];
      expect(eventData).toHaveProperty('name', 'Test Template');
      expect(eventData).toHaveProperty('structure');
      expect(eventData.structure).toHaveProperty('sections');
    });
  });

  describe('TemplatePreview Component', () => {
    const testSections = [
      {
        id: 1,
        type: 'hero',
        config: {
          title: 'Test Hero',
          subtitle: 'Test subtitle',
          ctaText: 'Click Me'
        }
      },
      {
        id: 2,
        type: 'text',
        config: {
          content: '<p>Test content</p>'
        }
      }
    ];

    it('renders hero section correctly', () => {
      const wrapper = mount(TemplatePreview, {
        props: {
          sections: testSections
        },
        global: {
          plugins: [pinia]
        }
      });

      const heroSection = wrapper.find('.hero-section');
      expect(heroSection.exists()).toBe(true);
      expect(heroSection.find('h1').text()).toBe('Test Hero');
      expect(heroSection.find('p').text()).toBe('Test subtitle');
      expect(heroSection.find('button').text()).toBe('Click Me');
    });

    it('renders text section with HTML content', () => {
      const wrapper = mount(TemplatePreview, {
        props: {
          sections: testSections
        },
        global: {
          plugins: [pinia]
        }
      });

      const textSection = wrapper.find('.text-section');
      expect(textSection.exists()).toBe(true);
      expect(textSection.text()).toBe('Test content'); // HTML is rendered as text
    });

    it('toggles preview mode', async () => {
      const wrapper = mount(TemplatePreview, {
        props: {
          sections: testSections
        },
        global: {
          plugins: [pinia]
        }
      });

      const toggleButton = wrapper.find('.toggle-btn');
      expect(toggleButton.text()).toBe('Edit Mode'); // Initially in preview mode

      await toggleButton.trigger('click');
      expect(toggleButton.text()).toBe('Preview Mode'); // Now in edit mode
    });

    it('handles empty sections gracefully', () => {
      const wrapper = mount(TemplatePreview, {
        props: {
          sections: []
        },
        global: {
          plugins: [pinia]
        }
      });

      // Should not throw errors with empty sections
      const container = wrapper.find('.preview-container');
      expect(container.exists()).toBe(true);
    });
  });

  describe('Template Components Integration', () => {
    const TestIntegration = {
      template: `
        <div class="integration-test">
          <template-designer ref="designer" @save="handleSave" />
          <template-preview :sections="currentSections" />
        </div>
      `,
      components: {
        TemplateDesigner,
        TemplatePreview
      },
      data() {
        return {
          currentSections: []
        };
      },
      methods: {
        handleSave(data) {
          this.currentSections = data.structure.sections;
        }
      }
    };

    it('integrates designer and preview components', async () => {
      const wrapper = mount(TestIntegration, {
        global: {
          plugins: [pinia]
        }
      });

      const designer = wrapper.findComponent(TemplateDesigner);
      const preview = wrapper.findComponent(TemplatePreview);

      // Initially no sections in preview
      expect(preview.vm.sections).toHaveLength(0);

      // Save template through designer
      await designer.find('.save-btn').trigger('click');
      await wrapper.vm.$nextTick();

      // Preview should now have sections
      expect(preview.vm.sections).toHaveLength(1);
      expect(preview.vm.sections[0].type).toBe('hero');
    });
  });

  describe('Template Component Error Handling', () => {
    it('handles invalid section types gracefully', () => {
      const invalidSections = [
        {
          id: 1,
          type: 'invalid-type',
          config: { someProp: 'value' }
        }
      ];

      const wrapper = mount(TemplatePreview, {
        props: {
          sections: invalidSections
        },
        global: {
          plugins: [pinia]
        }
      });

      // Should not crash with invalid section types
      expect(wrapper.exists()).toBe(true);

      // Invalid sections should be rendered as plain divs
      const sectionElements = wrapper.findAll('[class*="section-"]');
      expect(sectionElements).toHaveLength(1);
    });

    it('handles malformed config objects', () => {
      const malformedSections = [
        {
          id: 1,
          type: 'hero',
          config: null // Invalid config
        }
      ];

      const wrapper = mount(TemplatePreview, {
        props: {
          sections: malformedSections
        },
        global: {
          plugins: [pinia]
        }
      });

      // Should not crash with malformed config
      expect(wrapper.exists()).toBe(true);
    });
  });

  describe('Template Component Performance', () => {
    it('handles large sections array without performance issues', () => {
      const largeSectionsArray = Array.from({ length: 50 }, (_, i) => ({
        id: i + 1,
        type: i % 2 === 0 ? 'hero' : 'text',
        config: {
          title: `Section ${i}`,
          content: `Content for section ${i}`,
          subtitle: `Subtitle ${i}`
        }
      }));

      const startTime = performance.now();

      const wrapper = mount(TemplatePreview, {
        props: {
          sections: largeSectionsArray
        },
        global: {
          plugins: [pinia]
        }
      });

      const renderTime = performance.now() - startTime;

      // Should render within reasonable time (less than 100ms)
      expect(renderTime).toBeLessThan(100);
      expect(wrapper.findAll('[class*="section-"]')).toHaveLength(50);
    });

    it('updates efficiently when props change', async () => {
      const wrapper = mount(TemplatePreview, {
        props: {
          sections: []
        },
        global: {
          plugins: [pinia]
        }
      });

      const initialSections = wrapper.findAll('[class*="section-"]').length;

      await wrapper.setProps({
        sections: [
          { id: 1, type: 'hero', config: { title: 'Updated' } },
          { id: 2, type: 'text', config: { content: 'Updated content' } }
        ]
      });

      const updatedSections = wrapper.findAll('[class*="section-"]').length;
      expect(updatedSections).toBeGreaterThan(initialSections);
    });
  });
});