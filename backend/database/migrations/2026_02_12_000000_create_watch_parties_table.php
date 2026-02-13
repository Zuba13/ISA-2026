<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_parties', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $blueprint->string('name');
            $blueprint->string('video_id')->nullable();
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_parties');
    }
};
