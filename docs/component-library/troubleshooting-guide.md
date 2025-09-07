# Component Library Troubleshooting Guide

## Overview

This comprehensive troubleshooting guide helps developers and users resolve common issues with the Component Library System and its GrapeJS integration. Issues are organized by category with step-by-step solutions and prevention strategies.

## Quick Diagnostic Checklist

Before diving into specific issues, run through this quick checklist:

- [ ] Clear browser cache and refresh page
- [ ] Check browser console for JavaScript errors
- [ ] Verify network connectivity and API responses
- [ ] Confirm user permissions and authentication
- [ ] Test in different browser or incognito mode
- [ ] Check system status and recent deployments

## Component Integration Issues

### Issue: Component Not Appearing in Block Manager

**Symptoms:**
- Component is registered but doesn't show in GrapeJS Block Manager
- Component appears but has no preview image
- Component category is missing or incorrect

**Diagnostic Steps:**

1. **Check Component Registration**
   ```javascript
   // Open browser console and check if component is registered
   console.log(editor.BlockManager.getAll().models);
   
   // Look for your component ID in the list
   const myComponent = editor.BlockManager.get('my-component-id');
   console.log(myComponent);
   ```

2. **Verify Category Exists**
   ```javascript
   // Check available categories
   console.log(editor.BlockManager.getCategories().models);
   
   // Verify your category is present
   const myCategory = editor.BlockManager.getCategories().where({id: 'my-category'});
   console.log(myCategory);
   ```

3. **Inspect Component Metadata**
   ```php
   // In Laravel Tinker or controller
   $component = Component::find($componentId);
   dd($component->getGrapeJSMetadata());
   ```

**Common Causes and Solutions:**

| Cause | Solution |
|-------|----------|
| Missing `blockId` in metadata | Add unique `blockId` to component metadata |
| Category not defined | Create category before registering component |
| Invalid SVG icon | Provide valid SVG string or image URL |
| Component not active | Set `is_active` to true in database |
| Tenant isolation issue | Verify component belongs to current tenant |

**Code Fix Examples:**

```php
// ❌ Incorrect - Missing required metadata
public function getGrapeJSMetadata(): array
{
    return [
        'label' => $this->name
        // Missing blockId, category, etc.
    ];
}

// ✅ Correct - Complete metadata
public function getGrapeJSMetadata(): array
{
    return [
        'blockId' => "component-{$this->type}-{$this->id}",
        'category' => $this->category,
        'label' => $this->name,
        'icon' => $this->getIconSvg(),
        'traits' => $this->getTraitDefinitions(),
        'responsive' => true
    ];
}
```

```javascript
// ❌ Incorrect - Category doesn't exist
editor.BlockManager.add('my-block', {
    category: 'Non-existent Category'
});

// ✅ Correct - Create category first
editor.BlockManager.getCategories().add({
    id: 'custom-components',
    label: 'Custom Components',
    open: true
});

editor.BlockManager.add('my-block', {
    category: 'custom-components'
});
```

### Issue: Component Configuration Not Updating

**Symptoms:**
- Changes in trait panel don't reflect in component
- Component shows default values instead of configured values
- Configuration changes are lost after page reload

**Diagnostic Steps:**

1. **Check Trait Configuration**
   ```javascript
   // Get selected component
   const selected = editor.getSelected();
   
   // Check traits
   console.log('Traits:', selected.getTraits().models);
   
   // Check attributes
   console.log('Attributes:', selected.getAttributes());
   
   // Check if changeProp is set
   selected.getTraits().each(trait => {
       console.log(`${trait.get('name')}: changeProp=${trait.get('changeProp')}`);
   });
   ```

2. **Verify Component Model Updates**
   ```javascript
   // Listen for attribute changes
   selected.on('change:attributes', () => {
       console.log('Attributes changed:', selected.getAttributes());
   });
   
   // Check if component triggers updates
   selected.trigger('change:attributes');
   ```

**Solutions:**

1. **Add `changeProp` to Traits**
   ```javascript
   // ❌ Incorrect - Missing changeProp
   {
       type: 'text',
       name: 'title',
       label: 'Title'
   }
   
   // ✅ Correct - Include changeProp
   {
       type: 'text',
       name: 'title',
       label: 'Title',
       changeProp: 1  // Enables live updates
   }
   ```

