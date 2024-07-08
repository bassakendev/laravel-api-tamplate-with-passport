<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1  = User::create([
            'first_name' => 'User1',
            'last_name' => 'user1',
            'phone' => '66666666',
            'email' => 'user11@example.com',
            'password' => bcrypt('password'),
            'activation_token' => 'john',
        ]);

        $user1->wallet->add(150000.00);

        $user2  = User::create([
            'first_name' => 'User2',
            'last_name' => 'user2',
            'phone' => '66666666',
            'email' => 'user22@example.com',
            'password' => bcrypt('password'),
            'activation_token' => 'john',
        ]);

        $user2->wallet->add(45000.00);

        $user3  = User::create([
            'first_name' => 'User3',
            'last_name' => 'user3',
            'phone' => '66666666',
            'email' => 'user33@example.com',
            'referrer_id' => $user2->id,
            'password' => bcrypt('password'),
            'activation_token' => 'john',
        ]);

        $user3->wallet->add(50.00);
    }
}
