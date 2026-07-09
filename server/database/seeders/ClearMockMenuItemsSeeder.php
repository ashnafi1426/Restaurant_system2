<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearMockMenuItemsSeeder extends Seeder
{
    /**
     * Remove all mock menu items from database
     * 
     * These were placeholder items showing:
     * - Breakfast: 18 items
     * - Lunch: 24 items
     * - Dinner: 32 items
     * - Drinks: 45 items
     * - Dessert: 12 items
     */
    public function run(): void
    {
        try {
            // Get current counts before deletion
            $beforeCounts = [
                'total' => MenuItem::count(),
                'breakfast' => MenuItem::where('category', 'breakfast')->count(),
                'lunch' => MenuItem::where('category', 'lunch')->count(),
                'dinner' => MenuItem::where('category', 'dinner')->count(),
                'drinks' => MenuItem::where('category', 'drinks')->count(),
                'dessert' => MenuItem::where('category', 'dessert')->count(),
            ];

            echo "\n📊 Menu Items Before Deletion:\n";
            echo "   Total: {$beforeCounts['total']}\n";
            echo "   - Breakfast: {$beforeCounts['breakfast']}\n";
            echo "   - Lunch: {$beforeCounts['lunch']}\n";
            echo "   - Dinner: {$beforeCounts['dinner']}\n";
            echo "   - Drinks: {$beforeCounts['drinks']}\n";
            echo "   - Dessert: {$beforeCounts['dessert']}\n";

            // Disable foreign key checks to allow truncation
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Delete all menu items (will cascade to order_items via foreign key)
            // Note: This will also delete all order_items that reference these menu items
            MenuItem::truncate();
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            echo "\n✅ All menu items deleted from database\n";

            // Verify deletion
            $afterCount = MenuItem::count();
            echo "   Remaining items: {$afterCount}\n";

            // Show new statistics
            echo "\n📊 Menu Statistics After Deletion:\n";
            echo "   Total: {$afterCount}\n";
            echo "   - Breakfast: " . MenuItem::where('category', 'breakfast')->count() . "\n";
            echo "   - Lunch: " . MenuItem::where('category', 'lunch')->count() . "\n";
            echo "   - Dinner: " . MenuItem::where('category', 'dinner')->count() . "\n";
            echo "   - Drinks: " . MenuItem::where('category', 'drinks')->count() . "\n";
            echo "   - Dessert: " . MenuItem::where('category', 'dessert')->count() . "\n";

            echo "\n✅ Mock menu items cleared successfully!\n";
            echo "   You can now create real menu items via the API.\n\n";

        } catch (\Exception $e) {
            echo "\n❌ Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
