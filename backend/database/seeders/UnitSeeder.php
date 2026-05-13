<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['name' => 'Gram', 'abbreviation' => 'g'],
            ['name' => 'Liter', 'abbreviation' => 'L'],
            ['name' => 'MiliLiter', 'abbreviation' => 'ml'],
            ['name' => 'Pieces', 'abbreviation' => 'pcs'],
            ['name' => 'Box', 'abbreviation' => 'box'],
            ['name' => 'Botol', 'abbreviation' => 'btl'],
            ['name' => 'Porsi', 'abbreviation' => 'prs'],
            ['name' => 'Ikat', 'abbreviation' => 'ikt'],
            ['name' => 'Bungkus', 'abbreviation' => 'bks'],
        ];

        foreach ($units as $unit) {
            \App\Models\Unit::firstOrCreate(['abbreviation' => $unit['abbreviation']], ['name' => $unit['name']]);
        }
    }
}
