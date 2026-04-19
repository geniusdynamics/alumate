#!/usr/bin/env php
<?php

/**
 * Service Governance Check
 *
 * Validates that all services comply with governance rules:
 * - Maximum 300 lines per service
 * - Maximum 15 public methods per service
 * - Maximum 5 dependencies in constructor
 *
 * Usage: php scripts/governance/check-service-size.php
 */
$serviceDir = __DIR__.'/../../app/Services';
$maxLines = 300;
$maxMethods = 15;
$maxDependencies = 5;

$errors = [];
$warnings = [];
$stats = [
    'total' => 0,
    'compliant' => 0,
    'non_compliant' => 0,
    'largest' => ['file' => '', 'lines' => 0],
    'most_deps' => ['file' => '', 'deps' => 0],
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($serviceDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $stats['total']++;
    $content = file_get_contents($file->getPathname());
    $lines = substr_count($content, "\n") + 1;
    $relativePath = str_replace(__DIR__.'/../../', '', $file->getPathname());

    // Track largest service
    if ($lines > $stats['largest']['lines']) {
        $stats['largest'] = ['file' => $relativePath, 'lines' => $lines];
    }

    // Check line count
    if ($lines > $maxLines) {
        $errors[] = "❌ {$relativePath}: {$lines} lines (max {$maxLines})";
        $stats['non_compliant']++;
    } else {
        $stats['compliant']++;
    }

    // Check method count
    preg_match_all('/^\s*public\s+function\s+(\w+)/m', $content, $methodMatches);
    $methodCount = count($methodMatches[1]);

    if ($methodCount > $maxMethods) {
        $errors[] = "❌ {$relativePath}: {$methodCount} public methods (max {$maxMethods})";
    }

    // Check constructor dependencies
    preg_match('/public\s+function\s+__construct\s*\((.*?)\)/s', $content, $constructorMatch);
    if (! empty($constructorMatch[1])) {
        preg_match_all('/private\s+\w+\s+\$\w+/', $constructorMatch[1], $depMatches);
        $depCount = count($depMatches[0]);

        if ($depCount > $maxDependencies) {
            $warnings[] = "⚠️  {$relativePath}: {$depCount} constructor dependencies (max {$maxDependencies})";
        }

        if ($depCount > $stats['most_deps']['deps']) {
            $stats['most_deps'] = ['file' => $relativePath, 'deps' => $depCount];
        }
    }
}

// Output report
echo "\n";
echo "========================================\n";
echo "  Service Governance Report\n";
echo '  '.date('Y-m-d H:i:s')."\n";
echo "========================================\n\n";

echo "Summary:\n";
echo "  Total Services: {$stats['total']}\n";
echo "  Compliant: {$stats['compliant']}\n";
echo "  Non-Compliant: {$stats['non_compliant']}\n";
echo "  Largest Service: {$stats['largest']['file']} ({$stats['largest']['lines']} lines)\n";
echo "  Most Dependencies: {$stats['most_deps']['file']} ({$stats['most_deps']['deps']} deps)\n\n";

if (! empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "  {$error}\n";
    }
    echo "\n";
}

if (! empty($warnings)) {
    echo "Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  {$warning}\n";
    }
    echo "\n";
}

if (empty($errors)) {
    echo "✅ All services pass governance checks!\n";
    exit(0);
} else {
    echo '❌ '.count($errors)." service(s) failed governance checks.\n";
    exit(1);
}
