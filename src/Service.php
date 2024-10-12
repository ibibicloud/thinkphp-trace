<?php

namespace think\trace;

use think\Service as BaseService;

class Service extends BaseService
{
    public function register()
    {
        $this->app->middleware->add(TraceDebug::class);
    }
}
