<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Services\ComponentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->componentService = app(ComponentService::class);
});

describe('Media Component Creation', function () {
    it('can create an image gallery component', function () {
        $config = [
            'type' => 'image-gallery',
            'title' => 'Alu