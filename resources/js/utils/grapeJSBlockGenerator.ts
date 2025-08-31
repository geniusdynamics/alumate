/**
 * GrapeJS Block Generator Utilities
 * 
 * This module provides utilities for generating GrapeJS block definitions
 * from Component Library components with proper categorization and metadata.
 */

import type {
  Component,
  ComponentCategory,
  GrapeJSBlockMetadata,
  GrapeJSComponentDefinition,
  HeroComponentConfig,
  FormComponentConfig,
  TestimonialComponentConfig,
  StatisticsComponentConfig,
  CTAComponentConfig,
  MediaComponentConfig
} from '@/types/components'

export interface GrapeJSBlockGeneratorOptions {
  includePreviewImages?: boolean
  generateResponsiveVariants?: boolean
  includeAccessibilityMetadata?: boolean
  customCategoryMapping?: Record<ComponentCategory, string>
}

export class GrapeJSBlockGenerator {
  private options: GrapeJSBlockGeneratorOptions

  constructor(options: GrapeJSBlockGeneratorOptions = {}) {
    this.options = {
      includePreviewImages: true,
      generateResponsiveVariants: true,
      includeAccessibilityMetadata: true,
      ...options
    }
  }

  /**
   * Generate GrapeJS block metadata for all component categories
   */
  generateAllBlocks(components: Component[]): GrapeJSBlockMetadata[] {
    return components.map(component => this.generateBlock(component))
  }

  /**
   * Generate GrapeJS block metadata for a single component
   */
  generateBlock(component: Component): GrapeJSBlockMetadata {
    const baseBlock: GrapeJSBlockMetadata = {
      id: `component-${component.id}`,
      label: component.name,
      category: this.getCategoryName(component.category),
      media: this.getComponentIcon(component.category),
      content: this.generateComponentDefinition(component),
      attributes: {
        'data-component-id': component.id,
        'data-component-type': component.type,
        'data-component-category': component.category,
        'data-tenant-id': component.tenantId,
        'data-version': component.version
      },
      activate: true,
      select: true
    }

    // Add preview image if enabled
    if (this.options.includePreviewImages) {
      baseBlock.media = this.generatePreviewImage(component)
    }

    return baseBlock
  }

  /**
   * Generate component definition based on category
   */
  private generateComponentDefinition(component: Component): GrapeJSComponentDefinition {
    switch (component.category) {
      case 'hero':
        return this.generateHeroDefinition(component, component.config as HeroComponentConfig)
      case 'forms':
        return this.generateFormDefinition(component, component.config as FormComponentConfig)
      case 'testimonials':
        return this.generateTestimonialDefinition(component, component.config as TestimonialComponentConfig)
      case 'statistics':
        return this.generateStatisticsDefinition(component, component.config as StatisticsComponentConfig)
      case 'ctas':
        return this.generateCTADefinition(component, component.config as CTAComponentConfig)
      case 'media':
        return this.generateMediaDefinition(component, component.config as MediaComponentConfig)
      default:
        return this.generateDefaultDefinition(component)
    }
  }

