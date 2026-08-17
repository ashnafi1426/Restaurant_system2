<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 50)->default('receptionist')->after('status');
            $table->text('special_requests')->nullable()->after('notes');
            $table->foreignUuid('reservation_id')->nullable()->change();
        });
    }
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['source', 'special_requests']);
            // Note: Reverting the nullable change on reservation_id would require more complex logic
        });
    }
};
