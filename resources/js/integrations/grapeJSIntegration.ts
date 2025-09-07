/**
 * GrapeJS Integration Main Module
 * 
 * This module provides the main integration layer between the Component Library System
 * and GrapeJS Page Builder, orchestrating all the integration services and utilities.
 */

import type {
  Component,
  ComponentCategory,
  GrapeJSBlockMetadata,
  GrapeJSSerializationData,
  ComponentGrapeJSMetadata
} from '@/types/components'

import { componentLibraryBridge } from '@/services/ComponentLibraryBridge'
import { grapeJSBlockGenerator } from '@/utils/grapeJSBlockGenerator'
import { componentSerializer } from '@/utils/componentSerialization'
import { componentSchemaValidator } from '@/utils/componentSchemaValidator'
import { componentPreviewGenerator } from '@/services/ComponentPreviewGenerator'

export interface GrapeJSIntegrationOptions {
  enablePreviewGeneration?: boolean
  enableValidation?: boolean
  enableSerialization?: boolean
  autoSync?: boolean
  cacheEnabled?: boolean
  debugMode?: boolean
}

export interface IntegrationResult {
  success: boolean
  data?: any
  errors?: string[]
  warnings?: string[]
  metadata?: Record<string, any>
}

export interface ComponentRegistrationResult {
  registered: number
  failed: number
  errors: string[]
  blocks: GrapeJSBlockMetadata[]
}

export class GrapeJSIntegration {
  private options: GrapeJSIntegrationOptions
  private registeredComponents: Map<string, Component> = new Map()
  private blockCache: Map<string, GrapeJSBlockMetadata> = new Map()
  private previewCache: Map<string, string> = new Map()
  private editor: any = null

  constructor(options: GrapeJSIntegrationOptions = {}) {
    this.options = {
      enablePreviewGeneration: true,
      enableValidation: true,
      enableSerialization: true,
      autoSync: false,
      cacheEnabled: true,
      debugMode: false,
      ...options
    }

    this.log('GrapeJS Integration initialized with options:', this.options)
  }

  /**
   * Initialize the integration with a GrapeJS editor instance
   */
  initialize(editor: any): IntegrationResult {
    try {
      this.editor = editor
      this.log('Initializing GrapeJS integration with editor:', editor)

      // Set up editor event listeners
      this.setupEditorListeners()

      // Register custom component types
      this.registerCustomComponentTypes()

      // Set up block manager categories
      this.setupBlockCategories()

      return {
        success: true,
        metadata: {
          editorVersion: editor.getVersion?.() || 'unknown',
          integrationVersion: '1.0.0',
          featuresEnabled: this.options
        }
      }
    } catch (error) {
      return {
        success: false,
        errors: [`Failed to initialize GrapeJS integration: ${error instanceof Error ? error.message : 'Unknown error'}`]
      }
    }
  }

  /**
   * Register Component Library components with GrapeJS
   */
  async registerComponents(components: Component[]): Promise<ComponentRegistrationResult> {
    const result: ComponentRegistrationResult = {
      registered: 0,
      failed: 0,
      errors: [],
      blocks: []
    }

    this.log(`Registering ${components.length} components with GrapeJS`)

    for (const component of components) {
      try {
        // Validate component if validation is enabled
        if (this.options.enableValidation) {
          const validation = componentSchemaValidator.validateComponent(component)
          if (!validation.valid) {
            result.errors.push(`Component ${component.id}: ${validation.errors.map(e => e.message).join(', ')}`)
            result.failed++
            continue
          }
        }

        // Register component with bridge
        componentLibraryBridge.registerComponent(component)

        // Generate GrapeJS block
        const block = await this.generateComponentBlock(component)
        
        // Add block to GrapeJS editor
        if (this.editor) {
          this.editor.BlockManager.add(block.id, block)
        }

        // Cache the block
        if (this.options.cacheEnabled) {
          this.blockCache.set(component.id, block)
        }

        // Store registered component
        this.registeredComponents.set(component.id, component)

        result.blocks.push(block)
        result.registered++

        this.log(`Successfully registered component: ${component.name} (${component.id})`)
      } catch (error) {
        const errorMessage = `Failed to register component ${component.id}: ${error instanceof Error ? error.message : 'Unknown error'}`
        result.errors.push(errorMessage)
        result.failed++
        this.log(errorMessage, 'error')
      }
    }

    this.log(`Component registration complete. Registered: ${result.registered}, Failed: ${result.failed}`)
    return result
  }