2. **Implement Proper Change Handling**
   ```javascript
   // Component model
   model: {
       defaults: {
           traits: [/* trait definitions */]
       },
       
       init() {
           this.on('change:attributes', this.handleAttributeChange);
       },
       
       handleAttributeChange() {
           const attributes = this.getAttributes();
           // Update component configuration
           this.set('config', attributes);
           this.trigger('component:update');
       }
   }
   ```

3. **Fix Vue Component Reactivity**
   ```vue
   <!-- ❌ Incorrect - Static props -->
   <template>
     <h1>{{ staticTitle }}</h1>
   </template>
   
   <!-- ✅ Correct - Reactive props -->
   <template>
     <h1>{{ config.title || 'Default Title' }}</h1>
   </template>
   
   <script setup>
   const props = defineProps<{
     config: ComponentConfig
   }>()
   
   // Watch for config changes
   watch(() => props.config, (newConfig) => {
     // Handle configuration updates
   }, { deep: true })
   </script>
   ```

### Issue: Component Rendering Problems

**Symptoms:**
- Component appears blank or shows error message
- Component styling is incorrect or missing
- Component doesn't respond to interactions

**Diagnostic Steps:**

1. **Check Vue Component Errors**
   ```javascript
   // Check for Vue errors in console
   window.addEventListener('error', (e) => {
       console.error('Global error:', e);
   });
   
   // Check Vue warnings
   Vue.config.warnHandler = (msg, vm, trace) => {
       console.warn('Vue warning:', msg, trace);
   };
   ```

2. **Verify Component Registration**
   ```javascript
   // Check if Vue component is registered
   console.log('Registered components:', app._context.components);
   
   // Test component loading
   const ComponentClass = await import('@/components/MyComponent.vue');
   console.log('Component loaded:', ComponentClass);
   ```

**Solutions:**

1. **Fix Component Import Issues**
   ```javascript
   // ❌ Incorrect - Wrong import path
   const component = () => import('@/components/Wrong/Path.vue');
   
   // ✅ Correct - Proper import path
   const component = () => import('@/components/ComponentLibrary/Hero/HeroComponent.vue');
   ```

2. **Handle Component Loading Errors**
   ```javascript
   // Add error handling for component loading
   async function loadComponent(componentType) {
       try {
           const component = await import(`@/components/${componentType}.vue`);
           return component.default || component;
       } catch (error) {
           console.error(`Failed to load component ${componentType}:`, error);
           // Return fallback component
           return () => import('@/components/ComponentLibrary/ErrorComponent.vue');
       }
   }
   ```

3. **Fix Styling Issues**
   ```vue
   <!-- Ensure proper CSS scoping -->
   <style scoped>
   .component {
     /* Component-specific styles */
   }
   </style>
   
   <!-- Or use CSS modules -->
   <style module>
   .component {
     /* Modular styles */
   }
   </style>
   ```

## Theme Integration Issues

### Issue: Theme Not Applying to Components

**Symptoms:**
- Components don't inherit theme colors
- Theme changes don't reflect in components
- Inconsistent styling across components

**Diagnostic Steps:**

1. **Check Theme Application**
   ```php
   // In Laravel Tinker
   $theme = ComponentTheme::find($themeId);
   $components = Component::where('theme_id', $themeId)->get();
   
   foreach ($components as $component) {
       echo "Component: {$component->name}, Theme: {$component->theme_id}\n";
   }
   ```

2. **Verify CSS Variable Generation**
   ```javascript
   // Check if CSS variables are applied
   const rootStyles = getComputedStyle(document.documentElement);
   console.log('Primary color:', rootStyles.getPropertyValue('--color-primary-500'));
   
   // Check theme object in component
   console.log('Component theme:', this.theme);
   ```

**Solutions:**

1. **Ensure Theme Props Are Passed**
   ```vue
   <!-- ❌ Incorrect - No theme prop -->
   <ComponentLibrary :config="config" />
   
   <!-- ✅ Correct - Include theme -->
   <ComponentLibrary 
     :config="config" 
     :theme="currentTheme" 
   />
   ```

2. **Use Theme Variables in Components**
   ```vue
   <!-- ❌ Incorrect - Hardcoded colors -->
   <template>
     <div style="background-color: #blue; color: #white;">
       Content
     </div>
   </template>
   
   <!-- ✅ Correct - Theme variables -->
   <template>
     <div :style="{
       backgroundColor: theme?.colors?.primary?.[500] || 'var(--color-primary-500)',
       color: theme?.colors?.text?.primary || 'var(--color-text-primary)'
     }">
       Content
     </div>
   </template>
   ```

