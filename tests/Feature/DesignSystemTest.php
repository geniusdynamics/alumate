<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_design_system_documentation_exists(): void
    {
        $this->assertTrue(
            file_exists(base_path('docs/design-system.md')),
            'Design system documentation file should exist'
        );
    }

    public function test_coding_standards_documentation_exists(): void
    {
        $this->assertTrue(
            file_exists(base_path('docs/coding-standards.md')),
            'Coding standards documentation file should exist'
        );
    }

    public function test_design_system_showcase_page_exists(): void
    {
        $this->assertTrue(
            file_exists(resource_path('js/Pages/DesignSystem/Showcase.vue')),
            'Design system showcase page should exist'
        );
    }

    public function test_design_system_showcase_route_exists(): void
    {
        $response = $this->get('/design-system');
        
        $response->assertStatus(200);
    }

    public function test_design_system_documentation_contains_required_sections(): void
    {
        $content = file_get_contents(base_path('docs/design-system.md'));
        
        $requiredSections = [
            '# Modern Alumni Platform - Design System Documentation',
            '## Color Palette',
            '## Typography Scale',
            '## Spacing System',
            '## Component Variants',
            '## Design Tokens',
            '## Naming Conventions',
            '## Accessibility Guidelines',
            '## Mobile Optimization',
            '## Theme System',
            '## Component Showcase'
        ];

        foreach ($requiredSections as $section) {
            $this->assertStringContainsString(
                $section,
                $content,
                "Design system documentation should contain section: {$section}"
            );
        }
    }

    public function test_coding_standards_documentation_contains_required_sections(): void
    {
        $content = file_get_contents(base_path('docs/coding-standards.md'));
        
        $requiredSections = [
            '# Modern Alumni Platform - Coding Standards',
            '## PHP/Laravel Standards',
            '## Vue.js/TypeScript Standards',
            '## CSS/Tailwind Standards',
            '## Database Standards',
            '## Testing Standards',
            '## File Organization',
            '## Naming Conventions',
            '## Documentation Standards',
            '## Performance Guidelines'
        ];

        foreach ($requiredSections as $section) {
            $this->assertStringContainsString(
                $section,
                $content,
                "Coding standards documentation should contain section: {$section}"
            );
        }
    }

    public function test_ui_components_have_proper_index_files(): void
    {
        $components = ['button', 'card', 'input', 'badge', 'label', 'textarea', 'checkbox'];
        
        foreach ($components as $component) {
            $indexPath = resource_path("js/Components/ui/{$component}/index.ts");
            $this->assertTrue(
                file_exists($indexPath),
                "UI component {$component} should have an index.ts file"
            );
        }
    }

    public function test_theme_system_css_variables_are_defined(): void
    {
        $appCssContent = file_get_contents(resource_path('css/app.css'));
        
        $requiredVariables = [
            '--background:',
            '--foreground:',
            '--primary:',
            '--primary-foreground:',
            '--secondary:',
            '--secondary-foreground:',
            '--muted:',
            '--muted-foreground:',
            '--border:',
            '--input:',
            '--ring:'
        ];

        foreach ($requiredVariables as $variable) {
            $this->assertStringContainsString(
                $variable,
                $appCssContent,
                "app.css should define CSS variable: {$variable}"
            );
        }
    }

    public function test_tailwind_config_has_custom_design_tokens(): void
    {
        $tailwindConfigContent = file_get_contents(base_path('tailwind.config.js'));
        
        $requiredTokens = [
            'fontFamily',
            'colors',
            'spacing',
            'borderRadius',
            'minHeight',
            'minWidth'
        ];

        foreach ($requiredTokens as $token) {
            $this->assertStringContainsString(
                $token,
                $tailwindConfigContent,
                "tailwind.config.js should define custom token: {$token}"
            );
        }
    }
}