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

        // All seeders are idempotent (updateOrCreate by unique email) so the
        // Railway pre-deploy step can safely run db:seed on every deploy.
        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@marketlink.test'],
            [
                'name' => 'Alex Admin',
                'email' => 'admin@marketlink.test',
                'phone' => '555-0100',
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );

        // Farmers
        $farmerData = [
            ['Sarah Greenfield', 'farmer1@marketlink.test', 'Greenfield Gardens', 'Sarah Greenfield', 'Family-run organic vegetable garden in the valley for over 20 years.', 'approved'],
            ['Marcus Orchard', 'farmer2@marketlink.test', 'Orchard Lane Fruits', 'Marcus Orchard', 'Seasonal stone fruit, apples and berries from our hillside orchard.', 'approved'],
            ['Elena Moo', 'farmer3@marketlink.test', 'Meadow Dairy', 'Elena Moo', 'Small-batch cheeses, yoghurts and fresh milk from grass-fed cows.', 'approved'],
            ['Tom Baker', 'farmer4@marketlink.test', 'Hearth & Grain Bakery', 'Tom Baker', 'Sourdough, pastries and artisan baked goods baked before dawn.', 'approved'],
            ['Nina Sprout', 'farmer5@marketlink.test', 'Sprout House Microgreens', 'Nina Sprout', 'Microgreens, herbs and edible flowers grown in our greenhouse.', 'pending'],
            // Karachi-based growers for a fuller, local-feeling marketplace
            ['Aisha Indus', 'farmer6@marketlink.test', 'Indus Greens', 'Aisha Indus', 'Riverbank greens and seasonal sabzi from our Indus-side farm near Karachi.', 'approved'],
            ['Yousuf Thar', 'farmer7@marketlink.test', 'Thar Honey Co.', 'Yousuf Thar', 'Wild desert honey and traditional preserves harvested by Thar families.', 'approved'],
            ['Zainab Malir', 'farmer8@marketlink.test', 'Malir Date Farm', 'Zainab Malir', 'Aseel dates, tropical fruits and chutneys from Malir’s historic orchards.', 'approved'],
            ['Omar Gadap', 'farmer9@marketlink.test', 'Gadap Veggie House', 'Omar Gadap', 'Daily-fresh vegetables trucked in from Gadap, Karachi’s vegetable belt.', 'pending'],
        ];

        $farmers = [];
        foreach ($farmerData as [$name, $email, $business, $contact, $desc, $status]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => '555-02'.random_int(10, 99),
                    'role' => 'farmer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => $password,
                ]
            );

            $farmers[] = FarmerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $business,
                    'contact_person' => $contact,
                    'address' => rand(10, 99).' Farm Belt Road, Malir, Karachi',
                    'description' => $desc,
                    'approval_status' => $status,
                    'approved_at' => $status === 'approved' ? now()->subDays(rand(5, 60)) : null,
                    'approved_by' => $status === 'approved' ? $admin->id : null,
                ]
            );
        }

        // Customers
        $customerData = [
            ['Oliver Shopper', 'customer1@marketlink.test', '12 Maple Street, Clifton, Karachi'],
            ['Priya Fresh', 'mysidtuaham@gmail.com', '48 Birch Avenue, PECHS, Karachi'],
            ['Diego Mercado', 'customer3@marketlink.test', '7 Cedar Lane, Nazimabad, Karachi'],
            ['Emma Weekend', 'customer4@marketlink.test', '230 Oak Drive, Bath Island, Karachi'],
            ['Hamza Saddar', 'customer5@marketlink.test', '14 Preedy Street, Saddar, Karachi'],
            ['Sana Gulshan', 'customer6@marketlink.test', 'Block 13-C, Gulshan-e-Iqbal, Karachi'],
            ['Bilal Clifton', 'customer7@marketlink.test', 'Khayaban-e-Seher, DHA Phase 6, Karachi'],
        ];

        foreach ($customerData as [$name, $email, $address]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => '555-03'.random_int(10, 99),
                    'role' => 'customer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => $password,
                ]
            );

            CustomerProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['address' => $address]
            );
        }

        // One suspended customer so admins have something to manage
        $suspended = User::updateOrCreate(
            ['email' => 'suspended@marketlink.test'],
            [
                'name' => 'Sam Suspended',
                'phone' => '555-0399',
                'role' => 'customer',
                'status' => 'suspended',
                'password' => $password,
            ]
        );
        CustomerProfile::updateOrCreate(
            ['user_id' => $suspended->id],
            ['address' => '1 Trouble Lane, Nowhere']
        );
    }
}
