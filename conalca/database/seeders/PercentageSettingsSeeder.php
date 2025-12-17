<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PercentageSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        \App\Models\PercentageSetting::firstOrCreate(
            ['id' => 1],
            [
                'min_percentage' => 17,
                'avg_percentage' => 24,
                'max_percentage' => 32,
                'use_custom_percentages' => false,
            ]
        );
    }
}
