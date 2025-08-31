# Theme Integration Documentation

## Overview

The Component Library System includes comprehensive theme integration that ensures brand consistency across all components while maintaining compatibility with the GrapeJS page builder. This documentation covers theme validation, compatibility checking, and implementation guidelines.

## Theme System Architecture

### Theme Structure

Themes in the Component Library System follow a hierarchical structure that supports inheritance and customization:

```typescript
interface ComponentTheme {
  id: string;
  tenantId: string;
  name: string;
  slug: string;
  config: ThemeConfig;
  isDefault: boolean;
  parentThemeId?: string;
  createdAt: Date;
  updatedAt: Date;
}

interface ThemeConfig {
  colors: ColorPalette;
  typography: TypographyConfig;
  spacing: SpacingConfig;
  borders: BorderConfig;
  shadows: ShadowConfig;
  animations: AnimationConfig;
  breakpoints: BreakpointConfig;
}
```

### Color Palette System

The color system supports both semantic and brand-specific color definitions:

```typescript
interface ColorPalette {
  // Brand Colors
  primary: ColorVariants;
  secondary: ColorVariants;
  accent: ColorVariants;
  
  // Semantic Colors
  success: ColorVariants;
  warning: ColorVariants;
  error: ColorVariants;
  info: ColorVariants;
  
  // Neutral Colors
  neutral: ColorVariants;
  
  // Text Colors
  text: {
    primary: string;
    secondary: string;
    muted: string;
    inverse: string;
  };
  
  // Background Colors
  background: {
    primary: string;
    secondary: string;
    tertiary: string;
    overlay: string;
  };
}

interface ColorVariants {
  50: string;   // Lightest
  100: string;
  200: string;
  300: string;
  400: string;
  500: string;  // Base color
  600: string;
  700: string;
  800: string;
  900: string;  // Darkest
}
```

### Typography Configuration

Typography settings ensure consistent text styling across components:

```typescript
interface TypographyConfig {
  fontFamilies: {
    primary: FontFamily;
    secondary: FontFamily;
    monospace: FontFamily;
  };
  
  fontSizes: {
    xs: string;    // 0.75rem
    sm: string;    // 0.875rem
    base: string;  // 1rem
    lg: string;    // 1.125rem
    xl: string;    // 1.25rem
    '2xl': string; // 1.5rem
    '3xl': string; // 1.875rem
    '4xl': string; // 2.25rem
    '5xl': string; // 3rem
    '6xl': string; // 3.75rem
  };
  
  fontWeights: {
    thin: number;      // 100
    light: number;     // 300
    normal: number;    // 400
    medium: number;    // 500
    semibold: number;  // 600
    bold: number;      // 700
    extrabold: number; // 800
    black: number;     // 900
  };
  
  lineHeights: {
    none: number;    // 1
    tight: number;   // 1.25
    snug: number;    // 1.375
    normal: number;  // 1.5
    relaxed: number; // 1.625
    loose: number;   // 2
  };
  
  letterSpacing: {
    tighter: string; // -0.05em
    tight: string;   // -0.025em
    normal: string;  // 0em
    wide: string;    // 0.025em
    wider: string;   // 0.05em
    widest: string;  // 0.1em
  };
}

interface FontFamily {
  name: string;
  fallbacks: string[];
  weights: number[];
  styles: ('normal' | 'italic')[];
  source: 'google' | 'system' | 'custom';
  url?: string;
}
```

### Spacing and Layout

Consistent spacing ensures visual harmony across components:

```typescript
interface SpacingConfig {
  scale: {
    0: string;    // 0px
    px: string;   // 1px
    0.5: string;  // 0.125rem
    1: string;    // 0.25rem
    1.5: string;  // 0.375rem
    2: string;    // 0.5rem
    2.5: string;  // 0.625rem
    3: string;    // 0.75rem
    3.5: string;  // 0.875rem
    4: string;    // 1rem
    5: string;    // 1.25rem
    6: string;    // 1.5rem
    7: string;    // 1.75rem
    8: string;    // 2rem
    9: string;    // 2.25rem
    10: string;   // 2.5rem
    11: string;   // 2.75rem
    12: string;   // 3rem
    14: string;   // 3.5rem
    16: string;   // 4rem
    20: string;   // 5rem
    24: string;   // 6rem
    28: string;   // 7rem
    32: string;   // 8rem
    36: string;   // 9rem
    40: string;   // 10rem
    44: string;   // 11rem
    48: string;   // 12rem
    52: string;   // 13rem
    56: string;   // 14rem
    60: string;   // 15rem
    64: string;   // 16rem
    72: string;   // 18rem
    80: string;   // 20rem
    96: string;   // 24rem
  };
  
  containers: {
    sm: string;   // 640px
    md: string;   // 768px
    lg: string;   // 1024px
    xl: string;   // 1280px
    '2xl': string; // 1536px
  };
}
```

