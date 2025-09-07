# Component Library API Reference

## ComponentLibraryBridge Service

The `ComponentLibraryBridge` service provides the core integration between the Component Library System and GrapeJS page builder.

### Class: ComponentLibraryBridge

#### Constructor

```typescript
constructor(
  editor: Editor,
  options: ComponentLibraryBridgeOptions = {}
)
```

**Parameters:**
- `editor`: GrapeJS Editor instance
- `options`: Configuration options for the bridge

**Options:**
```typescript
interface ComponentLibraryBridgeOptions {
  apiEndpoint?: string;
  cacheEnabled?: boolean;
  debugMode?: boolean;
  themeIntegration?: boolean;
  responsiveBreakpoints?: ResponsiveBreakpoints;
}
```

#### Methods

##### registerComponent()

Registers a component with the GrapeJS editor and Component Library system.

```typescript
registerComponent(config: ComponentRegistrationConfig): Promise<void>
```

**Parameters:**
```typescript
interface ComponentRegistrationConfig {
  id: string;
  category: string;
  label: string;
  component: ComponentDefinition;
  metadata: ComponentMetadata;
  schema?: JSONSchema;
  traits?: TraitDefinition[];
}
```

**Example:**
```typescript
await bridge.registerComponent({
  id: 'custom-hero',
  category: 'Hero Components',
  label: 'Custom Hero Section',
  component: () => import('@/components/CustomHero.vue'),
  metadata: {
    blockId: 'custom-hero',
    icon: '<svg>...</svg>',
    responsive: true
  },
  traits: [
    {
      type: 'text',
      name: 'headline',
      label: 'Headline',
      changeProp: 1
    }
  ]
});
```

##### convertToGrapeJSBlock()

Converts a Component Library component to GrapeJS block format.

```typescript
convertToGrapeJSBlock(component: Component): GrapeJSBlock
```

**Parameters:**
- `component`: Component model instance

**Returns:**
```typescript
interface GrapeJSBlock {
  id: string;
  label: string;
  category: string;
  media: string;
  content: BlockContent;
  traits: TraitDefinition[];
}
```

**Example:**
```typescript
const component = await Component.find(1);
const block = bridge.convertToGrapeJSBlock(component);
editor.BlockManager.add(block.id, block);
```

##### serializeComponent()

Serializes a component instance for storage or transmission.

```typescript
serializeComponent(
  componentInstance: ComponentInstance,
  options: SerializationOptions = {}
): SerializedComponent
```

**Parameters:**
```typescript
interface SerializationOptions {
  includeMetadata?: boolean;
  compressData?: boolean;
  validateSchema?: boolean;
}
```

**Returns:**
```typescript
interface SerializedComponent {
  id: string;
  type: string;
  config: ComponentConfig;
  metadata?: ComponentMetadata;
  version: string;
  checksum: string;
}
```

##### deserializeComponent()

Deserializes component data back to a component instance.

```typescript
deserializeComponent(
  data: SerializedComponent,
  options: DeserializationOptions = {}
): Promise<ComponentInstance>
```

**Example:**
```typescript
const serialized = bridge.serializeComponent(instance);
const restored = await bridge.deserializeComponent(serialized);
```

##### validateConfiguration()

Validates component configuration against its schema.

```typescript
validateConfiguration(
  componentType: string,
  config: ComponentConfig
): ValidationResult
```

**Returns:**
```typescript
interface ValidationResult {
  valid: boolean;
  errors: ValidationError[];
  warnings: ValidationWarning[];
}
```

##### generatePreview()

Generates a preview image or HTML for a component.

```typescript
generatePreview(
  component: Component,
  options: PreviewOptions = {}
): Promise<PreviewResult>
```

**Parameters:**
```typescript
interface PreviewOptions {
  format: 'image' | 'html' | 'svg';
  width?: number;
  height?: number;
  theme?: Theme;
  sampleData?: boolean;
}
```

##### syncWithGrapeJS()

Synchronizes component changes between Component Library and GrapeJS.

```typescript
syncWithGrapeJS(
  componentId: string,
  changes: ComponentChanges
): Promise<void>
```

**Example:**
```typescript
await bridge.syncWithGrapeJS('hero-1', {
  config: { headline: 'New Headline' },
  traits: { backgroundColor: '#ff0000' }
});
```