  /**
   * Generate Hero component definition
   */
  private generateHeroDefinition(component: Component, config: HeroComponentConfig): GrapeJSComponentDefinition {
    return {
      type: 'hero-component',
      tagName: 'section',
      attributes: {
        'data-component-id': component.id,
        'data-audience-type': config.audienceType,
        'data-layout': config.layout,
        class: `hero-section hero-${config.audienceType} hero-${config.layout}`,
        role: 'banner'
      },
      components: [
        {
          type: 'hero-background',
          tagName: 'div',
          attributes: {
            class: 'hero-background absolute inset-0'
          },
          components: this.generateHeroBackgroundContent(config)
        },
        {
          type: 'hero-content',
          tagName: 'div',
          attributes: {
            class: 'hero-content relative z-10 container mx-auto px-4'
          },
          components: [
            {
              type: 'text',
              tagName: `h${config.headingLevel}`,
              attributes: {
                class: 'hero-headline text-4xl md:text-6xl font-bold mb-6'
              },
              components: config.headline
            },
            ...(config.subheading ? [{
              type: 'text',
              tagName: 'p',
              attributes: {
                class: 'hero-subheading text-xl md:text-2xl mb-8'
              },
              components: config.subheading
            }] : []),
            ...(config.description ? [{
              type: 'text',
              tagName: 'p',
              attributes: {
                class: 'hero-description text-lg mb-8'
              },
              components: config.description
            }] : []),
            ...(config.ctaButtons.length > 0 ? [{
              type: 'hero-cta-container',
              tagName: 'div',
              attributes: {
                class: 'hero-cta-container flex flex-wrap gap-4'
              },
              components: config.ctaButtons.map(cta => ({
                type: 'link',
                tagName: 'a',
                attributes: {
                  href: cta.url,
                  class: `cta-button cta-${cta.style} cta-${cta.size}`,
                  'data-cta-id': cta.id
                },
                components: cta.text
              }))
            }] : []),
            ...(config.statistics && config.statistics.length > 0 ? [{
              type: 'hero-statistics',
              tagName: 'div',
              attributes: {
                class: 'hero-statistics mt-12 grid grid-cols-2 md:grid-cols-4 gap-8'
              },
              components: config.statistics.map(stat => ({
                type: 'statistic-item',
                tagName: 'div',
                attributes: {
                  class: 'statistic-item text-center',
                  'data-statistic-id': stat.id
                },
                components: [
                  {
                    type: 'text',
                    tagName: 'div',
                    attributes: {
                      class: 'statistic-value text-3xl font-bold'
                    },
                    components: String(stat.value)
                  },
                  {
                    type: 'text',
                    tagName: 'div',
                    attributes: {
                      class: 'statistic-label text-sm'
                    },
                    components: stat.label
                  }
                ]
              }))
            }] : [])
          ]
        }
      ],
      traits: [
        {
          type: 'text',
          name: 'headline',
          label: 'Headline',
          default: config.headline,
          changeProp: true
        },
        {
          type: 'text',
          name: 'subheading',
          label: 'Subheading',
          default: config.subheading,
          changeProp: true
        },
        {
          type: 'select',
          name: 'audienceType',
          label: 'Audience Type',
          options: [
            { id: 'individual', name: 'Individual Alumni' },
            { id: 'institution', name: 'Institution' },
            { id: 'employer', name: 'Employer' }
          ],
          default: config.audienceType,
          changeProp: true
        },
        {
          type: 'select',
          name: 'layout',
          label: 'Layout',
          options: [
            { id: 'centered', name: 'Centered' },
            { id: 'left-aligned', name: 'Left Aligned' },
            { id: 'right-aligned', name: 'Right Aligned' },
            { id: 'split', name: 'Split Layout' }
          ],
          default: config.layout,
          changeProp: true
        }
      ],
      style: {
        'min-height': '100vh',
        'display': 'flex',
        'align-items': 'center',
        'position': 'relative'
      },
      droppable: false,
      draggable: true,
      copyable: true,
      removable: true,
      stylable: true,
      selectable: true
    }
  }

