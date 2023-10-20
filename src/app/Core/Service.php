<?php

namespace App\Core;

abstract class Service
{
    use UsesApplication;

    public function boot(): void {}
}