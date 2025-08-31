import type {
  ComponentGroup,
  ComponentRelationship,
  ComponentCategory,
  Component
} from '@/types/components';

/**
 * Service for managing component grouping and relationships for GrapeJS operations
 */
export class ComponentGroupingService {
  private static instance: ComponentGroupingService;
  
  // Predefined component groups for better organization
  private readonly defaultGroups: ComponentGroup[] = [
    {
      id: 'hero-variants',
      name: 'Hero Sections',
      description: 'Hero components optimized for different audiences',
      components: [],
      category: 'hero',
      tags: ['landing', 'header', 'banner'],
      grapeJSCategory: 'Hero Components',
      sortOrder: 1,
      icon: 'hero',
      color: '#3B82F6'
    },
    {
      id: 'form-templates',
      name: 'Form Templates',
      description: 'Pre-built forms for lead capture and contact',
      components: [],
      category: 'forms',
      tags: ['forms', 'lead-capture', 'contact'],
      grapeJSCategory: 'Forms',
      sortOrder: 2,
      icon: 'form',
      color: '#10B981'
    },
    {
      id: 'social-proof',
      name: 'Social Proof',
      description: 'Testimonials and reviews to build trust',
      components: [],
      category: 'testimonials',
      tags: ['testimonials', 'reviews', 'social-proof'],
      grapeJSCategory: 'Social Proof',
      sortOrder: 3,
      icon: 'testimonial',
      color: '#F59E0B'
    },
    {
      id: 'data-visualization',
      name: 'Statistics & Metrics',
      description: 'Components to showcase data and achievements',
      components: [],
      category: 'statistics',
      tags: ['statistics', 'metrics', 'data'],
      grapeJSCategory: 'Statistics',
      sortOrder: 4,
      icon: 'chart',
      color: '#8B5CF6'
    },
    {
      id: 'conversion-elements',
      name: 'Call-to-Actions',
      description: 'Buttons and CTAs to drive conversions',
      components: [],
      category: 'ctas',
      tags: ['cta', 'buttons', 'conversion'],
      grapeJSCategory: 'CTAs',
      sortOrder: 5,
      icon: 'cta',
      color: '#EF4444'
    },
    {
      id: 'media-showcase',
      name: 'Media & Content',
      description: 'Images, videos, and interactive content',
      components: [],
      category: 'media',
      tags: ['media', 'images', 'videos'],
      grapeJSCategory: 'Media',
      sortOrder: 6,
      icon: 'media',
      color: '#06B6D4'
    },
    {
      id: 'mobile-optimized',
      name: 'Mobile-First',
      description: 'Components optimized for mobile devices',
      components: [],
      tags: ['mobile', 'responsive', 'touch'],
      grapeJSCategory: 'Mobile Components',
      sortOrder: 7,
      icon: 'mobile',
      color: '#84CC16'
    },
    {
      id: 'accessibility-focused',
      name: 'Accessibility Enhanced',
      description: 'Components with enhanced accessibility features',
      components: [],
      tags: ['accessibility', 'a11y', 'wcag'],
      grapeJSCategory: 'Accessible Components',
      sortOrder: 8,
      icon: 'accessibility',
      color: '#6366F1'
    }
  ];

  public static getInstance(): ComponentGroupingService {
    if (!ComponentGroupingService.instance) {
      ComponentGroupingService.instance = new ComponentGroupingService();
    }
    return ComponentGroupingService.instance;
  }

  /**
   * Get all predefined component groups
   */
  public getDefaultGroups(): ComponentGroup[] {
    return [...this.defaultGroups];
  }

  /**
   * Get group by ID
   */
  public getGroup(groupId: string): ComponentGroup | undefined {
    return this.defaultGroups.find(group => group.id === groupId);
  }

  /**
   * Get groups by category
   */
  public getGroupsByCategory(category: ComponentCategory): ComponentGroup[] {
    return this.defaultGroups.filter(group => group.category === category);
  }

  /**
   * Create a new component group
   */
  public createGroup(
    name: string,
    description: string,
    category?: ComponentCategory,
    tags: string[] = []
  ): ComponentGroup {
    const id = this.generateGroupId(name);
    
    return {
      id,
      name,
      description,
      components: [],
      category,
      tags,
      grapeJSCategory: name,
      sortOrder: this.defaultGroups.length + 1,
      icon: 'group',
      color: '#6B7280'
    };
  }

  /**
   * Add component to group
   */
  public addComponentToGroup(groupId: string, componentId: string): boolean {
    const group = this.defaultGroups.find(g => g.id === groupId);
    if (!group) return false;

    if (!group.components.includes(componentId)) {
      group.components.push(componentId);
    }
    
    return true;
  }

