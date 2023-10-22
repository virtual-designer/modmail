<?php

namespace App\Database;

use App\Core\UsesApplication;

abstract class Migration
{
    use UsesApplication;

    abstract public function up(): void;
    abstract public function down(): void;
}