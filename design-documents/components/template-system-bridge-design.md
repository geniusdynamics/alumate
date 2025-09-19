# Template System Bridge Design

## Overview

This document outlines the design for the TemplateSystemBridge service that will integrate the existing Template Creation System with GrapeJS Page Builder. This bridge will enable loading templates into GrapeJS, saving GrapeJS pages as templates, and maintaining synchronization between both systems.

## Architecture

### Class Structure

```typescript
export class TemplateSystemBridge {
  private editor: grapesjs.Editor
  private templateService: TemplateService
  
  constructor(editor: grapesjs.Editor) {
    this.editor = editor
    this.templateService = new TemplateService()
  }
}
```

### Key Interfaces

```typescript
interface TemplateSystemBridgeInterface {
  loadTemplate(templateId: string): Promise<void>
  saveAsTemplate(metadata: TemplateMetadata): Promise<Template>
  applyTemplate(template: Template): void
  generatePreview(template: Template): Promise<string>
  syncTemplateUpdates(templateId: string): Promise<void>
}

interface GrapeJSTemplate {
  html: string
  css: string
 components: ComponentDefinition[]
  styles: StyleDefinition[]
  metadata: TemplateMetadata
}

interface TemplateMetadata {
  id?: number
  name: string
  description: string
 category: string
  tags: string[]
  isPremium: boolean
  thumbnail?: string
  tenantId?: string
}
```

## Core Functionality

### 1. Template Loading

The bridge will convert Template Creation System templates to GrapeJS format:

```typescript
async loadTemplate(templateId: string): Promise<void> {
  try {
    // Fetch template from Template Creation System
    const template = await this.templateService.fetchTemplate(templateId)
    
    // Convert to GrapeJS format
    const grapeJSTemplate = this.convertTemplateToGrapeJS(template)
    
    // Load into GrapeJS editor
    this.editor.setComponents(grapeJSTemplate.components)
    this.editor.setStyle(grapeJSTemplate.styles)
    
    // Apply template-specific configurations
    this.applyTemplateConfiguration(template)
  } catch (error) {
    console.error('Failed to load template:', error)
    throw error
 }
}
```

### 2. Template Saving

The bridge will convert GrapeJS pages to Template Creation System format:

```typescript
async saveAsTemplate(metadata: TemplateMetadata): Promise<Template> {
  // Extract data from GrapeJS editor
  const grapeJSData = {
    html: this.editor.getHtml(),
    css: this.editor.getCss(),
    components: this.editor.getComponents().toJSON(),
    styles: this.editor.getStyle().toJSON()
  }
  
  // Convert to Template Creation System format
  const templateData = this.convertGrapeJSToTemplate(grapeJSData, metadata)
  
  // Save to Template Creation System
  return await this.templateService.createTemplate(templateData)
}
```

### 3. Format Conversion

#### Template to GrapeJS Conversion

```typescript
private convertTemplateToGrapeJS(template: Template): GrapeJSTemplate {
  // Convert template structure to GrapeJS components
  const components = template.structure.sections.map(section => 
    this.convertSectionToComponent(section)
  )
  
  // Convert template styles to GrapeJS styles
  const styles = this.convertConfigToStyles(template.default_config)
  
 return {
    html: this.generateHTMLFromSections(template.structure.sections),
    css: this.generateCSSFromConfig(template.default_config),
    components,
    styles,
    metadata: template.metadata
  }
}
```

#### GrapeJS to Template Conversion

```typescript
private convertGrapeJSToTemplate(
  grapeJSData: any, 
  metadata: TemplateMetadata
): any {
  // Convert GrapeJS data to Template Creation System format
  return {
    name: metadata.name,
    description: metadata.description,
    category: metadata.category,
    tags: metadata.tags,
    is_premium: metadata.isPremium,
    thumbnail: metadata.thumbnail,
    structure: {
      sections: this.convertComponentsToSections(grapeJSData.components)
    },
    default_config: this.convertStylesToConfig(grapeJSData.styles),
    metadata: {
      ...metadata,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }
  }
}
```

