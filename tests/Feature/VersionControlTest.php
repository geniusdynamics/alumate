<?php

use App\Models\LandingPage;
use App\Models\PageVersion;
use App\Models\User;
use App\Services\VersionControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->page = LandingPage::factory()->create([
        'tenant_id' => $this->user->tenant_id,
    ]);
    $this->versionControlService = app(VersionControlService::class);
});

it('can create a new version', function () {
    $grapeJSData = [
        'html' => '<div>Test content</div>',
        'css' => '.test { color: red; }',
        'components' => [],
    ];

    $version = $this->versionControlService->createVersion(
        $this->page,
        $grapeJSData,
        $this->user,
        'Initial version'
    );

    expect($version)->toBeInstanceOf(PageVersion::class);
    expect($version->version_number)->toBe(1);
    expect($version->grapejs_data)->toBe($grapeJSData);
    expect($version->change_summary)->toBe('Initial version');
    expect($version->created_by)->toBe($this->user->id);
    expect($version->is_published)->toBeFalse();
});

it('can get version history', function () {
    // Create multiple versions
    $grapeJSData1 = ['html' => '<div>Version 1</div>'];
    $grapeJSData2 = ['html' => '<div>Version 2</div>'];

    $this->versionControlService->createVersion($this->page, $grapeJSData1, $this->user, 'Version 1');
    $this->versionControlService->createVersion($this->page, $grapeJSData2, $this->user, 'Version 2');

    $history = $this->versionControlService->getVersionHistory($this->page);

    expect($history)->toHaveCount(2);
    expect($history->first()->version_number)->toBe(2); // Latest first
    expect($history->last()->version_number)->toBe(1);
});

it('can rollback to a previous version', function () {
    // Create initial version
    $grapeJSData1 = ['html' => '<div>Version 1</div>'];
    $grapeJSData2 = ['html' => '<div>Version 2</div>'];

    $version1 = $this->versionControlService->createVersion($this->page, $grapeJSData1, $this->user, 'Version 1');
    $this->versionControlService->createVersion($this->page, $grapeJSData2, $this->user, 'Version 2');

    // Rollback to version 1
    $rollbackVersion = $this->versionControlService->rollbackToVersion($this->page, 1, $this->user);

    expect($rollbackVersion->version_number)->toBe(3); // New version created
    expect($rollbackVersion->grapejs_data)->toBe($grapeJSData1); // Same data as version 1
    expect($rollbackVersion->change_summary)->toContain('Rolled back to version 1');

    // Check that page was updated
    $this->page->refresh();
    expect($this->page->configuration)->toBe($grapeJSData1);
});

it('can publish a version', function () {
    $grapeJSData = ['html' => '<div>Test content</div>'];
    $version = $this->versionControlService->createVersion($this->page, $grapeJSData, $this->user, 'Test version');

    $publishedVersion = $this->versionControlService->publishVersion($version);

    expect($publishedVersion->is_published)->toBeTrue();
    expect($publishedVersion->published_at)->not->toBeNull();

    // Check that page was updated with published version data
    $this->page->refresh();
    expect($this->page->configuration)->toBe($grapeJSData);
});

it('can auto-save versions', function () {
    $grapeJSData = ['html' => '<div>Auto-save content</div>'];

    $autoSaveVersion = $this->versionControlService->autoSaveVersion($this->page, $grapeJSData, $this->user);

    expect($autoSaveVersion->change_summary)->toContain('Auto-save');
    expect($autoSaveVersion->metadata['auto_save'])->toBeTrue();
});

it('can compare versions', function () {
    $grapeJSData1 = ['html' => '<div>Version 1</div>', 'css' => '.v1 { color: red; }'];
    $grapeJSData2 = ['html' => '<div>Version 2</div>', 'css' => '.v2 { color: blue; }'];

    $version1 = $this->versionControlService->createVersion($this->page, $grapeJSData1, $this->user, 'Version 1');
    $version2 = $this->versionControlService->createVersion($this->page, $grapeJSData2, $this->user, 'Version 2');

    $comparison = $this->versionControlService->compareVersions($version1, $version2);

    expect($comparison)->toHaveKeys(['version_1', 'version_2', 'differences']);
    expect($comparison['version_1']['number'])->toBe(1);
    expect($comparison['version_2']['number'])->toBe(2);
    expect($comparison['differences'])->toHaveKeys(['html', 'css']);
});

it('only allows one published version per page', function () {
    $grapeJSData1 = ['html' => '<div>Version 1</div>'];
    $grapeJSData2 = ['html' => '<div>Version 2</div>'];

    $version1 = $this->versionControlService->createVersion($this->page, $grapeJSData1, $this->user, 'Version 1');
    $version2 = $this->versionControlService->createVersion($this->page, $grapeJSData2, $this->user, 'Version 2');

    // Publish version 1
    $this->versionControlService->publishVersion($version1);

    // Publish version 2
    $this->versionControlService->publishVersion($version2);

    // Check that only version 2 is published
    $version1->refresh();
    $version2->refresh();

    expect($version1->is_published)->toBeFalse();
    expect($version2->is_published)->toBeTrue();
});