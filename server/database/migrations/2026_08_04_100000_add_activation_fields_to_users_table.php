<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add activation fields to users table for enterprise workflow.
     * Admin creates user without password → User activates via email link.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Activation token (UUID) for secure one-time activation
            $table->string('activation_token', 100)->nullable()->after('password_hash');
            
            // Activation token expiration timestamp (default: 24 hours)
            $table->timestamp('activation_token_expires_at')->nullable()->after('activation_token');
            
            // Activation status tracking
            $table->enum('activation_status', [
                'pending',      // Account created, waiting activation
                'activated',    // Successfully activated
                'expired',      // Activation link expired
                'deactivated'   // Account deactivated by admin
            ])->default('activated')->after('activation_token_expires_at');
            
            // Email verification timestamp (Laravel standard)
            $table->timestamp('email_verified_at')->nullable()->after('activation_status');
            
            // Add index for faster token lookups
            $table->index('activation_token');
            $table->index('activation_status');
        });

        // Make password_hash nullable for users pending activation
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_hash')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['activation_token']);
            $table->dropIndex(['activation_status']);
            
            $table->dropColumn([
                'activation_token',
                'activation_token_expires_at',
                'activation_status',
                'email_verified_at'
            ]);
        });

        // Restore password_hash to not nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_hash')->nullable(false)->change();
        });
    }
};