## Theme Validation System

### Validation Rules

The theme validation system ensures themes meet quality and compatibility standards:

#### Color Validation

```typescript
class ColorValidator {
  validateColorPalette(palette: ColorPalette): ValidationResult {
    const errors: ValidationError[] = [];
    
    // Validate color format (hex, rgb, hsl)
    for (const [colorName, colorValue] of Object.entries(palette)) {
      if (typeof colorValue === 'string') {
        if (!this.isValidColor(colorValue)) {
          errors.push({
            field: `colors.${colorName}`,
            message: `Invalid color format: ${colorValue}`,
            code: 'INVALID_COLOR_FORMAT'
          });
        }
      } else if (typeof colorValue === 'object') {
        // Validate color variants
        for (const [variant, value] of Object.entries(colorValue)) {
          if (!this.isValidColor(value)) {
            errors.push({
              field: `colors.${colorName}.${variant}`,
              message: `Invalid color format: ${value}`,
              code: 'INVALID_COLOR_FORMAT'
            });
          }
        }
      }
    }
    
    // Validate contrast ratios
    const contrastErrors = this.validateContrast(palette);
    errors.push(...contrastErrors);
    
    return {
      valid: errors.length === 0,
      errors,
      warnings: this.generateColorWarnings(palette)
    };
  }
  
  private validateContrast(palette: ColorPalette): ValidationError[] {
    const errors: ValidationError[] = [];
    
    // Check text on background contrast
    const textPrimary = palette.text.primary;
    const backgroundPrimary = palette.background.primary;
    
    const contrastRatio = this.calculateContrastRatio(textPrimary, backgroundPrimary);
    
    if (contrastRatio < 4.5) {
      errors.push({
        field: 'colors.contrast',
        message: `Insufficient contrast ratio: ${contrastRatio.toFixed(2)} (minimum 4.5)`,
        code: 'INSUFFICIENT_CONTRAST'
      });
    }
    
    return errors;
  }
  
  private isValidColor(color: string): boolean {
    // Validate hex colors
    if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color)) {
      return true;
    }
    
    // Validate rgb/rgba colors
    if (/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+)?\s*\)$/.test(color)) {
      return true;
    }
    
    // Validate hsl/hsla colors
    if (/^hsla?\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*(,\s*[\d.]+)?\s*\)$/.test(color)) {
      return true;
    }
    
    return false;
  }
}
```

#### Typography Validation

```typescript
class TypographyValidator {
  validateTypography(typography: TypographyConfig): ValidationResult {
    const errors: ValidationError[] = [];
    const warnings: ValidationWarning[] = [];
    
    // Validate font families
    for (const [name, fontFamily] of Object.entries(typography.fontFamilies)) {
      if (!fontFamily.name || fontFamily.name.trim() === '') {
        errors.push({
          field: `typography.fontFamilies.${name}.name`,
          message: 'Font family name is required',
          code: 'MISSING_FONT_NAME'
        });
      }
      
      if (fontFamily.fallbacks.length === 0) {
        warnings.push({
          field: `typography.fontFamilies.${name}.fallbacks`,
          message: 'No fallback fonts specified',
          code: 'NO_FONT_FALLBACKS'
        });
      }
      
      // Validate Google Fonts
      if (fontFamily.source === 'google') {
        if (!this.isValidGoogleFont(fontFamily.name)) {
          errors.push({
            field: `typography.fontFamilies.${name}.name`,
            message: `Invalid Google Font: ${fontFamily.name}`,
            code: 'INVALID_GOOGLE_FONT'
          });
        }
      }
    }
    
    // Validate font size scale
    const fontSizeErrors = this.validateFontSizeScale(typography.fontSizes);
    errors.push(...fontSizeErrors);
    
    return {
      valid: errors.length === 0,
      errors,
      warnings
    };
  }
  
  private validateFontSizeScale(fontSizes: Record<string, string>): ValidationError[] {
    const errors: ValidationError[] = [];
    const requiredSizes = ['xs', 'sm', 'base', 'lg', 'xl', '2xl'];
    
    for (const size of requiredSizes) {
      if (!fontSizes[size]) {
        errors.push({
          field: `typography.fontSizes.${size}`,
          message: `Required font size missing: ${size}`,
          code: 'MISSING_FONT_SIZE'
        });
      }
    }
    
    return errors;
  }
}
```