  /**
   * Generate Form component definition
   */
  private generateFormDefinition(component: Component, config: FormComponentConfig): GrapeJSComponentDefinition {
    return {
      type: 'form-component',
      tagName: 'form',
      attributes: {
        'data-component-id': component.id,
        'data-form-layout': config.layout,
        class: `form-component form-${config.layout} form-${config.theme}`,
        method: config.submission.method,
        action: config.submission.action,
        novalidate: true
      },
      components: [
        ...(config.title ? [{
          type: 'text',
          tagName: 'h2',
          attributes: {
            class: 'form-title text-2xl font-bold mb-4'
          },
          components: config.title
        }] : []),
        ...(config.description ? [{
          type: 'text',
          tagName: 'p',
          attributes: {
            class: 'form-description text-gray-600 mb-6'
          },
          components: config.description
        }] : []),
        {
          type: 'form-fields-container',
          tagName: 'div',
          attributes: {
            class: `form-fields ${this.getFormLayoutClasses(config.layout)}`
          },
          components: config.fields.map(field => ({
            type: 'form-field',
            tagName: 'div',
            attributes: {
              class: `form-field form-field-${field.type}`,
              'data-field-name': field.name
            },
            components: [
              {
                type: 'text',
                tagName: 'label',
                attributes: {
                  for: field.id,
                  class: 'form-label block text-sm font-medium mb-2'
                },
                components: field.label
              },
              {
                type: 'form-input',
                tagName: this.getInputTagName(field.type),
                attributes: {
                  id: field.id,
                  name: field.name,
                  type: field.type,
                  placeholder: field.placeholder || '',
                  required: field.required || false,
                  class: 'form-input w-full px-3 py-2 border border-gray-300 rounded-md'
                }
              }
            ]
          }))
        },
        {
          type: 'form-submit',
          tagName: 'button',
          attributes: {
            type: 'submit',
            class: 'form-submit bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700'
          },
          components: 'Submit'
        }
      ],
      traits: [
        {
          type: 'text',
          name: 'title',
          label: 'Form Title',
          default: config.title,
          changeProp: true
        },
        {
          type: 'text',
          name: 'action',
          label: 'Form Action URL',
          default: config.submission.action,
          changeProp: true
        },
        {
          type: 'select',
          name: 'layout',
          label: 'Layout',
          options: [
            { id: 'single-column', name: 'Single Column' },
            { id: 'two-column', name: 'Two Column' },
            { id: 'grid', name: 'Grid Layout' }
          ],
          default: config.layout,
          changeProp: true
        }
      ],
      style: {
        'max-width': '600px',
        'margin': '0 auto',
        'padding': '2rem',
        'background': '#ffffff',
        'border-radius': '8px',
        'box-shadow': '0 4px 6px rgba(0, 0, 0, 0.1)'
      },
      droppable: false,
      draggable: true,
      copyable: true,
      removable: true,
      stylable: true,
      selectable: true
    }
  }

  /**
   * Generate Testimonial component definition
   */
  private generateTestimonialDefinition(component: Component, config: TestimonialComponentConfig): GrapeJSComponentDefinition {
    return {
      type: 'testimonial-component',
      tagName: 'section',
      attributes: {
        'data-component-id': component.id,
        'data-layout': config.layout,
        class: `testimonial-section testimonial-${config.layout}`,
        role: 'region',
        'aria-label': 'Customer testimonials'
      },
      components: [
        {
          type: 'testimonial-container',
          tagName: 'div',
          attributes: {
            class: `testimonial-container ${this.getTestimonialLayoutClasses(config.layout)}`
          },
          components: config.testimonials.slice(0, 3).map(testimonial => ({
            type: 'testimonial-item',
            tagName: 'div',
            attributes: {
              class: 'testimonial-item bg-white p-6 rounded-lg shadow-md',
              'data-testimonial-id': testimonial.id
            },
            components: [
              {
                type: 'text',
                tagName: 'blockquote',
                attributes: {
                  class: 'testimonial-quote text-lg italic mb-4'
                },
                components: `"${testimonial.content.quote}"`
              },
              {
                type: 'testimonial-author',
                tagName: 'div',
                attributes: {
                  class: 'testimonial-author flex items-center'
                },
                components: [
                  ...(config.showAuthorPhoto && testimonial.author.photo ? [{
                    type: 'image',
                    tagName: 'img',
                    attributes: {
                      src: testimonial.author.photo.url,
                      alt: testimonial.author.name,
                      class: 'author-photo w-12 h-12 rounded-full mr-4'
                    }
                  }] : []),
                  {
                    type: 'author-info',
                    tagName: 'div',
                    components: [
                      {
                        type: 'text',
                        tagName: 'div',
                        attributes: {
                          class: 'author-name font-semibold'
                        },
                        components: testimonial.author.name
                      },
                      ...(config.showAuthorTitle && testimonial.author.title ? [{
                        type: 'text',
                        tagName: 'div',
                        attributes: {
                          class: 'author-title text-sm text-gray-600'
                        },
                        components: testimonial.author.title
                      }] : [])
                    ]
                  }
                ]
              }
            ]
          }))
        }
      ],
      traits: [
        {
          type: 'select',
          name: 'layout',
          label: 'Layout',
          options: [
            { id: 'single', name: 'Single Testimonial' },
            { id: 'carousel', name: 'Carousel' },
            { id: 'grid', name: 'Grid Layout' },
            { id: 'masonry', name: 'Masonry' }
          ],
          default: config.layout,
          changeProp: true
        },
        {
          type: 'checkbox',
          name: 'showAuthorPhoto',
          label: 'Show Author Photo',
          default: config.showAuthorPhoto,
          changeProp: true
        },
        {
          type: 'checkbox',
          name: 'showRating',
          label: 'Show Rating',
          default: config.showRating,
          changeProp: true
        }
      ],
      style: {
        'padding': '3rem 1rem',
        'background-color': '#f8f9fa'
      },
      droppable: false,
      draggable: true,
      copyable: true,
      removable: true,
      stylable: true,
      selectable: true
    }
  }