3. **Fix CSS Variable Generation**
   ```php
   // Ensure CSS variables are properly generated
   public function generateCSSVariables(ComponentTheme $theme): string
   {
       $variables = [];
       
       // Generate color variables
       foreach ($theme->config['colors'] as $colorName => $colorValue) {
           if (is_array($colorValue)) {
               foreach ($colorValue as $variant => $value) {
                   $variables["--color-{$colorName}-{$variant}"] = $value;
               }
           } else {
               $variables["--color-{$colorName}"] = $colorValue;
           }
       }
       
       // Convert to CSS
       $css = ':root {';
       foreach ($variables as $property => $value) {
           $css .= "\n  {$property}: {$value};";
       }
       $css .= "\n}";
       
       return $css;
   }
   ```

### Issue: Theme Validation Errors

**Symptoms:**
- Theme fails to save with validation errors
- Components show styling errors after theme application
- Color contrast warnings or accessibility issues

**Solutions:**

1. **Fix Color Format Issues**
   ```php
   // ❌ Incorrect - Invalid color format
   'primary' => 'blue'
   
   // ✅ Correct - Valid hex color
   'primary' => '#3b82f6'
   
   // ✅ Also correct - RGB color
   'primary' => 'rgb(59, 130, 246)'
   ```

2. **Ensure Required Colors Are Present**
   ```php
   // Check theme has all required colors
   public function validateThemeColors(array $colors): array
   {
       $required = [
           'primary.500',
           'text.primary',
           'background.primary'
       ];
       
       $missing = [];
       foreach ($required as $colorPath) {
           if (!$this->hasColor($colors, $colorPath)) {
               $missing[] = $colorPath;
           }
       }
       
       return $missing;
   }
   ```

3. **Fix Contrast Issues**
   ```php
   // Validate color contrast
   public function validateContrast(string $foreground, string $background): float
   {
       $fgLuminance = $this->calculateLuminance($foreground);
       $bgLuminance = $this->calculateLuminance($background);
       
       $lighter = max($fgLuminance, $bgLuminance);
       $darker = min($fgLuminance, $bgLuminance);
       
       return ($lighter + 0.05) / ($darker + 0.05);
   }
   ```

## Performance Issues

### Issue: Slow Component Loading

**Symptoms:**
- Components take long time to appear in Block Manager
- Page builder feels sluggish when adding components
- High memory usage in browser

**Diagnostic Steps:**

1. **Check Network Performance**
   ```javascript
   // Monitor API response times
   console.time('component-load');
   fetch('/api/components')
       .then(response => response.json())
       .then(data => {
           console.timeEnd('component-load');
           console.log('Components loaded:', data.length);
       });
   ```

2. **Profile Component Rendering**
   ```javascript
   // Use Performance API
   performance.mark('component-render-start');
   
   // Render component
   renderComponent();
   
   performance.mark('component-render-end');
   performance.measure('component-render', 'component-render-start', 'component-render-end');
   
   console.log(performance.getEntriesByType('measure'));
   ```

**Solutions:**

1. **Implement Component Lazy Loading**
   ```javascript
   // ✅ Lazy load components
   const componentRegistry = {
     'hero': () => import('@/components/Hero/HeroComponent.vue'),
     'form': () => import('@/components/Forms/FormComponent.vue')
   };
   
   async function loadComponent(type) {
     if (!componentRegistry[type]) {
       throw new Error(`Unknown component type: ${type}`);
     }
     
     return componentRegistry[type]();
   }
   ```

2. **Optimize Component Previews**
   ```javascript
   // Generate lightweight previews
   function generateComponentPreview(component) {
     return {
       id: component.id,
       name: component.name,
       thumbnail: generateThumbnail(component, { width: 150, height: 100 }),
       category: component.category
     };
   }
   ```

3. **Cache Component Metadata**
   ```php
   // Cache expensive operations
   public function getGrapeJSMetadata(): array
   {
       return Cache::remember(
           "component-metadata-{$this->id}",
           3600, // 1 hour
           fn() => $this->generateMetadata()
       );
   }
   ```

### Issue: Memory Leaks

**Symptoms:**
- Browser memory usage increases over time
- Page becomes unresponsive after extended use
- Components stop responding to interactions

**Solutions:**

