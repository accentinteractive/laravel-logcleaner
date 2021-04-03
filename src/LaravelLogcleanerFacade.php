<?php

namespace Accentinteractive\LaravelLogcleaner;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Accentinteractive\LaravelLogcleaner\Skeleton\SkeletonClass
 */
class LaravelLogcleanerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-logcleaner';
    }
}
