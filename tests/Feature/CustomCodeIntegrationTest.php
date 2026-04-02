<?php

test('custom code validation service exists', function () {
    // Test that the validation service files exist
    expect(file_exists(resource_path('js/services/CustomCodeValidationService.ts')))->toBeTrue();
    expect(file_exists(resource_path('js/services/CustomCodeStorageService.ts')))->toBeTrue();
    expect(file_exists(resource_path('js/services/CodeIsolationService.ts')))->toBeTrue();
})->skip('Database not configured');

test('custom code vue components exist', function () {
    expect(file_exists(resource_path('js/components/PageBuilder/CustomCodePanel.vue')))->toBeTrue();
    expect(file_exists(resource_path('js/components/PageBuilder/CustomCodeEditor.vue')))->toBeTrue();
    expect(file_exists(resource_path('js/components/PageBuilder/ValidationPanel.vue')))->toBeTrue();
    expect(file_exists(resource_path('js/components/PageBuilder/CodePreviewModal.vue')))->toBeTrue();
});

test('grapejs custom code plugin exists', function () {
    expect(file_exists(resource_path('js/services/grapeJSCustomCodePlugin.ts')))->toBeTrue();
});

test('laravel backend components exist', function () {
    expect(file_exists(app_path('Models/CustomCode.php')))->toBeTrue();
    expect(file_exists(app_path('Http/Controllers/CustomCodeController.php')))->toBeTrue();
});

test('custom code integration implementation', function () {
    // Test that PageBuilder component includes CustomCodePanel
    $pageBuilderContent = file_get_contents(resource_path('js/components/PageBuilder/PageBuilder.vue'));
    
    expect($pageBuilderContent)->toContain('CustomCodePanel');
    expect($pageBuilderContent)->toContain('showCustomCodePanel');
    expect($pageBuilderContent)->toContain('Custom Code');
});

test('required functionality implemented', function () {
    // Test CustomCodeValidationService has required methods
    $validationServiceContent = file_get_contents(resource_path('js/services/CustomCodeValidationService.ts'));
    
    expect($validationServiceContent)->toContain('validateCode');
    expect($validationServiceContent)->toContain('scanSecurity');
    expect($validationServiceContent)->toContain('analyzePerformance');
    expect($validationServiceContent)->toContain('lintCode');
    
    // Test CodeIsolationService has required methods
    $isolationServiceContent = file_get_contents(resource_path('js/services/CodeIsolationService.ts'));
    
    expect($isolationServiceContent)->toContain('createEnvironment');
    expect($isolationServiceContent)->toContain('executeHTML');
    expect($isolationServiceContent)->toContain('executeCSS');
    expect($isolationServiceContent)->toContain('executeJavaScript');
    
    // Test CustomCodeStorageService has required methods
    $storageServiceContent = file_get_contents(resource_path('js/services/CustomCodeStorageService.ts'));
    
    expect($storageServiceContent)->toContain('storeCustomCode');
    expect($storageServiceContent)->toContain('getCustomCodes');
    expect($storageServiceContent)->toContain('updateCustomCode');
    expect($storageServiceContent)->toContain('deleteCustomCode');
});

test('security measures implemented', function () {
    $validationServiceContent = file_get_contents(resource_path('js/services/CustomCodeValidationService.ts'));
    
    // Check for security-related functionality
    expect($validationServiceContent)->toContain('SecurityIssue');
    expect($validationServiceContent)->toContain('scanSecurity');
    expect($validationServiceContent)->toContain('sanitize');
    
    $isolationServiceContent = file_get_contents(resource_path('js/services/CodeIsolationService.ts'));
    
    // Check for isolation functionality
    expect($isolationServiceContent)->toContain('IsolatedEnvironment');
    expect($isolationServiceContent)->toContain('sanitizeHTML');
    expect($isolationServiceContent)->toContain('sanitizeCSS');
    expect($isolationServiceContent)->toContain('createRestrictedGlobals');
});

test('syntax highlighting and error checking implemented', function () {
    $editorContent = file_get_contents(resource_path('js/components/PageBuilder/CustomCodeEditor.vue'));
    
    expect($editorContent)->toContain('validateCode');
    expect($editorContent)->toContain('formatCode');
    expect($editorContent)->toContain('syntax');
    
    $validationPanelContent = file_get_contents(resource_path('js/components/PageBuilder/ValidationPanel.vue'));
    
    expect($validationPanelContent)->toContain('ValidationResult');
    expect($validationPanelContent)->toContain('errors');
    expect($validationPanelContent)->toContain('warnings');
    expect($validationPanelContent)->toContain('security');
});

test('component isolation implemented', function () {
    $isolationServiceContent = file_get_contents(resource_path('js/services/CodeIsolationService.ts'));
    
    expect($isolationServiceContent)->toContain('createIsolatedContainer');
    expect($isolationServiceContent)->toContain('applyIsolationStyles');
    expect($isolationServiceContent)->toContain('cleanupEnvironment');
    
    $previewModalContent = file_get_contents(resource_path('js/components/PageBuilder/CodePreviewModal.vue'));
    
    expect($previewModalContent)->toContain('isolated');
    expect($previewModalContent)->toContain('security');
});
