<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_notifications', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('manager_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');

            $table->text('message');

            $table->string('type');
            // info
            // warning
            // success
            // danger

            $table->boolean('is_read')
                ->default(false);

            $table->timestamp('read_at')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_notifications');
    }
};