### Component Compatibility Checking

The system validates theme compatibility with all component types:

```typescript
class ComponentCompatibilityChecker {
  checkThemeCompatibility(
    theme: ComponentTheme,
    components: Component[]
  ): CompatibilityResult {
    const results: ComponentCompatibilityResult[] = [];
    
    for (const component of components) {
      const result = this.checkComponentCompatibility(theme, component);
      results.push(result);
    }
    
    return {
      overallCompatible: results.every(r => r.compatible),
      results,
      summary: this.generateCompatibilitySummary(results)
    };
  }
  
  private checkComponentCompatibility(
    theme: ComponentTheme,
    component: Component
  ): ComponentCompatibilityResult {
    const issues: CompatibilityIssue[] = [];
    
    // Check color requirements
    const colorIssues = this.checkColorCompatibility(theme, component);
    issues.push(...colorIssues);
    
    // Check typography requirements
    const typographyIssues = this.checkTypographyCompatibility(theme, component);
    issues.push(...typographyIssues);
    
    // Check spacing requirements
    const spacingIssues = this.checkSpacingCompatibility(theme, component);
    issues.push(...spacingIssues);
    
    return {
      componentId: component.id,
      componentName: component.name,
      compatible: issues.length === 0,
      issues
    };
  }
  
  private checkColorCompatibility(
    theme: ComponentTheme,
    component: Component
  ): CompatibilityIssue[] {
    const issues: CompatibilityIssue[] = [];
    const requiredColors = component.getRequiredColors();
    
    for (const colorPath of requiredColors) {
      if (!this.hasColor(theme.config.colors, colorPath)) {
        issues.push({
          type: 'missing_color',
          severity: 'error',
          message: `Required color missing: ${colorPath}`,
          field: `colors.${colorPath}`
        });
      }
    }
    
    return issues;
  }
}
```

## Theme Application Process

### Applying Themes to Components

The theme application process ensures consistent styling across all components:

```typescript
class ThemeApplicationService {
  async applyTheme(
    themeId: string,
    componentIds: string[]
  ): Promise<ThemeApplicationResult> {
    const theme = await ComponentTheme.findOrFail(themeId);
    const components = await Component.whereIn('id', componentIds).get();
    
    // Validate compatibility
    const compatibility = this.compatibilityChecker.checkThemeCompatibility(
      theme,
      components
    );
    
    if (!compatibility.overallCompatible) {
      throw new ThemeCompatibilityError(
        'Theme is not compatible with all selected components',
        compatibility.results
      );
    }
    
    // Apply theme to each component
    const results: ComponentThemeApplicationResult[] = [];
    
    for (const component of components) {
      const result = await this.applyThemeToComponent(theme, component);
      results.push(result);
    }
    
    // Generate CSS variables
    const cssVariables = this.generateCSSVariables(theme);
    
    // Update GrapeJS style manager
    await this.updateGrapeJSStyles(theme, cssVariables);
    
    return {
      themeId,
      appliedComponents: results,
      cssVariables,
      success: true
    };
  }
  
  private async applyThemeToComponent(
    theme: ComponentTheme,
    component: Component
  ): Promise<ComponentThemeApplicationResult> {
    // Merge theme config with component config
    const mergedConfig = this.mergeThemeWithComponent(theme.config, component.config);
    
    // Validate merged configuration
    const validation = this.validateMergedConfig(component.type, mergedConfig);
    
    if (!validation.valid) {
      throw new ConfigurationValidationError(
        `Invalid configuration after theme application`,
        validation.errors
      );
    }
    
    // Update component configuration
    await component.update({
      config: mergedConfig,
      theme_id: theme.id
    });
    
    return {
      componentId: component.id,
      previousConfig: component.config,
      newConfig: mergedConfig,
      success: true
    };
  }
  
  private generateCSSVariables(theme: ComponentTheme): Record<string, string> {
    const variables: Record<string, string> = {};
    
    // Generate color variables
    this.generateColorVariables(theme.config.colors, variables);
    
    // Generate typography variables
    this.generateTypographyVariables(theme.config.typography, variables);
    
    // Generate spacing variables
    this.generateSpacingVariables(theme.config.spacing, variables);
    
    return variables;
  }
  
  private generateColorVariables(
    colors: ColorPalette,
    variables: Record<string, string>
  ): void {
    // Primary colors
    if (colors.primary) {
      for (const [variant, value] of Object.entries(colors.primary)) {
        variables[`--color-primary-${variant}`] = value;
      }
    }
    
    // Secondary colors
    if (colors.secondary) {
      for (const [variant, value] of Object.entries(colors.secondary)) {
        variables[`--color-secondary-${variant}`] = value;
      }
    }
    
    // Text colors
    if (colors.text) {
      variables['--color-text-primary'] = colors.text.primary;
      variables['--color-text-secondary'] = colors.text.secondary;
      variables['--color-text-muted'] = colors.text.muted;
      variables['--color-text-inverse'] = colors.text.inverse;
    }
    
    // Background colors
    if (colors.background) {
      variables['--color-bg-primary'] = colors.background.primary;
      variables['--color-bg-secondary'] = colors.background.secondary;
      variables['--color-bg-tertiary'] = colors.background.tertiary;
      variables['--color-bg-overlay'] = colors.background.overlay;
    }
  }
}
```

