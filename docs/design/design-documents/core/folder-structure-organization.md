# Folder Structure Organization

## Required Directories to Create

1. `design-documents/core/` - Core system architecture documents
2. `design-documents/features/` - Feature-specific design documents
3. `design-documents/integrations/` - Integration system design documents
4. `design-documents/tools/` - Tool-specific design documents

## Documents to Move

### Core System Documents (Move to `design-documents/core/`)
- `system-architecture.md` - Overall system architecture
- `vue-wrapper-component.md` - Vue wrapper component design

### Feature Documents (Move to `design-documents/features/`)
- `real-time-editing-preview-design.md` - Real-time editing and preview
- `advanced-styling-customization-design.md` - Advanced styling tools
- `form-builder-crm-connectivity-design.md` - Form builder with CRM
- `version-control-collaboration-design.md` - Version control features
- `preview-testing-publishing-design.md` - Preview and publishing
- `seo-performance-optimization-design.md` - SEO and performance
- `analytics-tracking-design.md` - Analytics and tracking
- `ab-testing-integration-design.md` - A/B testing system
- `custom-code-integration-design.md` - Custom code integration
- `export-backup-migration-design.md` - Export and backup
- `multi-language-support-design.md` - Multi-language support
- `comprehensive-testing-suite-design.md` - Testing suite
- `security-access-control-design.md` - Security features

### Integration Documents (Move to `design-documents/integrations/`)
- `template-system-bridge-design.md` - Template system integration
- `grapejs-vue-wrapper-design.md` - GrapeJS-Vue integration
- `component-library-bridge-design.md` - Component library bridge

### Tool Documents (Move to `design-documents/tools/`)
- `deployment-production-optimization-design.md` - Deployment tools

## PowerShell Commands to Execute

```powershell
# Create directory structure
New-Item -ItemType Directory -Path "design-documents\core" -Force
New-Item -ItemType Directory -Path "design-documents\features" -Force
New-Item -ItemType Directory -Path "design-documents\integrations" -Force
New-Item -ItemType Directory -Path "design-documents\tools" -Force

# Move core system documents
Move-Item -Path "system-architecture.md" -Destination "design-documents\core\system-architecture.md" -Force
Move-Item -Path "vue-wrapper-component.md" -Destination "design-documents\core\vue-wrapper-component.md" -Force

# Move feature documents
Move-Item -Path "real-time-editing-preview-design.md" -Destination "design-documents\features\real-time-editing-preview-design.md" -Force
Move-Item -Path "advanced-styling-customization-design.md" -Destination "design-documents\features\advanced-styling-customization-design.md" -Force
Move-Item -Path "form-builder-crm-connectivity-design.md" -Destination "design-documents\features\form-builder-crm-connectivity-design.md" -Force
Move-Item -Path "version-control-collaboration-design.md" -Destination "design-documents\features\version-control-collaboration-design.md" -Force
Move-Item -Path "preview-testing-publishing-design.md" -Destination "design-documents\features\preview-testing-publishing-design.md" -Force
Move-Item -Path "seo-performance-optimization-design.md" -Destination "design-documents\features\seo-performance-optimization-design.md" -Force
Move-Item -Path "analytics-tracking-design.md" -Destination "design-documents\features\analytics-tracking-design.md" -Force
Move-Item -Path "ab-testing-integration-design.md" -Destination "design-documents\features\ab-testing-integration-design.md" -Force
Move-Item -Path "custom-code-integration-design.md" -Destination "design-documents\features\custom-code-integration-design.md" -Force
Move-Item -Path "export-backup-migration-design.md" -Destination "design-documents\features\export-backup-migration-design.md" -Force
Move-Item -Path "multi-language-support-design.md" -Destination "design-documents\features\multi-language-support-design.md" -Force
Move-Item -Path "comprehensive-testing-suite-design.md" -Destination "design-documents\features\comprehensive-testing-suite-design.md" -Force
Move-Item -Path "security-access-control-design.md" -Destination "design-documents\features\security-access-control-design.md" -Force

# Move integration documents
Move-Item -Path "template-system-bridge-design.md" -Destination "design-documents\integrations\template-system-bridge-design.md" -Force
Move-Item -Path "grapejs-vue-wrapper-design.md" -Destination "design-documents\integrations\grapejs-vue-wrapper-design.md" -Force
Move-Item -Path "component-library-bridge-design.md" -Destination "design-documents\integrations\component-library-bridge-design.md" -Force

# Move tool documents
Move-Item -Path "deployment-production-optimization-design.md" -Destination "design-documents\tools\deployment-production-optimization-design.md" -Force
```

## Verification Commands

```powershell
# Verify directory structure
Get-ChildItem -Path "design-documents" -Recurse

# Verify core documents
Get-ChildItem -Path "design-documents\core"

# Verify feature documents
Get-ChildItem -Path "design-documents\features"

# Verify integration documents
Get-ChildItem -Path "design-documents\integrations"

# Verify tool documents
Get-ChildItem -Path "design-documents\tools"
```

## Notes

1. Ensure you have proper permissions to create directories and move files
2. Make sure all the source files exist before running the move commands
3. The `-Force` parameter will overwrite existing files if they already exist in the destination
4. Run the verification commands after moving files to confirm the operation was successful
5. Update any references to these files in other documents or code