  /**
   * Remove component from group
   */
  public removeComponentFromGroup(groupId: string, componentId: string): boolean {
    const group = this.defaultGroups.find(g => g.id === groupId);
    if (!group) return false;

    const index = group.components.indexOf(componentId);
    if (index > -1) {
      group.components.splice(index, 1);
      return true;
    }
    
    return false;
  }

  /**
   * Get groups containing a specific component
   */
  public getGroupsForComponent(componentId: string): ComponentGroup[] {
    return this.defaultGroups.filter(group => 
      group.components.includes(componentId)
    );
  }

  /**
   * Create component relationship
   */
  public createRelationship(
    type: ComponentRelationship['type'],
    componentId: string,
    relatedComponentId: string,
    metadata?: Record<string, any>
  ): ComponentRelationship {
    return {
      type,
      componentId,
      relationshipId: relatedComponentId,
      metadata: metadata || {}
    };
  }

  /**
   * Get component relationships
   */
  public getComponentRelationships(
    componentId: string,
    relationships: ComponentRelationship[]
  ): {
    parents: ComponentRelationship[];
    children: ComponentRelationship[];
    siblings: ComponentRelationship[];
    dependencies: ComponentRelationship[];
    variants: ComponentRelationship[];
  } {
    const result = {
      parents: [] as ComponentRelationship[],
      children: [] as ComponentRelationship[],
      siblings: [] as ComponentRelationship[],
      dependencies: [] as ComponentRelationship[],
      variants: [] as ComponentRelationship[]
    };

    relationships.forEach(rel => {
      if (rel.componentId === componentId) {
        switch (rel.type) {
          case 'parent':
            result.parents.push(rel);
            break;
          case 'child':
            result.children.push(rel);
            break;
          case 'sibling':
            result.siblings.push(rel);
            break;
          case 'dependency':
            result.dependencies.push(rel);
            break;
          case 'variant':
            result.variants.push(rel);
            break;
        }
      }
    });

    return result;
  }

  /**
   * Auto-group components based on their properties
   */
  public autoGroupComponents(components: Component[]): Map<string, string[]> {
    const groupAssignments = new Map<string, string[]>();

    components.forEach(component => {
      const groups = this.determineAutoGroups(component);
      
      groups.forEach(groupId => {
        if (!groupAssignments.has(groupId)) {
          groupAssignments.set(groupId, []);
        }
        groupAssignments.get(groupId)!.push(component.id);
      });
    });

    return groupAssignments;
  }

  /**
   * Determine which groups a component should belong to automatically
   */
  private determineAutoGroups(component: Component): string[] {
    const groups: string[] = [];

    // Add to category-specific group
    if (component.category) {
      const categoryGroup = this.defaultGroups.find(g => g.category === component.category);
      if (categoryGroup) {
        groups.push(categoryGroup.id);
      }
    }

    // Check for mobile optimization
    if (this.isMobileOptimized(component)) {
      groups.push('mobile-optimized');
    }

    // Check for accessibility features
    if (this.hasAccessibilityFeatures(component)) {
      groups.push('accessibility-focused');
    }

    return groups;
  }

  /**
   * Check if component is mobile-optimized
   */
  private isMobileOptimized(component: Component): boolean {
    const config = component.config as any;
    
    // Check for responsive configuration
    if (config.responsive && config.responsive.mobile) {
      return true;
    }

    // Check for mobile-specific settings
    if (config.mobileLayout || config.mobileOptimized) {
      return true;
    }

    // Check for touch-friendly features
    if (config.touchGestures || config.touchSupport) {
      return true;
    }

    return false;
  }

  /**
   * Check if component has accessibility features
   */
  private hasAccessibilityFeatures(component: Component): boolean {
    const config = component.config as any;
    
    // Check for accessibility configuration
    if (config.accessibility) {
      return true;
    }

    // Check for ARIA attributes
    if (config.ariaLabel || config.ariaDescribedBy || config.role) {
      return true;
    }

    // Check for keyboard navigation
    if (config.keyboardNavigation || config.keyboardSupport) {
      return true;
    }

    // Check for screen reader support
    if (config.screenReaderText || config.screenReaderSupport) {
      return true;
    }

    return false;
  }

  /**
   * Generate GrapeJS block categories from groups
   */
  public generateGrapeJSCategories(): Array<{
    id: string;
    label: string;
    order?: number;
  }> {
    return this.defaultGroups
      .filter(group => group.grapeJSCategory)
      .map(group => ({
        id: group.id,
        label: group.grapeJSCategory!,
        order: group.sortOrder
      }))
      .sort((a, b) => (a.order || 0) - (b.order || 0));
  }