  /**
   * Generate Statistics component definition
   */
  private generateStatisticsDefinition(component: Component, config: StatisticsComponentConfig): GrapeJSComponentDefinition {
    return {
      type: 'statistics-component',
      tagName: 'section',
      attributes: {
        'data-component-id': component.id,
        'data-display-type': config.displayType,
        class: `statistics-section statistics-${config.displayType}`,
        role: 'region',
        'aria-label': 'Statistics and metrics'
      },
      components: [
        {
          type: 'statistics-container',
          tagName: 'div',
          attributes: {
            class: `statistics-container ${this.getStatisticsLayoutClasses(config.layout)}`
          },
          components: [
            {
              type: 'statistic-item',
              tagName: 'div',
              attributes: {
                class: 'statistic-item text-center p-6'
              },
              components: [
                {
                  type: 'text',
                  tagName: 'div',
                  attributes: {
                    class: 'statistic-value text-4xl font-bold text-blue-600 mb-2'
                  },
                  components: '10,000+'
                },
                {
                  type: 'text',
                  tagName: 'div',
                  attributes: {
                    class: 'statistic-label text-lg text-gray-700'
                  },
                  components: 'Alumni Connected'
                }
              ]
            },
            {
              type: 'statistic-item',
              tagName: 'div',
              attributes: {
                class: 'statistic-item text-center p-6'
              },
              components: [
                {
                  type: 'text',
                  tagName: 'div',
                  attributes: {
                    class: 'statistic-value text-4xl font-bold text-green-600 mb-2'
                  },
                  components: '95%'
                },
                {
                  type: 'text',
                  tagName: 'div',
                  attributes: {
                    class: 'statistic-label text-lg text-gray-700'
                  },
                  components: 'Success Rate'
                }
              ]
            }
          ]
        }
      ],
      traits: [
        {
          type: 'select',
          name: 'displayType',
          label: 'Display Type',
          options: [
            { id: 'counters', name: 'Animated Counters' },
            { id: 'progress', name: 'Progress Bars' },
            { id: 'charts', name: 'Charts' },
            { id: 'mixed', name: 'Mixed Display' }
          ],
          default: config.displayType,
          changeProp: true
        },
        {
          type: 'select',
          name: 'layout',
          label: 'Layout',
          options: [
            { id: 'grid', name: 'Grid' },
            { id: 'row', name: 'Horizontal Row' },
            { id: 'column', name: 'Vertical Column' }
          ],
          default: config.layout,
          changeProp: true
        }
      ],
      style: {
        'padding': '3rem 1rem',
        'background-color': '#ffffff'
      },
      droppable: false,
      draggable: true,
      copyable: true,
      removable: true,
      stylable: true,
      selectable: true
    }
  }

