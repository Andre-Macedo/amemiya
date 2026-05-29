<?php

namespace Modules\Metrology\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Metrology\Models\Material;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            ['name' => 'Steel', 'cte' => 11.5, 'category' => 'Metal'],
            ['name' => 'Stainless Steel (304)', 'cte' => 16.0, 'category' => 'Metal'],
            ['name' => 'Stainless Steel (416)', 'cte' => 9.9, 'category' => 'Metal'],
            ['name' => 'Tungsten Carbide', 'cte' => 5.0, 'category' => 'Ceramic'],
            ['name' => 'Ceramic (Zirconia)', 'cte' => 9.5, 'category' => 'Ceramic'],
            ['name' => 'Aluminum', 'cte' => 23.0, 'category' => 'Metal'],
            ['name' => 'Brass', 'cte' => 19.0, 'category' => 'Metal'],
            ['name' => 'Glass', 'cte' => 8.0, 'category' => 'Glass'],
            ['name' => 'Granite', 'cte' => 6.5, 'category' => 'Stone'],
            ['name' => 'Invar', 'cte' => 1.2, 'category' => 'Metal'],
        ];

        foreach ($materials as $material) {
            Material::firstOrCreate(['name' => $material['name']], $material);
        }
    }
}
