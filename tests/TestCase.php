<?php

namespace MustafaFares\SelectiveResponse\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use MustafaFares\SelectiveResponse\SelectiveResponseServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            SelectiveResponseServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup test environment
    }
}

