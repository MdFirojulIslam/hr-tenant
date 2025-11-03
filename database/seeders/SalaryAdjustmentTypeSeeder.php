<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SalaryAdjustmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('salary_adjustment_types')->insert([
            ['adjustment_name' => 'Pending Salary', 'adjustment_type' => '1', 'status' => 1],
            ['adjustment_name' => 'Overtime', 'adjustment_type' => '1', 'status' => 1],
            ['adjustment_name' => 'Advance', 'adjustment_type' => '0', 'status' => 1],
        ]);
    }
};
