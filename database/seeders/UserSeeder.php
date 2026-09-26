<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // Admin
        $admin = User::create([
            'name' => 'Alex Admin',
            'email' => 'admin@marketlink.test',
            'phone' => '555-0100',
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
            'password' => $password,
        ]);

        // Farmers
        $farmerData = [
            ['Sarah Greenfield', 'farmer1@marketlink.test', 'Greenfield Gardens', 'Sarah Greenfield', 'Family-run organic vegetable garden in the valley for over 20 years.', 'approved'],
            ['Marcus Orchard', 'farmer2@marketlink.test', 'Orchard Lane Fruits', 'Marcus Orchard', 'Seasonal stone fruit, apples and berries from our hillside orchard.', 'approved'],
            ['Elena Moo', 'farmer3@marketlink.test', 'Meadow Dairy', 'Elena Moo', 'Small-batch cheeses, yoghurts and fresh milk from grass-fed cows.', 'approved'],
            ['Tom Baker', 'farmer4@marketlink.test', 'Hearth & Grain Bakery', 'Tom Baker', 'Sourdough, pastries and artisan baked goods baked before dawn.', 'approved'],
            ['Nina Sprout', 'farmer5@marketlink.test', 'Sprout House Microgreens', 'Nina Sprout', 'Microgreens, herbs and edible flowers grown in our greenhouse.', 'pending'],
        ];

        $farmers = [];
        foreach ($farmerData as [$name, $email, $business, $contact, $desc, $status]) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => '555-02'.random_int(10, 99),
                'role' => 'farmer',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => $password,
            ]);

            $farmers[] = FarmerProfile::create([
                'user_id' => $user->id,
                'business_name' => $business,
                'contact_person' => $contact,
                'address' => rand(10, 99).' Rural Route '.chr(64 + rand(1, 20)).', Green Valley',
                'description' => $desc,
                'approval_status' => $status,
                'approved_at' => $status === 'approved' ? now()->subDays(rand(5, 60)) : null,
                'approved_by' => $status === 'approved' ? $admin->id : null,
            ]);
        }

        // Customers
        $customerData = [
            ['Oliver Shopper', 'customer1@marketlink.test', '12 Maple Street, Green Valley'],
            ['Priya Fresh', 'customer2@marketlink.test', '48 Birch Avenue, Green Valley'],
            ['Diego Mercado', 'customer3@marketlink.test', '7 Cedar Lane, Riverside'],
            ['Emma Weekend', 'customer4@marketlink.test', '230 Oak Drive, Green Valley'],
        ];

        foreach ($customerData as [$name, $email, $address]) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => '555-03'.random_int(10, 99),
                'role' => 'customer',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => $password,
            ]);

            CustomerProfile::create([
                'user_id' => $user->id,
                'address' => $address,
            ]);
        }

        // One suspended customer so admins have something to manage
        $suspended = User::create([
            'name' => 'Sam Suspended',
            'email' => 'suspended@marketlink.test',
            'phone' => '555-0399',
            'role' => 'customer',
            'status' => 'suspended',
            'password' => $password,
        ]);
        CustomerProfile::create([
            'user_id' => $suspended->id,
            'address' => '1 Trouble Lane, Nowhere',
        ]);
    }
}