  /**
   * Get component hierarchy for GrapeJS tree view
   */
  public getComponentHierarchy(
    components: Component[],
    relationships: ComponentRelationship[]
  ): any[] {
    const hierarchy: any[] = [];
    const processedComponents = new Set<string>();

    // Find root components (no parents)
    const rootComponents = components.filter(component => {
      const hasParent = relationships.some(rel => 
        rel.type === 'child' && rel.componentId === component.id
      );
      return !hasParent;
    });

    // Build hierarchy recursively
    rootComponents.forEach(component => {
      if (!processedComponents.has(component.id)) {
        const node = this.buildHierarchyNode(
          component,
          components,
          relationships,
          processedComponents
        );
        hierarchy.push(node);
      }
    });

    return hierarchy;
  }

  /**
   * Build hierarchy node recursively
   */
  private buildHierarchyNode(
    component: Component,
    allComponents: Component[],
    relationships: ComponentRelationship[],
    processedComponents: Set<string>
  ): any {
    processedComponents.add(component.id);

    const node = {
      id: component.id,
      name: component.name,
      type: component.type,
      category: component.category,
      children: [] as any[]
    };

    // Find child components
    const childRelationships = relationships.filter(rel => 
      rel.type === 'parent' && rel.componentId === component.id
    );

    childRelationships.forEach(rel => {
      const childComponent = allComponents.find(c => c.id === rel.relationshipId);
      if (childComponent && !processedComponents.has(childComponent.id)) {
        const childNode = this.buildHierarchyNode(
          childComponent,
          allComponents,
          relationships,
          processedComponents
        );
        node.children.push(childNode);
      }
    });

    return node;
  }

  /**
   * Generate component search index for GrapeJS
   */
  public generateSearchIndex(components: Component[]): Map<string, Component[]> {
    const searchIndex = new Map<string, Component[]>();

    components.forEach(component => {
      // Index by name
      this.addToSearchIndex(searchIndex, component.name.toLowerCase(), component);
      
      // Index by category
      if (component.category) {
        this.addToSearchIndex(searchIndex, component.category, component);
      }
      
      // Index by type
      this.addToSearchIndex(searchIndex, component.type.toLowerCase(), component);
      
      // Index by description keywords
      if (component.description) {
        const keywords = component.description.toLowerCase().split(/\s+/);
        keywords.forEach(keyword => {
          if (keyword.length > 2) { // Skip very short words
            this.addToSearchIndex(searchIndex, keyword, component);
          }
        });
      }

      // Index by metadata tags
      if (component.metadata && component.metadata.tags) {
        const tags = component.metadata.tags as string[];
        tags.forEach(tag => {
          this.addToSearchIndex(searchIndex, tag.toLowerCase(), component);
        });
      }
    });

    return searchIndex;
  }

  /**
   * Add component to search index
   */
  private addToSearchIndex(
    index: Map<string, Component[]>,
    key: string,
    component: Component
  ): void {
    if (!index.has(key)) {
      index.set(key, []);
    }
    
    const components = index.get(key)!;
    if (!components.find(c => c.id === component.id)) {
      components.push(component);
    }
  }

  /**
   * Search components using the index
   */
  public searchComponents(
    query: string,
    searchIndex: Map<string, Component[]>
  ): Component[] {
    const results = new Set<Component>();
    const queryTerms = query.toLowerCase().split(/\s+/);

    queryTerms.forEach(term => {
      // Exact matches
      if (searchIndex.has(term)) {
        searchIndex.get(term)!.forEach(component => results.add(component));
      }

      // Partial matches
      for (const [key, components] of searchIndex.entries()) {
        if (key.includes(term) || term.includes(key)) {
          components.forEach(component => results.add(component));
        }
      }
    });

    return Array.from(results);
  }

  /**
   * Generate group ID from name
   */
  private generateGroupId(name: string): string {
    return name
      .toLowerCase()
      .replace(/[^a-z0-9\s]/g, '')
      .replace(/\s+/g, '-')
      .trim();
  }

  /**
   * Validate group structure
   */
  public validateGroup(group: ComponentGroup): { valid: boolean; errors: string[] } {
    const errors: string[] = [];

    if (!group.id || group.id.trim() === '') {
      errors.push('Group ID is required');
    }

    if (!group.name || group.name.trim() === '') {
      errors.push('Group name is required');
    }

    if (group.components && !Array.isArray(group.components)) {
      errors.push('Components must be an array');
    }

    if (group.tags && !Array.isArray(group.tags)) {
      errors.push('Tags must be an array');
    }

    if (group.sortOrder !== undefined && typeof group.sortOrder !== 'number') {
      errors.push('Sort order must be a number');
    }

    return {
      valid: errors.length === 0,
      errors
    };
  }
}

export default ComponentGroupingService;