##### getComponentCategories()

Retrieves all available component categories.

```typescript
getComponentCategories(): Promise<ComponentCategory[]>
```

**Returns:**
```typescript
interface ComponentCategory {
  id: string;
  label: string;
  icon: string;
  components: Component[];
  order: number;
}
```

##### searchComponents()

Searches components by various criteria.

```typescript
searchComponents(
  query: ComponentSearchQuery
): Promise<ComponentSearchResult[]>
```

**Parameters:**
```typescript
interface ComponentSearchQuery {
  text?: string;
  category?: string;
  tags?: string[];
  filters?: ComponentFilters;
  pagination?: PaginationOptions;
}
```

## Component Management API

### ComponentService

Core service for component CRUD operations and business logic.

#### Methods

##### create()

Creates a new component with validation and tenant scoping.

```typescript
create(data: CreateComponentData): Promise<Component>
```

**Parameters:**
```typescript
interface CreateComponentData {
  name: string;
  category: ComponentCategory;
  type: string;
  config: ComponentConfig;
  metadata?: ComponentMetadata;
  tenantId: string;
}
```

##### update()

Updates an existing component with validation.

```typescript
update(
  id: string,
  data: UpdateComponentData
): Promise<Component>
```

##### duplicate()

Creates a copy of an existing component.

```typescript
duplicate(
  id: string,
  options: DuplicationOptions = {}
): Promise<Component>
```

**Parameters:**
```typescript
interface DuplicationOptions {
  newName?: string;
  preserveMetadata?: boolean;
  updateVersion?: boolean;
}
```

##### activate() / deactivate()

Controls component availability in the library.

```typescript
activate(id: string): Promise<void>
deactivate(id: string): Promise<void>
```

##### getByCategory()

Retrieves components filtered by category.

```typescript
getByCategory(
  category: string,
  options: FilterOptions = {}
): Promise<Component[]>
```

##### validateConfig()

Validates component configuration against schema.

```typescript
validateConfig(
  componentType: string,
  config: ComponentConfig
): ValidationResult
```

### ComponentThemeService

Service for managing component themes and styling.

#### Methods

##### applyTheme()

Applies a theme to one or more components.

```typescript
applyTheme(
  themeId: string,
  componentIds: string[]
): Promise<void>
```

##### createTheme()

Creates a new theme with validation.

```typescript
createTheme(data: CreateThemeData): Promise<ComponentTheme>
```

**Parameters:**
```typescript
interface CreateThemeData {
  name: string;
  config: ThemeConfig;
  isDefault?: boolean;
  tenantId: string;
}
```

##### inheritTheme()

Creates a theme that inherits from another theme.

```typescript
inheritTheme(
  parentThemeId: string,
  overrides: Partial<ThemeConfig>
): Promise<ComponentTheme>
```

##### validateTheme()

Validates theme configuration for compatibility.

```typescript
validateTheme(config: ThemeConfig): ThemeValidationResult
```

**Returns:**
```typescript
interface ThemeValidationResult {
  valid: boolean;
  errors: ThemeValidationError[];
  compatibility: ComponentCompatibility[];
}
```

##### generateCSS()

Generates CSS variables and classes from theme configuration.

```typescript
generateCSS(theme: ComponentTheme): Promise<string>
```

### ComponentAnalyticsService

Service for tracking component performance and usage.

#### Methods

##### trackEvent()

Records a component interaction event.

```typescript
trackEvent(event: ComponentEvent): Promise<void>
```

**Parameters:**
```typescript
interface ComponentEvent {
  componentId: string;
  eventType: 'view' | 'click' | 'conversion' | 'form_submit';
  userId?: string;
  sessionId: string;
  data?: Record<string, any>;
}
```

##### getMetrics()

Retrieves analytics metrics for components.

```typescript
getMetrics(
  query: MetricsQuery
): Promise<ComponentMetrics>
```

**Parameters:**
```typescript
interface MetricsQuery {
  componentIds?: string[];
  dateRange: DateRange;
  metrics: MetricType[];
  groupBy?: 'component' | 'date' | 'variant';
}
```

##### createABTest()

Sets up A/B testing for component variants.

