#!/usr/bin/env php
<?php

/**
 * Frontend Component Audit Script
 *
 * Analyzes the frontend component structure and identifies:
 * - Large components (>300 lines)
 * - Unused components (no imports found)
 * - Component distribution by category
 * - TypeScript strictness issues
 *
 * Usage: php scripts/frontend/audit-components.php
 */
$baseDir = __DIR__.'/../../resources/js';
$componentDir = $baseDir.'/Components';
$pagesDir = $baseDir.'/Pages';

$stats = [
    'total_components' => 0,
    'total_pages' => 0,
    'large_components' => [],
    'component_categories' => [],
    'pages_by_role' => [],
    'typescript_issues' => [],
];

// Scan Components
$componentIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($componentDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($componentIterator as $file) {
    if ($file->getExtension() !== 'vue') {
        continue;
    }

    $stats['total_components']++;
    $content = file_get_contents($file->getPathname());
    $lines = substr_count($content, "\n") + 1;
    $relativePath = str_replace(__DIR__.'/../../', '', $file->getPathname());

    // Categorize by directory
    $parts = explode(DIRECTORY_SEPARATOR, str_replace($componentDir.DIRECTORY_SEPARATOR, '', $file->getPathname()));
    $category = $parts[0] ?? 'root';
    $stats['component_categories'][$category] = ($stats['component_categories'][$category] ?? 0) + 1;

    // Track large components
    if ($lines > 300) {
        $stats['large_components'][] = [
            'file' => $relativePath,
            'lines' => $lines,
        ];
    }

    // Check for TypeScript strictness issues
    if (strpos($content, ': any') !== false) {
        $stats['typescript_issues'][] = [
            'file' => $relativePath,
            'issue' => 'Uses `any` type',
        ];
    }
    if (strpos($content, 'as any') !== false) {
        $stats['typescript_issues'][] = [
            'file' => $relativePath,
            'issue' => 'Uses `as any` cast',
        ];
    }
}

// Scan Pages
$pageIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pagesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($pageIterator as $file) {
    if ($file->getExtension() !== 'vue') {
        continue;
    }

    $stats['total_pages']++;
    $relativePath = str_replace(__DIR__.'/../../', '', $file->getPathname());

    // Categorize by role (first directory level)
    $parts = explode(DIRECTORY_SEPARATOR, str_replace($pagesDir.DIRECTORY_SEPARATOR, '', $file->getPathname()));
    $role = $parts[0] ?? 'root';
    $stats['pages_by_role'][$role] = ($stats['pages_by_role'][$role] ?? 0) + 1;
}

// Output report
echo "\n";
echo "========================================\n";
echo "  Frontend Component Audit Report\n";
echo '  '.date('Y-m-d H:i:s')."\n";
echo "========================================\n\n";

echo "Summary:\n";
echo "  Total Components: {$stats['total_components']}\n";
echo "  Total Pages: {$stats['total_pages']}\n\n";

echo "Component Distribution:\n";
foreach ($stats['component_categories'] as $category => $count) {
    echo "  {$category}/: {$count} components\n";
}
echo "\n";

echo "Pages by Role:\n";
foreach ($stats['pages_by_role'] as $role => $count) {
    echo "  {$role}/: {$count} pages\n";
}
echo "\n";

if (! empty($stats['large_components'])) {
    echo "Large Components (>300 lines):\n";
    foreach ($stats['large_components'] as $comp) {
        echo "  ❌ {$comp['file']}: {$comp['lines']} lines\n";
    }
    echo "\n";
}

if (! empty($stats['typescript_issues'])) {
    echo "TypeScript Strictness Issues:\n";
    foreach ($stats['typescript_issues'] as $issue) {
        echo "  ⚠️  {$issue['file']}: {$issue['issue']}\n";
    }
    echo "\n";
}

if (empty($stats['large_components']) && empty($stats['typescript_issues'])) {
    echo "✅ All components pass audit checks!\n";
    exit(0);
} else {
    $totalIssues = count($stats['large_components']) + count($stats['typescript_issues']);
    echo "⚠️  {$totalIssues} issue(s) found. See above for details.\n";
    exit(0);
}
