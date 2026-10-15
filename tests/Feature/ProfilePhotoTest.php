<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FarmerProfile;
use App\Services\ProfileImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_gives_initials_and_null_avatar_fallback_when_no_photo_exists(): void
    {
        $user = User::factory()->create(['name' => 'Ayesha Khan']);

        $this->assertSame('AK', $user->initials());
        $this->assertNull($user->avatarUrl());
    }

    public function test_current_user_api_uses_the_farmer_profile_photo_when_account_photo_is_empty(): void
    {
        $user = User::factory()->create(['name' => 'Ayesha Khan', 'role' => 'farmer']);
        FarmerProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Khan Farms',
            'contact_person' => 'Ayesha Khan',
            'address' => '1 Farm Road',
            'profile_image' => 'profiles/farmer-avatar.jpg',
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->getJson('/api/me')->assertOk();

        $this->assertSame($user->avatarUrl(), $response->json('user.avatar'));
        $this->assertSame(asset('storage/profiles/farmer-avatar.jpg'), $response->json('user.avatar'));
    }

    public function test_stores_and_replaces_photos_and_cleans_up_the_old_file(): void
    {
        Storage::fake('public');
        $service = new ProfileImageService();

        $first = $service->replace(
            UploadedFile::fake()->create('me.jpg', 100, 'image/jpeg'),
            null,
            'customer-test'
        );
        Storage::disk('public')->assertExists($first);

        $second = $service->replace(
            UploadedFile::fake()->create('me2.png', 100, 'image/png'),
            $first,
            'customer-test'
        );

        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first); // old photo removed
    }

    public function test_refuses_to_delete_paths_outside_managed_folders(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/evil.jpg', 'x');

        (new ProfileImageService())->delete('uploads/evil.jpg');

        Storage::disk('public')->assertExists('uploads/evil.jpg');
    }
}