### CSS Variable Generation

The system generates CSS variables that can be used across components:

```css
/* Generated CSS variables from theme */
:root {
  /* Primary Colors */
  --color-primary-50: #eff6ff;
  --color-primary-100: #dbeafe;
  --color-primary-200: #bfdbfe;
  --color-primary-300: #93c5fd;
  --color-primary-400: #60a5fa;
  --color-primary-500: #3b82f6;
  --color-primary-600: #2563eb;
  --color-primary-700: #1d4ed8;
  --color-primary-800: #1e40af;
  --color-primary-900: #1e3a8a;
  
  /* Typography */
  --font-family-primary: 'Inter', system-ui, sans-serif;
  --font-family-secondary: 'Roboto', system-ui, sans-serif;
  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  
  /* Spacing */
  --spacing-1: 0.25rem;
  --spacing-2: 0.5rem;
  --spacing-3: 0.75rem;
  --spacing-4: 1rem;
  --spacing-5: 1.25rem;
  --spacing-6: 1.5rem;
  --spacing-8: 2rem;
  --spacing-10: 2.5rem;
  --spacing-12: 3rem;
  --spacing-16: 4rem;
  --spacing-20: 5rem;
  --spacing-24: 6rem;
}
```

## GrapeJS Style Manager Integration

### Style Manager Configuration

The theme system integrates with GrapeJS Style Manager to provide consistent styling options:

```typescript
class GrapeJSStyleManagerIntegration {
  configureStyleManager(editor: Editor, theme: ComponentTheme): void {
    const styleManager = editor.StyleManager;
    
    // Clear existing sectors
    styleManager.getSectors().reset();
    
    // Add theme-based sectors
    this.addColorSector(styleManager, theme.config.colors);
    this.addTypographySector(styleManager, theme.config.typography);
    this.addSpacingSector(styleManager, theme.config.spacing);
    this.addLayoutSector(styleManager);
  }
  
  private addColorSector(
    styleManager: StyleManager,
    colors: ColorPalette
  ): void {
    styleManager.addSector('colors', {
      name: 'Colors',
      open: true,
      properties: [
        {
          name: 'Background Color',
          property: 'background-color',
          type: 'color',
          list: this.generateColorList(colors, 'background')
        },
        {
          name: 'Text Color',
          property: 'color',
          type: 'color',
          list: this.generateColorList(colors, 'text')
        },
        {
          name: 'Border Color',
          property: 'border-color',
          type: 'color',
          list: this.generateColorList(colors, 'border')
        }
      ]
    });
  }
  
  private generateColorList(
    colors: ColorPalette,
    context: 'background' | 'text' | 'border'
  ): ColorOption[] {
    const colorList: ColorOption[] = [];
    
    // Add primary colors
    if (colors.primary) {
      for (const [variant, value] of Object.entries(colors.primary)) {
        colorList.push({
          name: `Primary ${variant}`,
          value: value,
          className: `${context}-primary-${variant}`
        });
      }
    }
    
    // Add secondary colors
    if (colors.secondary) {
      for (const [variant, value] of Object.entries(colors.secondary)) {
        colorList.push({
          name: `Secondary ${variant}`,
          value: value,
          className: `${context}-secondary-${variant}`
        });
      }
    }
    
    return colorList;
  }
}
```

## Theme Inheritance System

### Parent-Child Theme Relationships

Themes can inherit from parent themes, allowing for brand variations:

