<?php

namespace App\Services;

use App\Models\PublishedSite;
use App\Models\LandingPage;
use App\Models\SiteDeployment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * Publishing Service
 *
 * Core business logic for static site generation and publishing operations.
 * Handles template-to-HTML conversion, asset optimization, and deployment workflows.
 */
class PublishingService
{
    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'publishing_';
    private const CACHE_DURATION = 300; // 5 minutes

    /**
     * Supported output formats
     */
    public const OUTPUT_FORMATS = [
        'html',
        'static',
        'spa',
    ];

    /**
     * Generate static site from landing page
     *
     * @param LandingPage $landingPage
     * @param array $options Build options (format, minify, etc.)
     * @return array Generated site data
     */
    public function generateStaticSite(LandingPage $landingPage, array $options = []): array
    {
        try {
            Log::info('Starting static site generation', [
                'landing_page_id' => $landingPage->id,
                'options' => $options
            ]);

            // Get effective configuration
            $config = $landingPage->getEffectiveConfig();

            // Generate HTML content
            $htmlContent = $this->generateHtmlContent($landingPage, $config, $options);

            // Generate assets
            $assets = $this->generateAssets($landingPage, $config, $options);

            // Apply optimizations
            if ($options['minify'] ?? true) {
                $htmlContent = $this->minifyHtml($htmlContent);
                $assets = $this->optimizeAssets($assets);
            }

            // Generate build manifest
            $manifest = $this->generateBuildManifest($landingPage, $htmlContent, $assets);

            return [
                'html' => $htmlContent,
                'assets' => $assets,
                'manifest' => $manifest,
                'build_hash' => Str::random(32),
                'generated_at' => now(),
            ];

        } catch (\Exception $e) {
            Log::error('Static site generation failed', [
                'landing_page_id' => $landingPage->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Failed to generate static site: ' . $e->getMessage());
        }
    }

    /**
     * Generate HTML content from landing page
     *
     * @param LandingPage $landingPage
     * @param array $config
     * @param array $options
     * @return string
     */
    private function generateHtmlContent(LandingPage $landingPage, array $config, array $options): string
    {
        $template = $landingPage->template;
        $structure = $template ? $template->getEffectiveStructure() : [];

        // Build HTML structure
        $html = $this->buildHtmlStructure($structure, $config);

        // Add SEO metadata
        $html = $this->injectSEOMetadata($html, $landingPage);

        // Add analytics tracking
        if ($landingPage->tracking_id) {
            $html = $this->injectAnalytics($html, $landingPage->tracking_id);
        }

        // Add custom CSS/JS
        if ($landingPage->custom_css) {
            $html = $this->injectCustomCss($html, $landingPage->custom_css);
        }

        if ($landingPage->custom_js) {
            $html = $this->injectCustomJs($html, $landingPage->custom_js);
        }

        return $html;
    }

    /**
     * Build HTML structure from template
     *
     * @param array $structure
     * @param array $config
     * @return string
     */
    private function buildHtmlStructure(array $structure, array $config): string
    {
        $html = '<!DOCTYPE html><html lang="en"><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>' . ($config['seo_title'] ?? 'Landing Page') . '</title>';

        // Add CSS
        $html .= $this->generateInlineCss($config);

        $html .= '</head><body>';

        // Generate body content from sections
        if (isset($structure['sections'])) {
            foreach ($structure['sections'] as $section) {
                $html .= $this->renderSection($section, $config);
            }
        }

        // Add JavaScript
        $html .= $this->generateInlineJs($config);

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Render a section from template structure
     *
     * @param array $section
     * @param array $config
     * @return string
     */
    private function renderSection(array $section, array $config): string
    {
        $type = $section['type'] ?? 'div';
        $sectionConfig = $section['config'] ?? [];

        // Merge with global config
        $effectiveConfig = array_merge($config, $sectionConfig);

        switch ($type) {
            case 'hero':
                return $this->renderHeroSection($effectiveConfig);
            case 'form':
                return $this->renderFormSection($effectiveConfig);
            case 'statistics':
                return $this->renderStatisticsSection($effectiveConfig);
            case 'testimonials':
                return $this->renderTestimonialsSection($effectiveConfig);
            default:
                return $this->renderGenericSection($type, $effectiveConfig);
        }
    }

    /**
     * Render hero section
     *
     * @param array $config
     * @return string
     */
    private function renderHeroSection(array $config): string
    {
        return '<section class="hero-section">' .
               '<h1>' . ($config['title'] ?? '') . '</h1>' .
               '<p>' . ($config['subtitle'] ?? '') . '</p>' .
               '<a href="#" class="cta-button">' . ($config['cta_text'] ?? 'Get Started') . '</a>' .
               '</section>';
    }

    /**
     * Render form section
     *
     * @param array $config
     * @return string
     */
    private function renderFormSection(array $config): string
    {
        $html = '<section class="form-section">';
        $html .= '<form method="POST" action="/submit">';

        if (isset($config['fields'])) {
            foreach ($config['fields'] as $field) {
                $html .= '<div class="form-field">';
                $html .= '<label>' . ($field['label'] ?? '') . '</label>';
                $html .= '<input type="' . ($field['type'] ?? 'text') . '" name="' . ($field['name'] ?? '') . '">';
                $html .= '</div>';
            }
        }

        $html .= '<button type="submit">' . ($config['submit_text'] ?? 'Submit') . '</button>';
        $html .= '</form></section>';

        return $html;
    }

    /**
     * Render statistics section
     *
     * @param array $config
     * @return string
     */
    private function renderStatisticsSection(array $config): string
    {
        $html = '<section class="statistics-section">';

        if (isset($config['items'])) {
            foreach ($config['items'] as $item) {
                $html .= '<div class="stat-item">';
                $html .= '<div class="stat-number">' . ($item['number'] ?? '') . '</div>';
                $html .= '<div class="stat-label">' . ($item['label'] ?? '') . '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</section>';
        return $html;
    }

    /**
     * Render testimonials section
     *
     * @param array $config
     * @return string
     */
    private function renderTestimonialsSection(array $config): string
    {
        $html = '<section class="testimonials-section">';

        if (isset($config['items'])) {
            foreach ($config['items'] as $item) {
                $html .= '<div class="testimonial">';
                $html .= '<p>"' . ($item['content'] ?? '') . '"</p>';
                $html .= '<cite>' . ($item['author'] ?? '') . '</cite>';
                $html .= '</div>';
            }
        }

        $html .= '</section>';
        return $html;
    }

    /**
     * Render generic section
     *
     * @param string $type
     * @param array $config
     * @return string
     */
    private function renderGenericSection(string $type, array $config): string
    {
        return '<section class="' . $type . '-section">' .
               '<div class="content">' . ($config['content'] ?? '') . '</div>' .
               '</section>';
    }

    /**
     * Generate inline CSS
     *
     * @param array $config
     * @return string
     */
    private function generateInlineCss(array $config): string
    {
        $css = '<style>';

        // Brand colors
        if (isset($config['colors'])) {
            foreach ($config['colors'] as $key => $value) {
                $css .= "--brand-{$key}: {$value};";
            }
        }

        // Typography
        if (isset($config['typography'])) {
            $fontFamily = $config['typography']['font_family'] ?? 'Arial, sans-serif';
            $css .= "body { font-family: {$fontFamily}; }";
        }

        // Add base styles
        $css .= '
            .hero-section { padding: 100px 20px; text-align: center; background: var(--brand-primary, #007bff); color: white; }
            .form-section { padding: 50px 20px; max-width: 600px; margin: 0 auto; }
            .statistics-section { padding: 50px 20px; display: flex; justify-content: space-around; }
            .testimonials-section { padding: 50px 20px; }
            .cta-button { display: inline-block; padding: 15px 30px; background: var(--brand-secondary, #28a745); color: white; text-decoration: none; border-radius: 5px; }
        ';

        $css .= '</style>';
        return $css;
    }

    /**
     * Generate inline JavaScript
     *
     * @param array $config
     * @return string
     */
    private function generateInlineJs(array $config): string
    {
        return '<script>
            // Basic form handling
            document.addEventListener("DOMContentLoaded", function() {
                const forms = document.querySelectorAll("form");
                forms.forEach(form => {
                    form.addEventListener("submit", function(e) {
                        e.preventDefault();
                        alert("Form submitted!");
                    });
                });
            });
        </script>';
    }

    /**
     * Inject SEO metadata
     *
     * @param string $html
     * @param LandingPage $landingPage
     * @return string
     */
    private function injectSEOMetadata(string $html, LandingPage $landingPage): string
    {
        $seoData = $landingPage->getSEOMetadata();

        $metaTags = '';
        if ($seoData['description']) {
            $metaTags .= '<meta name="description" content="' . htmlspecialchars($seoData['description']) . '">';
        }

        if ($seoData['keywords']) {
            $metaTags .= '<meta name="keywords" content="' . htmlspecialchars(implode(', ', $seoData['keywords'])) . '">';
        }

        // Inject meta tags after charset
        return str_replace(
            '<meta charset="UTF-8">',
            '<meta charset="UTF-8">' . $metaTags,
            $html
        );
    }

    /**
     * Inject analytics tracking
     *
     * @param string $html
     * @param string $trackingId
     * @return string
     */
    private function injectAnalytics(string $html, string $trackingId): string
    {
        $analyticsCode = "<script async src='https://www.googletagmanager.com/gtag/js?id={$trackingId}'></script>";
        $analyticsCode .= "<script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '{$trackingId}');</script>";

        // Inject before closing body tag
        return str_replace('</body>', $analyticsCode . '</body>', $html);
    }

    /**
     * Inject custom CSS
     *
     * @param string $html
     * @param string $customCss
     * @return string
     */
    private function injectCustomCss(string $html, string $customCss): string
    {
        $cssTag = '<style>' . $customCss . '</style>';

        // Inject before closing head
        return str_replace('</head>', $cssTag . '</head>', $html);
    }

    /**
     * Inject custom JavaScript
     *
     * @param string $html
     * @param string $customJs
     * @return string
     */
    private function injectCustomJs(string $html, string $customJs): string
    {
        $jsTag = '<script>' . $customJs . '</script>';

        // Inject before closing body
        return str_replace('</body>', $jsTag . '</body>', $html);
    }

    /**
     * Generate assets for the site
     *
     * @param LandingPage $landingPage
     * @param array $config
     * @param array $options
     * @return array
     */
    private function generateAssets(LandingPage $landingPage, array $config, array $options): array
    {
        $assets = [];

        // Generate favicon if available
        if ($landingPage->favicon_url) {
            $assets['favicon.ico'] = $landingPage->favicon_url;
        }

        // Generate social image if available
        if ($landingPage->social_image) {
            $assets['social-image.jpg'] = $landingPage->social_image;
        }

        return $assets;
    }

    /**
     * Optimize assets
     *
     * @param array $assets
     * @return array
     */
    private function optimizeAssets(array $assets): array
    {
        // In a real implementation, this would compress images, minify CSS/JS, etc.
        // For now, just return as-is
        return $assets;
    }

    /**
     * Minify HTML content
     *
     * @param string $html
     * @return string
     */
    private function minifyHtml(string $html): string
    {
        // Remove extra whitespace and comments
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        return trim($html);
    }

    /**
     * Generate build manifest
     *
     * @param LandingPage $landingPage
     * @param string $htmlContent
     * @param array $assets
     * @return array
     */
    private function generateBuildManifest(LandingPage $landingPage, string $htmlContent, array $assets): array
    {
        return [
            'landing_page_id' => $landingPage->id,
            'template_id' => $landingPage->template_id ?? null,
            'version' => $landingPage->version,
            'build_time' => now()->toISOString(),
            'html_size' => strlen($htmlContent),
            'assets_count' => count($assets),
            'assets' => array_keys($assets),
        ];
    }

    /**
     * Deploy site to storage/CDN
     *
     * @param PublishedSite $site
     * @param array $buildData
     * @return array Deployment result
     */
    public function deploySite(PublishedSite $site, array $buildData): array
    {
        try {
            $deploymentId = 'deploy_' . Str::random(16);
            $basePath = "published-sites/{$site->tenant_id}/{$site->slug}";

            // Store HTML
            Storage::disk('public')->put("{$basePath}/index.html", $buildData['html']);

            // Store assets
            foreach ($buildData['assets'] as $filename => $content) {
                Storage::disk('public')->put("{$basePath}/assets/{$filename}", $content);
            }

            // Store manifest
            Storage::disk('public')->put(
                "{$basePath}/manifest.json",
                json_encode($buildData['manifest'], JSON_PRETTY_PRINT)
            );

            // Update site URLs
            $site->update([
                'static_url' => "/storage/{$basePath}/index.html",
                'build_hash' => $buildData['build_hash'],
                'last_deployed_at' => now(),
            ]);

            return [
                'deployment_id' => $deploymentId,
                'status' => 'success',
                'url' => $site->static_url,
                'build_hash' => $buildData['build_hash'],
            ];

        } catch (\Exception $e) {
            Log::error('Site deployment failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Deployment failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate publishing configuration
     *
     * @param array $config
     * @return bool
     * @throws \Exception
     */
    public function validatePublishingConfig(array $config): bool
    {
        $requiredFields = ['landing_page_id', 'tenant_id'];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        return true;
    }
}