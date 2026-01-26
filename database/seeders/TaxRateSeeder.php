<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxRate;
use Carbon\Carbon;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing tax rates
        TaxRate::truncate();

        $now = Carbon::now();

        // US State Tax Rates
        $usStates = [
            ['state' => 'AL', 'rate' => 0.04, 'name' => 'Alabama Sales Tax'],
            ['state' => 'AK', 'rate' => 0.00, 'name' => 'Alaska (No State Sales Tax)'],
            ['state' => 'AZ', 'rate' => 0.056, 'name' => 'Arizona Sales Tax'],
            ['state' => 'AR', 'rate' => 0.065, 'name' => 'Arkansas Sales Tax'],
            ['state' => 'CA', 'rate' => 0.0725, 'name' => 'California Sales Tax'],
            ['state' => 'CO', 'rate' => 0.029, 'name' => 'Colorado Sales Tax'],
            ['state' => 'CT', 'rate' => 0.0635, 'name' => 'Connecticut Sales Tax'],
            ['state' => 'DE', 'rate' => 0.00, 'name' => 'Delaware (No State Sales Tax)'],
            ['state' => 'FL', 'rate' => 0.06, 'name' => 'Florida Sales Tax'],
            ['state' => 'GA', 'rate' => 0.04, 'name' => 'Georgia Sales Tax'],
            ['state' => 'HI', 'rate' => 0.04, 'name' => 'Hawaii Sales Tax'],
            ['state' => 'ID', 'rate' => 0.06, 'name' => 'Idaho Sales Tax'],
            ['state' => 'IL', 'rate' => 0.0625, 'name' => 'Illinois Sales Tax'],
            ['state' => 'IN', 'rate' => 0.07, 'name' => 'Indiana Sales Tax'],
            ['state' => 'IA', 'rate' => 0.06, 'name' => 'Iowa Sales Tax'],
            ['state' => 'KS', 'rate' => 0.065, 'name' => 'Kansas Sales Tax'],
            ['state' => 'KY', 'rate' => 0.06, 'name' => 'Kentucky Sales Tax'],
            ['state' => 'LA', 'rate' => 0.0445, 'name' => 'Louisiana Sales Tax'],
            ['state' => 'ME', 'rate' => 0.055, 'name' => 'Maine Sales Tax'],
            ['state' => 'MD', 'rate' => 0.06, 'name' => 'Maryland Sales Tax'],
            ['state' => 'MA', 'rate' => 0.0625, 'name' => 'Massachusetts Sales Tax'],
            ['state' => 'MI', 'rate' => 0.06, 'name' => 'Michigan Sales Tax'],
            ['state' => 'MN', 'rate' => 0.06875, 'name' => 'Minnesota Sales Tax'],
            ['state' => 'MS', 'rate' => 0.07, 'name' => 'Mississippi Sales Tax'],
            ['state' => 'MO', 'rate' => 0.04225, 'name' => 'Missouri Sales Tax'],
            ['state' => 'MT', 'rate' => 0.00, 'name' => 'Montana (No State Sales Tax)'],
            ['state' => 'NE', 'rate' => 0.055, 'name' => 'Nebraska Sales Tax'],
            ['state' => 'NV', 'rate' => 0.0685, 'name' => 'Nevada Sales Tax'],
            ['state' => 'NH', 'rate' => 0.00, 'name' => 'New Hampshire (No State Sales Tax)'],
            ['state' => 'NJ', 'rate' => 0.06625, 'name' => 'New Jersey Sales Tax'],
            ['state' => 'NM', 'rate' => 0.05125, 'name' => 'New Mexico Sales Tax'],
            ['state' => 'NY', 'rate' => 0.08, 'name' => 'New York Sales Tax'],
            ['state' => 'NC', 'rate' => 0.0475, 'name' => 'North Carolina Sales Tax'],
            ['state' => 'ND', 'rate' => 0.05, 'name' => 'North Dakota Sales Tax'],
            ['state' => 'OH', 'rate' => 0.0575, 'name' => 'Ohio Sales Tax'],
            ['state' => 'OK', 'rate' => 0.045, 'name' => 'Oklahoma Sales Tax'],
            ['state' => 'OR', 'rate' => 0.00, 'name' => 'Oregon (No State Sales Tax)'],
            ['state' => 'PA', 'rate' => 0.06, 'name' => 'Pennsylvania Sales Tax'],
            ['state' => 'RI', 'rate' => 0.07, 'name' => 'Rhode Island Sales Tax'],
            ['state' => 'SC', 'rate' => 0.06, 'name' => 'South Carolina Sales Tax'],
            ['state' => 'SD', 'rate' => 0.045, 'name' => 'South Dakota Sales Tax'],
            ['state' => 'TN', 'rate' => 0.07, 'name' => 'Tennessee Sales Tax'],
            ['state' => 'TX', 'rate' => 0.0625, 'name' => 'Texas Sales Tax'],
            ['state' => 'UT', 'rate' => 0.0485, 'name' => 'Utah Sales Tax'],
            ['state' => 'VT', 'rate' => 0.06, 'name' => 'Vermont Sales Tax'],
            ['state' => 'VA', 'rate' => 0.053, 'name' => 'Virginia Sales Tax'],
            ['state' => 'WA', 'rate' => 0.065, 'name' => 'Washington Sales Tax'],
            ['state' => 'WV', 'rate' => 0.06, 'name' => 'West Virginia Sales Tax'],
            ['state' => 'WI', 'rate' => 0.05, 'name' => 'Wisconsin Sales Tax'],
            ['state' => 'WY', 'rate' => 0.04, 'name' => 'Wyoming Sales Tax'],
        ];

        // Create US state tax rates
        foreach ($usStates as $stateData) {
            TaxRate::create([
                'country' => 'US',
                'state' => $stateData['state'],
                'city' => null,
                'postal_code' => null,
                'rate' => $stateData['rate'],
                'type' => 'sales_tax',
                'name' => $stateData['name'],
                'description' => 'State sales tax for ' . $stateData['state'],
                'is_active' => true,
                'effective_from' => $now->copy()->subYear(),
                'effective_to' => null,
                'priority' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // International Tax Rates
        $internationalRates = [
            [
                'country' => 'CA',
                'rate' => 0.05,
                'type' => 'gst',
                'name' => 'Canadian GST',
                'description' => 'Canadian Goods and Services Tax (simplified)',
                'priority' => 50
            ],
            [
                'country' => 'GB',
                'rate' => 0.20,
                'type' => 'vat',
                'name' => 'UK VAT',
                'description' => 'United Kingdom Value Added Tax',
                'priority' => 50
            ],
            [
                'country' => 'IN',
                'rate' => 0.18,
                'type' => 'gst',
                'name' => 'Indian GST',
                'description' => 'Indian Goods and Services Tax (standard rate)',
                'priority' => 50
            ],
            [
                'country' => 'DE',
                'rate' => 0.19,
                'type' => 'vat',
                'name' => 'German VAT',
                'description' => 'German Value Added Tax',
                'priority' => 50
            ],
            [
                'country' => 'FR',
                'rate' => 0.20,
                'type' => 'vat',
                'name' => 'French VAT',
                'description' => 'French Value Added Tax',
                'priority' => 50
            ],
            [
                'country' => 'AU',
                'rate' => 0.10,
                'type' => 'gst',
                'name' => 'Australian GST',
                'description' => 'Australian Goods and Services Tax',
                'priority' => 50
            ],
        ];

        // Create international tax rates
        foreach ($internationalRates as $rateData) {
            TaxRate::create([
                'country' => $rateData['country'],
                'state' => null,
                'city' => null,
                'postal_code' => null,
                'rate' => $rateData['rate'],
                'type' => $rateData['type'],
                'name' => $rateData['name'],
                'description' => $rateData['description'],
                'is_active' => true,
                'effective_from' => $now->copy()->subYear(),
                'effective_to' => null,
                'priority' => $rateData['priority'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Tax rates seeded successfully!');
        $this->command->info('Created ' . TaxRate::count() . ' tax rate records.');
    }
}
