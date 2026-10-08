<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Statistic;

class StatisticSeeder extends Seeder
{
    /**
     * Run the database seeds for studio statistics.
     */
    public function run(): void
    {
        $statistics = [
            [
                'key' => 'years_mastery',
                'value' => 14,
                'symbol' => '+',
                'label' => 'Years of Architectural Mastery',
                'sort_order' => 1,
            ],
            [
                'key' => 'projects_realized',
                'value' => 120,
                'symbol' => '+',
                'label' => 'Bespoke Projects Realized',
                'sort_order' => 2,
            ],
            [
                'key' => 'design_awards',
                'value' => 18,
                'symbol' => '',
                'label' => 'Prestigious Design Awards',
                'sort_order' => 3,
            ],
            [
                'key' => 'client_care',
                'value' => 100,
                'symbol' => '%',
                'label' => 'Dedicated Client Care',
                'sort_order' => 4,
            ],
        ];

        foreach ($statistics as $stat) {
            Statistic::updateOrCreate(['key' => $stat['key']], $stat);
        }
    }
}
