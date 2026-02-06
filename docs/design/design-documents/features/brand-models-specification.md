# Brand Models Specification

## Overview
This document specifies the brand models that need to be created to support the BrandCustomizerService. These models are currently referenced in the service but don't exist yet.

## Models to Create

### 1. BrandLogo
- **Purpose**: Store tenant-specific logo assets
- **Fields**:
  - id (bigint, primary key)
  - tenant_id (bigint, foreign key to tenants)
  - name (string)
  - type (string, enum: primary, secondary, favicon, etc.)
  - url (string)
  - alt (string)
  - size (integer)
  - mime_type (string)
  - is_primary (boolean)
  - optimized (boolean)
  - cdn_url (string, nullable)
  - variants (json, array of variant objects)
  - usage_guidelines (json, array of guidelines)
  - created_at (timestamp)
  - updated_at (timestamp)

### 2. BrandColor
- **Purpose**: Store tenant-specific color palette
- **Fields**:
  - id (bigint, primary key)
  - tenant_id (bigint, foreign key to tenants)
  - name (string)
  - value (string, hex color code)
  - type (string, enum: primary, secondary, accent, neutral, etc.)
  - usage_guidelines (text, nullable)
  - usage_count (integer, default: 0)
  - contrast_ratios (json, array of contrast ratio objects)
  - accessibility (json, accessibility information)
  - created_at (timestamp)
  - updated_at (timestamp)

### 3. BrandFont
- **Purpose**: Store tenant-specific font configurations
- **Fields**:
  - id (bigint, primary key)
  - tenant_id (bigint, foreign key to tenants)
  - name (string)
  - family (string)
  - weights (json, array of available weights)
  - styles (json, array of available styles)
  - is_primary (boolean)
  - type (string, enum: system, google, custom)
  - source (string, url for custom fonts)
  - url (string, nullable)
  - fallbacks (json, array of fallback fonts)
  - usage_count (integer, default: 0)
  - loading_strategy (string, enum: swap, block, optional, etc.)
  - created_at (timestamp)
  - updated_at (timestamp)

### 4. BrandTemplate
- **Purpose**: Store predefined brand configurations that can be applied to components
- **Fields**:
  - id (bigint, primary key)
  - tenant_id (bigint, foreign key to tenants)
  - name (string)
  - description (text, nullable)
  - primary_font (string, nullable)
  - secondary_font (string, nullable)
  - logo_variant (string, nullable)
  - tags (json, array of tags)
  - is_default (boolean)
  - usage_count (integer, default: 0)
  - created_at (timestamp)
  - updated_at (timestamp)

### 5. BrandGuidelines
- **Purpose**: Store tenant-specific brand guideline configurations
- **Fields**:
  - id (bigint, primary key)
  - tenant_id (bigint, foreign key to tenants, unique)
  - enforce_color_palette (boolean, default: true)
  - require_contrast_check (boolean, default: true)
  - min_contrast_ratio (decimal, default: 4.5)
  - enforce_font_families (boolean, default: true)
  - enforce_typography_scale (boolean, default: true)
  - max_heading_size (integer, default: 48)
  - max_body_size (integer, default: 18)
  - enforce_logo_placement (boolean, default: true)
  - min_logo_size (integer, default: 32)
  - logo_clear_space (decimal, default: 1.5)
  - created_at (timestamp)
  - updated_at (timestamp)

## Relationships

### BrandTemplate ↔ BrandColor
- Many-to-many relationship through brand_template_colors pivot table
- Fields: brand_template_id, brand_color_id

## Indexes

All models should have the following indexes:
- tenant_id (for all tenant-scoped models)
- created_at (for sorting)
- name (for searching)
- Additionally, foreign key constraints where appropriate

## Tenant Scoping

All models except BrandGuidelines should be scoped to tenants:
- BrandGuidelines is tenant-specific but only one per tenant
- All other models can have multiple records per tenant

## JSON Structure Examples

### BrandLogo.variants
```json
[
  {
    "type": "optimized",
    "url": "https://cdn.example.com/logos/logo-32.webp",
    "size": 32,
    "format": "webp"
  },
  {
    "type": "fallback",
    "url": "https://cdn.example.com/logos/logo-32.png",
    "size": 32,
    "format": "png"
  }
]
```

### BrandColor.contrast_ratios
```json
[
  {
    "background": "#FFFFFF",
    "ratio": 4.5,
    "level": "AA"
  },
  {
    "background": "#000000", 
    "ratio": 7.0,
    "level": "AAA"
  }
]
```

### BrandTemplate.tags
```json
["marketing", "onboarding", "professional"]
```

## Migration Order

1. Create BrandGuidelines table (tenant_id unique)
2. Create BrandColor table
3. Create BrandFont table
4. Create BrandLogo table
5. Create BrandTemplate table
6. Create brand_template_colors pivot table

This order ensures foreign key dependencies are properly handled.