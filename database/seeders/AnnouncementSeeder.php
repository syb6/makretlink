<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@marketlink.test')->first();

        $announcements = [
            [
                'title' => 'Autumn Harvest Festival — Oct 12',
                'message' => 'Join us at Green Valley Community Market for the annual harvest festival: live music, tastings, and kids activities from 8am.',
                'status' => 'published',
                'day' => 27,
            ],
            [
                'title' => 'Pre-order cutoff now 24h before pickup',
                'message' => 'To give farmers better planning, pre-orders now close 24 hours before your chosen pickup slot.',
                'status' => 'published',
                'day' => 28,
            ],
            [
                'title' => 'New farmers joining Riverside',
                'message' => 'Three new stalls are joining the Riverside Farmers Exchange this month. Come say hello!',
                'status' => 'draft',
                'day' => 29,
            ],
        ];

        foreach ($announcements as $a) {
            $date = now()->setDay($a['day']);

            Announcement::create([
                'created_by'   => $admin?->id,
                'title'        => $a['title'],
                'message'      => $a['message'],
                'status'       => $a['status'],
                'published_at' => $a['status'] === 'published' ? $date : null,
                'created_at'   => $date,
                'updated_at'   => $date,
            ]);
        }
    }
}
