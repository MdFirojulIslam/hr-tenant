<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;



class NotificationTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $result = DB::table('notification_types')->insert([
            [
                'code' => 'Tasks',
                'name' => 'task_create',
                'category' => 'tasks',
                'priority' => 'high',
                'is_active' => true
            ],
            [
                'code' => 'Tasks',
                'name' => 'task_update',
                'category' => 'tasks',
                'priority' => 'high',
                'is_active' => true
            ],
            [
                'code' => 'Tasks',
                'name' => 'new_checklists_create',
                'category' => 'tasks',
                'priority' => 'high',
                'is_active' => true
            ],
            [
                'code' => 'Tasks',
                'name' => 'tasks_complete',
                'category' => 'tasks',
                'priority' => 'high',
                'is_active' => true
            ],
            [
                'code' => 'Tasks',
                'name' => 'updated_performs',
                'category' => 'tasks',
                'priority' => 'high',
                'is_active' => true
            ],
        ]);
    }
}
