<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Env;
use App\Core\Service;
use App\Core\UsesApplication;
use App\Log\Log;
use ErrorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseService extends Service
{
    public function boot(): void
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            "driver" => env(Env::DB_DRIVER),
            "host" => env(Env::DB_HOST),
            "database" => env(Env::DB_NAME),
            "username" => env(Env::DB_USER),
            "password" => env(Env::DB_PASSWORD)
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}