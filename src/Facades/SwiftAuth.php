<?php

namespace Teleurban\SwiftAuth\Facades;

use Illuminate\Support\Facades\Facade;

class SwiftAuth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'swift-auth';
    }
}
