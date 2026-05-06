<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'admin',
                'email' =>'admin@example.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'status' => '勤務外'
            ],
            [
                'name' => 'user1',
                'email' =>'user1@example.com',
                'password' => bcrypt('password'),
                'is_admin' => false,
                'status' => '勤務外'
            ],
            [
                'name' => 'user2',
                'email' =>'user2@example.com',
                'password' => bcrypt('password'),
                'is_admin' => false,
                'status' => '勤務外'
            ]
            ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }
    }
}