1. **Proper Event Cleanup**
   ```vue
   <script setup>
   import { onUnmounted } from 'vue'
   
   let intervalId: number | null = null
   let eventListeners: Array<() => void> = []
   
   onMounted(() => {
     // Set up interval
     intervalId = setInterval(() => {
       // Do something
     }, 1000)
     
     // Add event listeners
     const cleanup = () => {
       document.removeEventListener('click', handleClick)
     }
     document.addEventListener('click', handleClick)
     eventListeners.push(cleanup)
   })
   
   onUnmounted(() => {
     // Clear interval
     if (intervalId) {
       clearInterval(intervalId)
     }
     
     // Remove event listeners
     eventListeners.forEach(cleanup => cleanup())
   })
   </script>
   ```

2. **Component Instance Management**
   ```javascript
   class ComponentManager {
     private instances = new Map();
     
     addComponent(id, instance) {
       this.instances.set(id, instance);
     }
     
     removeComponent(id) {
       const instance = this.instances.get(id);
       if (instance && typeof instance.destroy === 'function') {
         instance.destroy();
       }
       this.instances.delete(id);
     }
     
     cleanup() {
       this.instances.forEach((instance, id) => {
         this.removeComponent(id);
       });
     }
   }
   ```

## API and Backend Issues

### Issue: API Endpoints Not Responding

**Symptoms:**
- 500 Internal Server Error responses
- Timeout errors when loading components
- Authentication failures

**Diagnostic Steps:**

1. **Check Laravel Logs**
   ```bash
   # View recent errors
   tail -f storage/logs/laravel.log
   
   # Check for specific errors
   grep "ERROR" storage/logs/laravel.log | tail -20
   ```

2. **Test API Endpoints**
   ```bash
   # Test component endpoint
   curl -H "Authorization: Bearer YOUR_TOKEN" \
        -H "Accept: application/json" \
        http://your-domain.com/api/components
   
   # Check response status
   curl -I http://your-domain.com/api/components
   ```

**Solutions:**

1. **Fix Route Issues**
   ```php
   // Ensure routes are properly defined
   Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
       Route::apiResource('components', ComponentController::class);
       Route::post('components/{component}/duplicate', [ComponentController::class, 'duplicate']);
   });
   ```

2. **Handle Validation Errors**
   ```php
   // Proper error handling in controller
   public function store(ComponentRequest $request)
   {
       try {
           $component = $this->componentService->create($request->validated());
           return new ComponentResource($component);
       } catch (ValidationException $e) {
           return response()->json([
               'error' => 'Validation failed',
               'details' => $e->errors()
           ], 422);
       } catch (\Exception $e) {
           Log::error('Component creation failed', [
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString()
           ]);
           
           return response()->json([
               'error' => 'Internal server error'
           ], 500);
       }
   }
   ```

3. **Fix Authentication Issues**
   ```php
   // Ensure proper middleware
   public function __construct()
   {
       $this->middleware(['auth:sanctum', 'verified']);
   }
   
   // Check user permissions
   public function index(Request $request)
   {
       $this->authorize('viewAny', Component::class);
       
       return ComponentResource::collection(
           Component::forTenant($request->user()->tenant_id)->paginate()
       );
   }
   ```

### Issue: Database Query Performance

**Symptoms:**
- Slow component loading
- Database timeout errors
- High CPU usage on database server

**Solutions:**

1. **Add Database Indexes**
   ```php
   // Migration to add indexes
   Schema::table('components', function (Blueprint $table) {
       $table->index(['tenant_id', 'category']);
       $table->index(['tenant_id', 'is_active']);
       $table->index('created_at');
   });
   ```

2. **Optimize Queries with Eager Loading**
   ```php
   // ❌ N+1 query problem
   $components = Component::all();
   foreach ($components as $component) {
       echo $component->theme->name; // Triggers additional query
   }
   
   // ✅ Eager loading
   $components = Component::with('theme')->get();
   foreach ($components as $component) {
       echo $component->theme->name; // No additional query
   }
   ```

3. **Use Query Scopes**
   ```php
   // Component model
   public function scopeActive($query)
   {
       return $query->where('is_active', true);
   }
   
   public function scopeForCategory($query, $category)
   {
       return $query->where('category', $category);
   }
   
   // Usage
   $heroComponents = Component::active()
       ->forCategory('hero')
       ->with('theme')
       ->get();
   ```

## Browser Compatibility Issues

### Issue: Components Not Working in Specific Browsers

**Symptoms:**
- Components work in Chrome but not Safari/Firefox
- JavaScript errors in older browsers
- CSS styling differences between browsers