  /**
   * Generate CTA component definition
   */
  private generateCTADefinition(component: Component, config: CTAComponentConfig): GrapeJSComponentDefinition {
    const ctaConfig = config.buttonConfig || config.bannerConfig || config.inlineLinkConfig

    return {
      type: 'cta-component',
      tagName: config.type === 'banner' ? 'section' : 'div',
      attributes: {
        'data-component-id': component.id,
        'data-cta-type': config.type,
        class: `cta-component cta-${config.type}`,
        role: config.type === 'banner' ? 'banner' : undefined
      },
      components: [
        {
          type: 'cta-content',
          tagName: 'div',
          attributes: {
            class: `cta-content ${config.type === 'banner' ? 'text-center py-16 px-8' : 'inline-block'}`
          },
          components: [
            ...(config.type === 'banner' && config.bannerConfig?.title ? [{
              type: 'text',
              tagName: 'h2',
              attributes: {
                class: 'cta-title text-3xl font-bold mb-4'
              },
              components: config.bannerConfig.title
            }] : []),
            ...(config.type === 'banner' && config.bannerConfig?.subtitle ? [{
              type: 'text',
              tagName: 'p',
              attributes: {
                class: 'cta-subtitle text-xl mb-8'
              },
              components: config.bannerConfig.subtitle
            }] : []),
            {
              type: 'link',
              tagName: 'a',
              attributes: {
                href: ctaConfig?.url || '#',
                class: this.getCTAClasses(config),
                'data-cta-tracking': JSON.stringify(ctaConfig?.trackingParams || {})
              },
              components: ctaConfig?.text || 'Click Here'
            }
          ]
        }
      ],
      traits: [
        {
          type: 'text',
          name: 'text',
          label: 'CTA Text',
          default: ctaConfig?.text,
          changeProp: true
        },
        {
          type: 'text',
          name: 'url',
          label: 'URL',
          default: ctaConfig?.url,
          changeProp: true
        },
        {
          type: 'select',
          name: 'type',
          label: 'CTA Type',
          options: [
            { id: 'button', name: 'Button' },
            { id: 'banner', name: 'Banner' },
            { id: 'inline-link', name: 'Inline Link' }
          ],
          default: config.type,
          changeProp: true
        }
      ],
      style: config.type === 'banner' ? {
        'background-color': '#3b82f6',
        'color': '#ffffff',
        'text-align': 'center',
        'padding': '4rem 2rem'
      } : {
        'display': 'inline-block'
      },
      droppable: false,
      draggable: true,
      copyable: true,
      removable: true,
      stylable: true,
      selectable: true
    }
  }

  /**
   * Generate Media component definition
   */
  private generateMediaDefinition(component: Component, config: MediaComponentConfig): GrapeJSComponentDefinition {
    return {
      type: 'media-component',
      tagName: 'section',
      attributes: {
        'data-component-id': component.id,
        'data-media-type': config.type,
        class: `media-section media-${config.type}`,
        role: 'region',
        'aria-label': 'Media content'
      },
      components: [
        {
          type: 'media-container',
          tagName: 'div',
          attributes: {
            class: `media-container ${this.getMediaLayoutClasses(config.layout)}`
          },
          components: config.mediaAssets.slice(0, 6).map((asset, index) => ({
            type: 'media-item',
            tagName: 'div',
            attributes: {
              class: 'media-item',
              'data-media-id': asset.id
            },
            components: [
              asset.type === 'image' ? {
                type: 'image',
                tagName: 'img',
                attributes: {
                  src: asset.url,
                  alt: asset.alt || `Media item ${index + 1}`,
                  class: 'media-image w-full h-auto rounded-lg',
                  loading: config.optimization.lazyLoading ? 'lazy' : 'eager'
                }
              } : {
                type: 'video',
                tagName: 'video',
                attributes: {
                  src: asset.url,
                  class: 'media-video w-full h-auto rounded-lg',
                  controls: true,
                  preload: 'metadata'
                }
              }
            ]
          }))
        }
      ],
      traits: [
        {
          type: 'select',
          name: 'type',
          label: 'Media Type',
          options: [
            { id: 'image-gallery', name: 'Image Gallery' },
            { id: 'video-embed', name: 'Video Embed' },
            { id: 'interactive-demo', name: 'Interactive Demo' }
          ],
          default: config.type,
          changeProp: true
        },
        {
          type: 'select',
          name: 'layout',
          label: 'Layout',
          options: [
            { id: 'grid', name: 'Grid' },
            { id: 'masonry', name: 'Masonry' },
            { id: 'carousel', name: 'Carousel' },
            { id: 'single', name: 'Single Item' }
          ],
          default: config.layout,
          changeProp: true
        },
        {
          type: 'checkbox',
          name: 'lazyLoading',
          label: 'Lazy Loading',
          default: config.optimization.lazyLoading,
          changeProp: true
        }
      ],
      style: {
        'padding': '2rem 1rem'
      },
      droppable: false,
      draggable: true,
      copyable: true,
      removable: true,
      stylable: true,
      selectable: true
    }
  }

