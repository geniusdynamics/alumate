<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase;

abstract class DuskTestCase extends TestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    protected function setUp(): void
    {
        static::startChromeDriver();

        parent::setUp();
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless=new', // Use new headless mode
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            self::getWebDriverUrl(),
            $options
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     */
    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_SERVER['DUSK_HEADLESS']) && $_SERVER['DUSK_HEADLESS'] === 'false';
    }

    /**
     * Get the URL to the WebDriver server.
     */
    protected static function getWebDriverUrl(): string
    {
        return 'http://localhost:9515'; // ChromeDriver default port
    }
}