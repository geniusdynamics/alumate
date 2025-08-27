<?php

use App\Services\ComponentRenderService;

it('can instantiate ComponentRenderService', function () {
    $service = new ComponentRenderService;
    expect($service)->toBeInstanceOf(ComponentRenderService::class);
});
