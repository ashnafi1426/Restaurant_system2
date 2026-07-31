<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the delivery_logs table: change order_id from unsignedBigInteger to uuid
        if (Schema::hasTable('delivery_logs')) {
            // Disable foreign key checks to allow modifications
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Get list of foreign keys to determine if order_id_foreign exists
            $result = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='delivery_logs' AND COLUMN_NAME='order_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
            
            // Drop foreign key if it exists
            if (!empty($result)) {
                $fkName = $result[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE delivery_logs DROP FOREIGN KEY {$fkName}");
            }
            
            // Modify the column type to UUID (VARCHAR(36))
            DB::statement('ALTER TABLE delivery_logs MODIFY order_id CHAR(36) NOT NULL');
            
            // Add the foreign key constraint back
            DB::statement('ALTER TABLE delivery_logs ADD CONSTRAINT delivery_logs_order_id_foreign FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE');
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('delivery_logs')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Check if foreign key exists
            $result = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='delivery_logs' AND COLUMN_NAME='order_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
            
            if (!empty($result)) {
                $fkName = $result[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE delivery_logs DROP FOREIGN KEY {$fkName}");
            }
            
            // Revert to unsignedBigInteger
            DB::statement('ALTER TABLE delivery_logs MODIFY order_id BIGINT UNSIGNED NOT NULL');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
