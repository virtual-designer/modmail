<?php

namespace App\Core;

trait UsesApplication
{
    protected readonly Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }
}