```typescript
createABTest(config: ABTestConfig): Promise<ABTest>
```

**Parameters:**
```typescript
interface ABTestConfig {
  name: string;
  componentId: string;
  variants: ComponentVariant[];
  trafficSplit: number[];
  duration: number;
  successMetric: string;
}
```

## REST API Endpoints

### Components

#### GET /api/components

Retrieves paginated list of components.

**Query Parameters:**
- `category` (string): Filter by category
- `search` (string): Search by name or description
- `active` (boolean): Filter by active status
- `page` (number): Page number for pagination
- `per_page` (number): Items per page (max 100)

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Hero Component",
      "category": "hero",
      "type": "individual",
      "config": {...},
      "metadata": {...},
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 20
  }
}
```

#### POST /api/components

Creates a new component.

**Request Body:**
```json
{
  "name": "Custom Hero",
  "category": "hero",
  "type": "custom",
  "config": {
    "headline": "Welcome",
    "backgroundColor": "#ffffff"
  },
  "metadata": {
    "tags": ["custom", "hero"],
    "description": "Custom hero component"
  }
}
```

#### GET /api/components/{id}

Retrieves a specific component.

#### PUT /api/components/{id}

Updates an existing component.

#### DELETE /api/components/{id}

Soft deletes a component.

#### POST /api/components/{id}/duplicate

Creates a duplicate of the component.

#### POST /api/components/{id}/activate

Activates the component.

#### POST /api/components/{id}/deactivate

Deactivates the component.

### Component Themes

#### GET /api/component-themes

Retrieves available themes.

#### POST /api/component-themes

Creates a new theme.

#### PUT /api/component-themes/{id}

Updates an existing theme.

#### POST /api/component-themes/{id}/apply

Applies theme to specified components.

**Request Body:**
```json
{
  "component_ids": ["uuid1", "uuid2", "uuid3"]
}
```

### Component Analytics

#### POST /api/component-analytics/events

Records component interaction events.

**Request Body:**
```json
{
  "component_id": "uuid",
  "event_type": "click",
  "session_id": "session-uuid",
  "data": {
    "button": "cta-primary",
    "position": "hero"
  }
}
```

#### GET /api/component-analytics/metrics

Retrieves analytics metrics.

**Query Parameters:**
- `component_ids[]` (array): Component IDs to include
- `start_date` (date): Start of date range
- `end_date` (date): End of date range
- `metrics[]` (array): Metrics to include (views, clicks, conversions)

## WebSocket Events

### Real-time Component Updates

#### component.updated

Fired when a component is updated.

```typescript
interface ComponentUpdatedEvent {
  componentId: string;
  changes: ComponentChanges;
  userId: string;
  timestamp: string;
}
```

#### theme.applied

Fired when a theme is applied to components.

```typescript
interface ThemeAppliedEvent {
  themeId: string;
  componentIds: string[];
  userId: string;
  timestamp: string;
}
```

#### analytics.event

Fired when analytics events are recorded.

```typescript
interface AnalyticsEvent {
  componentId: string;
  eventType: string;
  count: number;
  timestamp: string;
}
```

## Error Handling

### Error Response Format

All API endpoints return errors in a consistent format:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "name": ["The name field is required."],
      "config.backgroundColor": ["Invalid color format."]
    }
  }
}
```

### Common Error Codes

- `VALIDATION_ERROR`: Request validation failed
- `NOT_FOUND`: Resource not found
- `UNAUTHORIZED`: Authentication required
- `FORBIDDEN`: Insufficient permissions
- `TENANT_MISMATCH`: Resource belongs to different tenant
- `COMPONENT_INACTIVE`: Component is not active
- `THEME_INCOMPATIBLE`: Theme not compatible with component
- `SCHEMA_VALIDATION_FAILED`: Component config doesn't match schema

### Error Handling Best Practices

```typescript
try {
  const component = await ComponentService.create(data);
} catch (error) {
  if (error instanceof ValidationError) {
    // Handle validation errors
    console.error('Validation failed:', error.details);
  } else if (error instanceof NotFoundError) {
    // Handle not found errors
    console.error('Resource not found:', error.message);
  } else {
    // Handle unexpected errors
    console.error('Unexpected error:', error);
  }
}
```