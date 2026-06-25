<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->enum('role', [
                'admin',
                'receptionist',
                'cashier',
                'manager',
                'chef'
            ]);
            $table->boolean('is_active')
                ->default(true);
            $table->timestamp('deleted_at')
                ->nullable();
            $table->timestamp('last_login')
                ->nullable();
            $table->timestamps();
            $table->index('email');
            $table->index('role');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