## Integration Points

### 1. Component Library Bridge Integration

The TemplateSystemBridge will work alongside the ComponentLibraryBridge to ensure template components are properly registered:

```typescript
// When loading a template, ensure all components are registered
private async registerTemplateComponents(template: Template): Promise<void> {
  const componentIds = this.extractComponentIdsFromTemplate(template)
  
  for (const componentId of componentIds) {
    // Check if component is already registered
    if (!componentLibraryBridge.isComponentRegistered(componentId)) {
      // Register component with Component Library Bridge
      const component = await this.fetchComponent(componentId)
      componentLibraryBridge.registerComponent(component)
    }
  }
}
```

### 2. Real-time Synchronization

The bridge will support real-time template updates:

```typescript
async syncTemplateUpdates(templateId: string): Promise<void> {
  try {
    const updatedTemplate = await this.templateService.fetchTemplate(templateId)
    
    // Check if current template is loaded in editor
    const currentTemplateId = this.editor.getConfig().templateId
    if (currentTemplateId === templateId) {
      // Update the editor with new template data
      await this.loadTemplate(templateId)
    }
  } catch (error) {
    console.error('Failed to sync template updates:', error)
  }
}
```

## Error Handling

The bridge will implement comprehensive error handling:

```typescript
class TemplateSystemBridgeErrorHandler {
  handleTemplateLoadError(error: Error, templateId: string): void {
    // Log error
    console.error(`Failed to load template ${templateId}:`, error)
    
    // Show user-friendly error message
    // Depending on error type, suggest recovery actions
  }
  
  handleTemplateSaveError(error: Error, templateData: any): void {
    // Log error
    console.error('Failed to save template:', error)
    
    // Show user-friendly error message
    // Suggest retry or alternative actions
  }
  
  handleConversionError(error: Error, conversionType: string): void {
    // Log error with context
    console.error(`Failed ${conversionType} conversion:`, error)
    
    // Attempt fallback conversion or notify user
  }
}
```

## Performance Considerations

### 1. Caching

```typescript
private templateCache: Map<string, { template: Template; timestamp: number }> = new Map()
private cacheExpiry = 5 * 60 * 1000 // 5 minutes

private getCachedTemplate(templateId: string): Template | null {
  const cached = this.templateCache.get(templateId)
  if (cached && (Date.now() - cached.timestamp) < this.cacheExpiry) {
    return cached.template
  }
  return null
}
```

### 2. Lazy Loading

```typescript
async loadTemplateLazy(templateId: string): Promise<void> {
  // Show loading indicator
  this.showLoadingIndicator()
  
  try {
    // Load template in background
    await this.loadTemplate(templateId)
  } finally {
    // Hide loading indicator
    this.hideLoadingIndicator()
  }
}
```

## Testing Strategy

### Unit Tests

1. Template conversion functions
2. Error handling scenarios
3. Caching mechanisms
4. Integration with ComponentLibraryBridge

### Integration Tests

1. End-to-end template loading
2. Template saving and retrieval
3. Real-time synchronization
4. Performance with large templates

## Implementation Plan

### Phase 1: Core Bridge Implementation
- Implement basic TemplateSystemBridge class
- Create conversion functions between formats
- Add template loading functionality
- Add template saving functionality

### Phase 2: Integration Features
- Implement real-time synchronization
- Add caching mechanisms
- Integrate with ComponentLibraryBridge
- Add error handling

### Phase 3: Advanced Features
- Implement template preview generation
- Add batch operations
- Implement template versioning support
- Add analytics tracking

## Dependencies

- `grapesjs` - Core page builder engine
- `templateService` - Existing Template Creation System service
- `componentLibraryBridge` - Component Library integration bridge
- `httpService` - For API communication

## Security Considerations

- Validate all template data before loading into editor
- Sanitize HTML content to prevent XSS attacks
- Implement proper tenant isolation
- Validate user permissions for template operations