  /**
   * Generate GrapeJS block for a component
   */
  async generateComponentBlock(component: Component): Promise<GrapeJSBlockMetadata> {
    // Check cache first
    if (this.options.cacheEnabled && this.blockCache.has(component.id)) {
      return this.blockCache.get(component.id)!
    }

    // Generate block using block generator
    const block = grapeJSBlockGenerator.generateBlock(component)

    // Generate preview image if enabled
    if (this.options.enablePreviewGeneration) {
      try {
        const previewResult = await componentPreviewGenerator.generatePreview(component, {
          width: 200,
          height: 120,
          format: 'png'
        })

        if (previewResult.success && previewResult.dataUrl) {
          block.media = previewResult.dataUrl
          
          // Cache preview
          if (this.options.cacheEnabled) {
            this.previewCache.set(component.id, previewResult.dataUrl)
          }
        }
      } catch (error) {
        this.log(`Failed to generate preview for component ${component.id}: ${error}`, 'warn')
      }
    }

    return block
  }

  /**
   * Sync component updates with GrapeJS
   */
  async syncComponentUpdates(componentId: string): Promise<IntegrationResult> {
    try {
      if (!this.registeredComponents.has(componentId)) {
        return {
          success: false,
          errors: [`Component ${componentId} is not registered`]
        }
      }

      // Sync with bridge
      await componentLibraryBridge.syncComponentUpdates(componentId)

      // Update block in editor if available
      if (this.editor) {
        const component = this.registeredComponents.get(componentId)!
        const updatedBlock = await this.generateComponentBlock(component)
        
        const blockManager = this.editor.BlockManager
        const existingBlock = blockManager.get(`component-${componentId}`)
        
        if (existingBlock) {
          existingBlock.set(updatedBlock)
        }
      }

      // Clear cache
      if (this.options.cacheEnabled) {
        this.blockCache.delete(componentId)
        this.previewCache.delete(componentId)
      }

      return {
        success: true,
        metadata: {
          componentId,
          syncedAt: new Date().toISOString()
        }
      }
    } catch (error) {
      return {
        success: false,
        errors: [`Failed to sync component ${componentId}: ${error instanceof Error ? error.message : 'Unknown error'}`]
      }
    }
  }

  /**
   * Export current editor content to Component Library format
   */
  async exportToComponentLibrary(): Promise<IntegrationResult> {
    if (!this.editor) {
      return {
        success: false,
        errors: ['GrapeJS editor not initialized']
      }
    }

    try {
      // Get editor data
      const editorData = {
        html: this.editor.getHtml(),
        css: this.editor.getCss(),
        components: this.editor.getComponents().toJSON(),
        styles: this.editor.getStyle().toJSON(),
        assets: this.editor.getAssets().toJSON()
      }

      // Serialize to Component Library format
      const serializationResult = componentSerializer.deserialize(editorData)

      if (!serializationResult.success) {
        return {
          success: false,
          errors: serializationResult.errors || ['Serialization failed']
        }
      }

      return {
        success: true,
        data: serializationResult.components,
        warnings: serializationResult.warnings,
        metadata: {
          exportedAt: new Date().toISOString(),
          componentCount: serializationResult.components?.length || 0
        }
      }
    } catch (error) {
      return {
        success: false,
        errors: [`Export failed: ${error instanceof Error ? error.message : 'Unknown error'}`]
      }
    }
  }

  /**
   * Import Component Library components into editor
   */
  async importFromComponentLibrary(components: Component[]): Promise<IntegrationResult> {
    if (!this.editor) {
      return {
        success: false,
        errors: ['GrapeJS editor not initialized']
      }
    }

    try {
      // Serialize components to GrapeJS format
      const serializationResult = componentSerializer.serialize(components)

      if (!serializationResult.success) {
        return {
          success: false,
          errors: serializationResult.errors || ['Serialization failed']
        }
      }

      // Load into editor
      const grapeJSData = serializationResult.data!
      this.editor.setComponents(grapeJSData.components)
      this.editor.setStyle(grapeJSData.styles)

      return {
        success: true,
        warnings: serializationResult.warnings,
        metadata: {
          importedAt: new Date().toISOString(),
          componentCount: components.length
        }
      }
    } catch (error) {
      return {
        success: false,
        errors: [`Import failed: ${error instanceof Error ? error.message : 'Unknown error'}`]
      }
    }
  }

  /**
   * Get all registered components
   */
  getRegisteredComponents(): Component[] {
    return Array.from(this.registeredComponents.values())
  }

  /**
   * Get component by ID
   */
  getComponent(componentId: string): Component | undefined {
    return this.registeredComponents.get(componentId)
  }

