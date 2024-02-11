<?php

namespace Stillat\Relationships\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Stillat\Relationships\Events\EventStack as EventStackConcrete;

/**
 * @method static void increment()
 * @method static void decrement()
 * @method static int count()
 */
class EventStack extends Facade
{
    public static function getFacadeAccessor()
    {
        return EventStackConcrete::class;
    }
}
