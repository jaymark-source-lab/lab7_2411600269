<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupplierSeeder extends Seeder
{
    // create suppliers from the product supplier names and link them
    public function run(): void
    {
        // extra details per supplier (fictional contacts)
        $details = [
            'Optimum Nutrition' => ['Maria Santos', '+63 917 555 0101', 'Net 30', 7, 500],
            'MuscleTech'        => ['Carlo Reyes', '+63 917 555 0102', 'Net 30', 10, 300],
            'Cellucor'          => ['Ana Cruz', '+63 917 555 0103', 'Net 15', 14, 250],
            'Xtend'             => ['Paolo Garcia', '+63 917 555 0104', 'COD', 5, 0],
            'Dymatize'          => ['Liza Mendoza', '+63 917 555 0105', 'Net 30', 12, 400],
            'Quest Nutrition'   => ['Mark Villanueva', '+63 917 555 0106', 'Net 15', 6, 200],
            'Centrum'           => ['Grace Tan', '+63 917 555 0107', 'Net 30', 4, 150],
            "Nature's Bounty"   => ['Jose Ramos', '+63 917 555 0108', 'Net 30', 5, 150],
            'NOW Foods'         => ['Kim Bautista', '+63 917 555 0109', 'Net 45', 21, 300],
            'Nordic Naturals'   => ['Rhea Flores', '+63 917 555 0110', 'Net 30', 9, 200],
            'Rogue Fitness'     => ['Daniel Lim', '+63 917 555 0111', 'Net 30', 14, 1000],
            'Nike'              => ['Sophia Navarro', '+63 917 555 0112', 'Net 60', 7, 500],
            'Sweet Sweat'       => ['Miguel Torres', '+63 917 555 0113', 'COD', 3, 0],
            'Harbinger'         => ['Bea Castillo', '+63 917 555 0114', 'Net 30', 8, 150],
            'BlenderBottle'     => ['Luis Aquino', '+63 917 555 0115', 'Net 15', 5, 100],
        ];

        $groups = Product::whereNotNull('supplier')->get()->groupBy('supplier');

        foreach ($groups as $name => $products) {
            [$contact, $phone, $terms, $lead, $minimum] = $details[$name] ?? [null, null, 'Net 30', 7, 0];

            $supplier = Supplier::updateOrCreate(
                ['name' => $name],
                [
                    'contact_person'       => $contact,
                    'email'                => Str::slug($name) . '@supplier.test',
                    'phone'                => $phone,
                    'address'              => 'Metro Manila, Philippines',
                    'payment_terms'        => $terms,
                    'lead_time_days'       => $lead,
                    'minimum_order_amount' => $minimum,
                    'is_active'            => true,
                ]
            );

            // link each product with a wholesale cost (60% of selling price)
            foreach ($products as $product) {
                $supplier->products()->syncWithoutDetaching([
                    $product->id => [
                        'unit_cost'              => round($product->unit_price * 0.6, 2),
                        'minimum_order_quantity' => 5,
                        'is_preferred'           => true,
                    ],
                ]);
            }
        }
    }
}