  /**
   * Clear all caches
   */
  clearCache(): void {
    this.blockCache.clear()
    this.previewCache.clear()
    componentLibraryBridge.clearRegistry()
    this.log('All caches cleared')
  }

  /**
   * Get integration statistics
   */
  getStatistics(): Record<string, any> {
    return {
      registeredComponents: this.registeredComponents.size,
      cachedBlocks: this.blockCache.size,
      cachedPreviews: this.previewCache.size,
      options: this.options,
      editorConnected: !!this.editor
    }
  }

  // Private methods

  private setupEditorListeners(): void {
    if (!this.editor) return

    // Listen for component selection
    this.editor.on('component:selected', (component: any) => {
      const componentId = component.get('attributes')?.['data-component-id']
      if (componentId) {
        this.log(`Component selected: ${componentId}`)
        this.onComponentSelected(componentId, component)
      }
    })

    // Listen for component updates
    this.editor.on('component:update', (component: any) => {
      const componentId = component.get('attributes')?.['data-component-id']
      if (componentId && this.options.autoSync) {
        this.log(`Component updated: ${componentId}`)
        this.syncComponentUpdates(componentId)
      }
    })

    // Listen for storage events
    this.editor.on('storage:start:store', () => {
      this.log('Editor storage started')
    })

    this.editor.on('storage:end:store', () => {
      this.log('Editor storage completed')
    })
  }

  private registerCustomComponentTypes(): void {
    if (!this.editor) return

    const domComponents = this.editor.DomComponents

    // Register hero component type
    domComponents.addType('hero-component', {
      model: {
        defaults: {
          tagName: 'section',
          attributes: { class: 'hero-component' },
          traits: [
            { type: 'text', name: 'headline', label: 'Headline' },
            { type: 'text', name: 'subheading', label: 'Subheading' },
            { 
              type: 'select', 
              name: 'audienceType', 
              label: 'Audience Type',
              options: [
                { id: 'individual', name: 'Individual' },
                { id: 'institution', name: 'Institution' },
                { id: 'employer', name: 'Employer' }
              ]
            }
          ]
        }
      }
    })

    // Register form component type
    domComponents.addType('form-component', {
      model: {
        defaults: {
          tagName: 'form',
          attributes: { class: 'form-component' },
          traits: [
            { type: 'text', name: 'title', label: 'Form Title' },
            { type: 'text', name: 'action', label: 'Action URL' }
          ]
        }
      }
    })

    // Register other component types...
    this.log('Custom component types registered')
  }

  private setupBlockCategories(): void {
    if (!this.editor) return

    const blockManager = this.editor.BlockManager

    // Define categories
    const categories = [
      { id: 'hero-sections', label: 'Hero Sections', open: true },
      { id: 'forms-lead-capture', label: 'Forms & Lead Capture', open: false },
      { id: 'testimonials-reviews', label: 'Testimonials & Reviews', open: false },
      { id: 'statistics-metrics', label: 'Statistics & Metrics', open: false },
      { id: 'call-to-actions', label: 'Call to Actions', open: false },
      { id: 'media-gallery', label: 'Media & Gallery', open: false }
    ]

    // Add categories to block manager
    categories.forEach(category => {
      blockManager.add(category.id, {
        label: category.label,
        open: category.open,
        attributes: { class: 'block-category' }
      })
    })

    this.log('Block categories set up')
  }

  private onComponentSelected(componentId: string, grapeComponent: any): void {
    // Handle component selection
    const component = this.registeredComponents.get(componentId)
    if (component) {
      // Update property panel with component-specific traits
      this.updatePropertyPanel(component, grapeComponent)
    }
  }

  private updatePropertyPanel(component: Component, grapeComponent: any): void {
    // Update the property panel with component-specific configuration options
    // This would typically involve updating the traits panel in GrapeJS
    this.log(`Updating property panel for component: ${component.name}`)
  }

  private log(message: string, level: 'info' | 'warn' | 'error' = 'info'): void {
    if (this.options.debugMode) {
      const timestamp = new Date().toISOString()
      const prefix = `[GrapeJS Integration ${timestamp}]`
      
      switch (level) {
        case 'warn':
          console.warn(prefix, message)
          break
        case 'error':
          console.error(prefix, message)
          break
        default:
          console.log(prefix, message)
      }
    }
  }
}

// Export singleton instance
export const grapeJSIntegration = new GrapeJSIntegration()

// Export utility functions for direct use
export {
  componentLibraryBridge,
  grapeJSBlockGenerator,
  componentSerializer,
  componentSchemaValidator,
  componentPreviewGenerator
}

// Export types
export type {
  GrapeJSIntegrationOptions,
  IntegrationResult,
  ComponentRegistrationResult
}