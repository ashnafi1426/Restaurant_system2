<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_announcements', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('manager_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');

            $table->longText('content');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_announcements');
    }
};