<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class outlet_week_schedules extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('outlet_week_schedules')->insert([
            ['week_no' => 1, 'day_name' => 'saturday', 'is_complete' => false],
            ['week_no' => 1, 'day_name' => 'sunday', 'is_complete' => false],
            ['week_no' => 1, 'day_name' => 'monday', 'is_complete' => false],
            ['week_no' => 1, 'day_name' => 'tuesday', 'is_complete' => false],
            ['week_no' => 1, 'day_name' => 'wednesday', 'is_complete' => false],
            ['week_no' => 1, 'day_name' => 'thursday', 'is_complete' => false],
            ['week_no' => 2, 'day_name' => 'saturday', 'is_complete' => false],
            ['week_no' => 2, 'day_name' => 'sunday', 'is_complete' => false],
            ['week_no' => 2, 'day_name' => 'monday', 'is_complete' => false],
            ['week_no' => 2, 'day_name' => 'tuesday', 'is_complete' => false],
            ['week_no' => 2, 'day_name' => 'wednesday', 'is_complete' => false],
            ['week_no' => 2, 'day_name' => 'thursday', 'is_complete' => false],
            ['week_no' => 3, 'day_name' => 'saturday', 'is_complete' => false],
            ['week_no' => 3, 'day_name' => 'sunday', 'is_complete' => false],
            ['week_no' => 3, 'day_name' => 'monday', 'is_complete' => false],
            ['week_no' => 3, 'day_name' => 'tuesday', 'is_complete' => false],
            ['week_no' => 3, 'day_name' => 'wednesday', 'is_complete' => false],
            ['week_no' => 3, 'day_name' => 'thursday', 'is_complete' => false],
            ['week_no' => 4, 'day_name' => 'saturday', 'is_complete' => false],
            ['week_no' => 4, 'day_name' => 'sunday', 'is_complete' => false],
            ['week_no' => 4, 'day_name' => 'monday', 'is_complete' => false],
            ['week_no' => 4, 'day_name' => 'tuesday', 'is_complete' => false],
            ['week_no' => 4, 'day_name' => 'wednesday', 'is_complete' => false],
            ['week_no' => 4, 'day_name' => 'thursday', 'is_complete' => false],
        ]);
    }
}
