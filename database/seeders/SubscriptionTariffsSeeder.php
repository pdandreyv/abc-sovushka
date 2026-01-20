<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionTariffsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('subscription_tariffs')->insert([
            ['title' => '1 месяц', 'price' => 224.00, 'days' => 30, 'rating' => 1, 'is_visible' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '3 месяца', 'price' => 648.00, 'days' => 91, 'rating' => 2, 'is_visible' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '6 месяцев', 'price' => 1296.00, 'days' => 183, 'rating' => 3, 'is_visible' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '12 месяцев', 'price' => 2592.00, 'days' => 365, 'rating' => 4, 'is_visible' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
