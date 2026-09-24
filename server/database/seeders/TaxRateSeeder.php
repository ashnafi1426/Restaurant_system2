<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxRate;
use App\Models\Hotel;
use Illuminate\Support\Str;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hotels = Hotel::all();

        $defaultTaxes = [
            [
                'name' => 'VAT',
                'code' => 'VAT',
                'type' => 'vat',
                'rate' => 15.00,
                'applies_to' => 'food_beverage',
                'is_active' => true,
                'is_default' => true,
                'description' => 'Standard 15% Value Added Tax on food & beverages',
            ],
            [
                'name' => 'No Tax',
                'code' => 'NONE',
                'type' => 'percentage',
                'rate' => 0.00,
                'applies_to' => 'food_beverage',
                'is_active' => true,
                'is_default' => false,
                'description' => 'Zero-rated tax for tax-exempt items',
            ],
            [
                'name' => 'Service Charge',
                'code' => 'SERVICE',
                'type' => 'service',
                'rate' => 10.00,
                'applies_to' => 'service',
                'is_active' => true,
                'is_default' => false,
                'description' => 'Standard 10% Restaurant & Room Service Charge',
            ],
            [
                'name' => 'Municipal Tourism Tax',
                'code' => 'CITY',
                'type' => 'percentage',
                'rate' => 2.00,
                'applies_to' => 'room',
                'is_active' => true,
                'is_default' => false,
                'description' => 'City tourism development fee',
            ],
        ];

        if ($hotels->isEmpty()) {
            foreach ($defaultTaxes as $tax) {
                TaxRate::firstOrCreate(
                    ['code' => $tax['code'], 'hotel_id' => null],
                    array_merge($tax, ['id' => (string) Str::uuid()])
                );
            }
        } else {
            foreach ($hotels as $hotel) {
                foreach ($defaultTaxes as $tax) {
                    TaxRate::firstOrCreate(
                        ['code' => $tax['code'], 'hotel_id' => $hotel->id],
                        array_merge($tax, [
                            'id' => (string) Str::uuid(),
                            'hotel_id' => $hotel->id,
                        ])
                    );
                }
            }
        }
    }
}
