<?php

namespace Scryba\GoogleDriveFilesystem\Tests;

use Orchestra\Testbench\TestCase;
use Scryba\GoogleDriveFilesystem\Providers\GoogleDriveServiceProvider;

class ExampleTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [GoogleDriveServiceProvider::class];
    }

    public function test_service_provider_registers()
    {
        $this->assertTrue(class_exists(GoogleDriveServiceProvider::class));
    }
} 