  /**
   * Generate default component definition
   */
  private generateDefaultDefinition(component: Component): GrapeJSComponentDefinition {
    return {
      type: 'default-component',
      tagName: 'div',
      attributes: {
        'data-component-id': component.id,
        class: 'component-placeholder'
      },
      components: [
        {
          type: 'text',
          tagName: 'h3',
          components: component.name
        },
        {
          type: 'text',
          tagName: 'p',
          components: component.description || 'Component content will be rendered here'
        }
      ],
      style: {
        'padding': '2rem',
        'border': '2px dashed #ccc',
        'text-align': 'center',
        'background-color': '#f9f9f9'
      },
      droppable: false,
      draggable: true,
      copyable: true,
      removable: true,
      stylable: true,
      selectable: true
    }
  }

  // Helper methods

  private getCategoryName(category: ComponentCategory): string {
    const categoryNames: Record<ComponentCategory, string> = {
      hero: 'Hero Sections',
      forms: 'Forms & Lead Capture',
      testimonials: 'Testimonials & Reviews',
      statistics: 'Statistics & Metrics',
      ctas: 'Call to Actions',
      media: 'Media & Gallery'
    }

    return this.options.customCategoryMapping?.[category] || categoryNames[category]
  }

  private getComponentIcon(category: ComponentCategory): string {
    const icons: Record<ComponentCategory, string> = {
      hero: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>',
      forms: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>',
      testimonials: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 9h10v1H7zm0 2h10v1H7zm0 2h7v1H7z"/></svg>',
      statistics: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/></svg>',
      ctas: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>',
      media: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>'
    }

    return icons[category]
  }

  private generatePreviewImage(component: Component): string {
    // This would typically generate or fetch a preview image
    // For now, return a placeholder based on category
    const placeholders: Record<ComponentCategory, string> = {
      hero: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjNGNEY2Ii8+Cjx0ZXh0IHg9IjEwMCIgeT0iNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzM3NDE1MSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkhlcm8gU2VjdGlvbjwvdGV4dD4KPC9zdmc+',
      forms: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRkVGRkZGIi8+Cjx0ZXh0IHg9IjEwMCIgeT0iNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzM3NDE1MSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkZvcm0gQ29tcG9uZW50PC90ZXh0Pgo8L3N2Zz4=',
      testimonials: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjhGOUZBIi8+Cjx0ZXh0IHg9IjEwMCIgeT0iNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzM3NDE1MSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlRlc3RpbW9uaWFsczwvdGV4dD4KPC9zdmc+',
      statistics: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRkVGRkZGIi8+Cjx0ZXh0IHg9IjEwMCIgeT0iNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzM3NDE1MSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlN0YXRpc3RpY3M8L3RleHQ+Cjwvc3ZnPg==',
      ctas: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRTNGMkZEIi8+Cjx0ZXh0IHg9IjEwMCIgeT0iNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzM3NDE1MSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkNhbGwgdG8gQWN0aW9uPC90ZXh0Pgo8L3N2Zz4=',
      media: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRkFGQUZBIi8+Cjx0ZXh0IHg9IjEwMCIgeT0iNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzM3NDE1MSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk1lZGlhIEdhbGxlcnk8L3RleHQ+Cjwvc3ZnPg=='
    }

    return placeholders[category]
  }

