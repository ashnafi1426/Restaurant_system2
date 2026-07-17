<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Add category_id column after category (for new foreign key)
            $table->uuid('category_id')->nullable()->after('category');
            
            // Add foreign key constraint
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('restrict');
            
            // Add index for faster queries
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
