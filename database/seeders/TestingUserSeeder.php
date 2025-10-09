<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestingUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Test',
            'last_name' => 'Staff',
            'full_name' => 'Test Staff',
            'username' => 'TestStaff',
            'email' => 'teststaff@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])->assignRole('staff');

        User::create([
            'first_name' => 'Test',
            'last_name' => 'customer',
            'full_name' => 'Test Customer',
            'username' => 'TestCustomer',
            'email' => 'testcustomer@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])->assignRole('customer');
    }
}