  private generateHeroBackgroundContent(config: HeroComponentConfig): GrapeJSComponentDefinition[] {
    if (!config.backgroundMedia) return []

    switch (config.backgroundMedia.type) {
      case 'image':
        return [{
          type: 'image',
          tagName: 'img',
          attributes: {
            src: config.backgroundMedia.image?.url || '',
            alt: config.backgroundMedia.image?.alt || '',
            class: 'w-full h-full object-cover'
          }
        }]
      case 'video':
        return [{
          type: 'video',
          tagName: 'video',
          attributes: {
            src: config.backgroundMedia.video?.url || '',
            autoplay: config.backgroundMedia.video?.autoplay || false,
            muted: config.backgroundMedia.video?.muted || true,
            loop: config.backgroundMedia.video?.loop || true,
            class: 'w-full h-full object-cover'
          }
        }]
      case 'gradient':
        return [{
          type: 'div',
          tagName: 'div',
          attributes: {
            class: 'w-full h-full'
          },
          style: this.generateGradientStyle(config.backgroundMedia.gradient!)
        }]
      default:
        return []
    }
  }

  private generateGradientStyle(gradient: any): Record<string, string> {
    const colorStops = gradient.colors
      .map((c: any) => `${c.color} ${c.stop}%`)
      .join(', ')

    if (gradient.type === 'radial') {
      return {
        background: `radial-gradient(circle, ${colorStops})`
      }
    }

    return {
      background: `linear-gradient(${gradient.direction || '135deg'}, ${colorStops})`
    }
  }

  private getFormLayoutClasses(layout: string): string {
    const layoutClasses: Record<string, string> = {
      'single-column': 'space-y-6',
      'two-column': 'grid grid-cols-1 md:grid-cols-2 gap-6',
      'grid': 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6'
    }

    return layoutClasses[layout] || 'space-y-6'
  }

  private getTestimonialLayoutClasses(layout: string): string {
    const layoutClasses: Record<string, string> = {
      single: 'max-w-2xl mx-auto',
      carousel: 'flex overflow-x-auto space-x-6',
      grid: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6',
      masonry: 'columns-1 md:columns-2 lg:columns-3 gap-6'
    }

    return layoutClasses[layout] || 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'
  }

  private getStatisticsLayoutClasses(layout: string): string {
    const layoutClasses: Record<string, string> = {
      grid: 'grid grid-cols-2 md:grid-cols-4 gap-8',
      row: 'flex flex-wrap justify-center gap-8',
      column: 'flex flex-col space-y-8'
    }

    return layoutClasses[layout] || 'grid grid-cols-2 md:grid-cols-4 gap-8'
  }

  private getMediaLayoutClasses(layout: string): string {
    const layoutClasses: Record<string, string> = {
      grid: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4',
      masonry: 'columns-1 sm:columns-2 lg:columns-3 gap-4',
      carousel: 'flex overflow-x-auto space-x-4',
      single: 'max-w-2xl mx-auto'
    }

    return layoutClasses[layout] || 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4'
  }

  private getCTAClasses(config: CTAComponentConfig): string {
    if (config.type === 'button' && config.buttonConfig) {
      const { style, size } = config.buttonConfig
      const baseClasses = 'inline-flex items-center justify-center font-medium rounded-md transition-colors'
      
      const styleClasses: Record<string, string> = {
        primary: 'bg-blue-600 text-white hover:bg-blue-700',
        secondary: 'bg-gray-600 text-white hover:bg-gray-700',
        outline: 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white',
        ghost: 'text-blue-600 hover:bg-blue-50'
      }
      
      const sizeClasses: Record<string, string> = {
        xs: 'px-2.5 py-1.5 text-xs',
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2 text-base',
        lg: 'px-6 py-3 text-lg',
        xl: 'px-8 py-4 text-xl'
      }
      
      return `${baseClasses} ${styleClasses[style]} ${sizeClasses[size]}`
    }
    
    if (config.type === 'inline-link') {
      return 'text-blue-600 hover:text-blue-800 underline'
    }
    
    return 'inline-block'
  }

  private getInputTagName(fieldType: string): string {
    const tagMap: Record<string, string> = {
      textarea: 'textarea',
      select: 'select'
    }

    return tagMap[fieldType] || 'input'
  }
}

// Export singleton instance
export const grapeJSBlockGenerator = new GrapeJSBlockGenerator()