**Solutions:**

1. **Add Browser Polyfills**
   ```javascript
   // vite.config.ts
   export default defineConfig({
     build: {
       target: ['es2015', 'safari11'],
       polyfillModulePreload: true
     }
   });
   ```

2. **Use CSS Autoprefixer**
   ```javascript
   // postcss.config.js
   module.exports = {
     plugins: {
       autoprefixer: {
         overrideBrowserslist: [
           '> 1%',
           'last 2 versions',
           'not dead'
         ]
       }
     }
   };
   ```

3. **Feature Detection**
   ```javascript
   // Check for required features
   if (!window.IntersectionObserver) {
     // Load polyfill
     await import('intersection-observer');
   }
   
   if (!CSS.supports('display', 'grid')) {
     // Fallback for older browsers
     document.body.classList.add('no-grid-support');
   }
   ```

## Debugging Tools and Techniques

### Enable Debug Mode

```javascript
// Enable comprehensive debugging
window.ComponentLibraryDebug = true;

// GrapeJS debug mode
const editor = grapesjs.init({
  container: '#gjs',
  // Enable debug logging
  log: ['info', 'warning', 'error'],
  // Show debug panel
  showDevices: true,
  showOffsets: true
});

// Component Library debug logging
ComponentLibraryBridge.enableDebugMode();
```

### Console Debugging Commands

```javascript
// Component inspection
function debugComponent(componentId) {
  const component = editor.DomComponents.getById(componentId);
  console.group(`Component Debug: ${componentId}`);
  console.log('Model:', component);
  console.log('Attributes:', component.getAttributes());
  console.log('Traits:', component.getTraits().models);
  console.log('View:', component.view);
  console.groupEnd();
}

// Block Manager inspection
function debugBlockManager() {
  const blocks = editor.BlockManager.getAll();
  console.table(blocks.models.map(block => ({
    id: block.get('id'),
    label: block.get('label'),
    category: block.get('category')
  })));
}

// Theme debugging
function debugTheme() {
  const theme = ComponentLibraryBridge.getCurrentTheme();
  console.log('Current theme:', theme);
  
  // Check CSS variables
  const root = document.documentElement;
  const styles = getComputedStyle(root);
  const cssVars = {};
  
  for (let i = 0; i < styles.length; i++) {
    const prop = styles[i];
    if (prop.startsWith('--color-')) {
      cssVars[prop] = styles.getPropertyValue(prop);
    }
  }
  
  console.log('CSS Variables:', cssVars);
}
```

### Error Logging and Monitoring

```javascript
// Enhanced error logging
class ComponentErrorLogger {
  static logError(error, context = {}) {
    const errorData = {
      message: error.message,
      stack: error.stack,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      url: window.location.href,
      context
    };
    
    // Log to console
    console.error('Component Error:', errorData);
    
    // Send to monitoring service
    if (window.ComponentLibraryConfig?.errorReporting) {
      fetch('/api/errors', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(errorData)
      });
    }
  }
}

// Global error handler
window.addEventListener('error', (event) => {
  ComponentErrorLogger.logError(event.error, {
    type: 'global',
    filename: event.filename,
    lineno: event.lineno,
    colno: event.colno
  });
});

// Promise rejection handler
window.addEventListener('unhandledrejection', (event) => {
  ComponentErrorLogger.logError(event.reason, {
    type: 'promise_rejection'
  });
});
```

## Getting Additional Help

### When to Contact Support

Contact technical support when:

- Issues persist after following troubleshooting steps
- System-wide problems affecting multiple users
- Data corruption or loss concerns
- Security-related issues
- Performance problems affecting production

### Information to Provide

When contacting support, include:

1. **Error Details**
   - Exact error messages
   - Browser console logs
   - Network request/response details
   - Steps to reproduce the issue

2. **Environment Information**
   - Browser version and operating system
   - User role and permissions
   - Tenant/organization details
   - Recent changes or deployments

3. **Component Information**
   - Component type and configuration
   - Theme being used
   - Page builder state
   - Any custom modifications

### Self-Help Resources

- **Documentation**: Complete API and user documentation
- **Video Tutorials**: Step-by-step video guides
- **Community Forum**: User discussions and solutions
- **Knowledge Base**: Searchable solution database
- **Status Page**: System status and maintenance updates

This troubleshooting guide provides comprehensive solutions for the most common issues encountered with the Component Library System and GrapeJS integration. Regular updates ensure it remains current with new features and known issues.