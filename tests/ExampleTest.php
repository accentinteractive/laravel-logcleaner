<?php

namespace Accentinteractive\LaravelLogcleaner\Tests;

use Orchestra\Testbench\TestCase;
use Accentinteractive\LaravelLogcleaner\LaravelLogcleanerServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LaravelLogcleanerServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