```typescript
class ThemeInheritanceService {
  async createChildTheme(
    parentThemeId: string,
    overrides: Partial<ThemeConfig>,
    metadata: ThemeMetadata
  ): Promise<ComponentTheme> {
    const parentTheme = await ComponentTheme.findOrFail(parentThemeId);
    
    // Merge parent config with overrides
    const mergedConfig = this.mergeThemeConfigs(
      parentTheme.config,
      overrides
    );
    
    // Validate merged configuration
    const validation = this.validateThemeConfig(mergedConfig);
    
    if (!validation.valid) {
      throw new ThemeValidationError(
        'Invalid theme configuration after inheritance',
        validation.errors
      );
    }
    
    // Create child theme
    const childTheme = await ComponentTheme.create({
      tenant_id: parentTheme.tenant_id,
      name: metadata.name,
      slug: this.generateSlug(metadata.name),
      config: mergedConfig,
      parent_theme_id: parentThemeId,
      is_default: false
    });
    
    return childTheme;
  }
  
  private mergeThemeConfigs(
    parentConfig: ThemeConfig,
    overrides: Partial<ThemeConfig>
  ): ThemeConfig {
    return {
      colors: {
        ...parentConfig.colors,
        ...overrides.colors
      },
      typography: {
        ...parentConfig.typography,
        ...overrides.typography
      },
      spacing: {
        ...parentConfig.spacing,
        ...overrides.spacing
      },
      borders: {
        ...parentConfig.borders,
        ...overrides.borders
      },
      shadows: {
        ...parentConfig.shadows,
        ...overrides.shadows
      },
      animations: {
        ...parentConfig.animations,
        ...overrides.animations
      },
      breakpoints: {
        ...parentConfig.breakpoints,
        ...overrides.breakpoints
      }
    };
  }
  
  async getThemeHierarchy(themeId: string): Promise<ThemeHierarchy> {
    const theme = await ComponentTheme.findOrFail(themeId);
    const ancestors: ComponentTheme[] = [];
    const descendants: ComponentTheme[] = [];
    
    // Get ancestors
    let currentTheme = theme;
    while (currentTheme.parent_theme_id) {
      const parent = await ComponentTheme.find(currentTheme.parent_theme_id);
      if (parent) {
        ancestors.unshift(parent);
        currentTheme = parent;
      } else {
        break;
      }
    }
    
    // Get descendants
    const children = await ComponentTheme
      .where('parent_theme_id', themeId)
      .get();
    
    for (const child of children) {
      descendants.push(child);
      const grandchildren = await this.getDescendants(child.id);
      descendants.push(...grandchildren);
    }
    
    return {
      theme,
      ancestors,
      descendants,
      depth: ancestors.length
    };
  }
}
```

## Performance Optimization

### Theme Caching Strategy

```typescript
class ThemeCacheService {
  private cache = new Map<string, CachedTheme>();
  
  async getTheme(themeId: string): Promise<ComponentTheme> {
    const cacheKey = `theme:${themeId}`;
    
    if (this.cache.has(cacheKey)) {
      const cached = this.cache.get(cacheKey)!;
      
      if (!this.isCacheExpired(cached)) {
        return cached.theme;
      }
    }
    
    const theme = await ComponentTheme.findOrFail(themeId);
    
    this.cache.set(cacheKey, {
      theme,
      cachedAt: new Date(),
      expiresAt: new Date(Date.now() + 3600000) // 1 hour
    });
    
    return theme;
  }
  
  invalidateTheme(themeId: string): void {
    const cacheKey = `theme:${themeId}`;
    this.cache.delete(cacheKey);
    
    // Also invalidate child themes
    for (const [key, cached] of this.cache.entries()) {
      if (cached.theme.parent_theme_id === themeId) {
        this.cache.delete(key);
      }
    }
  }
  
  preloadThemes(themeIds: string[]): Promise<void[]> {
    return Promise.all(
      themeIds.map(id => this.getTheme(id))
    );
  }
}
```

### CSS Generation Optimization

```typescript
class OptimizedCSSGenerator {
  private cssCache = new Map<string, string>();
  
  async generateOptimizedCSS(theme: ComponentTheme): Promise<string> {
    const cacheKey = `css:${theme.id}:${theme.updated_at.getTime()}`;
    
    if (this.cssCache.has(cacheKey)) {
      return this.cssCache.get(cacheKey)!;
    }
    
    const css = await this.generateCSS(theme);
    const optimizedCSS = await this.optimizeCSS(css);
    
    this.cssCache.set(cacheKey, optimizedCSS);
    
    return optimizedCSS;
  }
  
  private async optimizeCSS(css: string): Promise<string> {
    // Remove duplicate rules
    const deduplicated = this.removeDuplicateRules(css);
    
    // Minify CSS
    const minified = this.minifyCSS(deduplicated);
    
    // Compress with gzip if supported
    return minified;
  }
}
```

This comprehensive theme integration documentation provides developers and users with the knowledge needed to effectively work with themes in the Component Library System while ensuring compatibility with GrapeJS and maintaining high performance standards.