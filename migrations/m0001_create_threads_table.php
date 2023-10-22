<?php

use App\Database\Migration;
use App\Log\Log;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class m0001_create_threads_table extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('threads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('userId');
            $table->string('channelId');
            $table->string('createdById');
            $table->boolean('isArchived')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->drop('threads');
    }
}