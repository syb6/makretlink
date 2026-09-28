<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers every role's profile update end to end, including the missing /
 * soft-deleted profile-row edge cases that used to 500 the page.
 */
class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $user = User::create([
            'name' => 'Test '.ucfirst($role),
            'email' => $role.'-'.uniqid().'@test.dev',
            'phone' => '123456',
            'role' => $role,
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        if ($role === 'customer') {
            $user->customerProfile()->create(['address' => 'Old Address']);
        }

        if ($role === 'farmer') {
            $user->farmerProfile()->create([
                'business_name' => 'Old Farm',
                'contact_person' => 'Old Person',
                'address' => 'Old Farm Road',
                'approval_status' => 'approved',
            ]);
        }

        return $user;
    }

    public function test_customer_can_update_profile_name_phone_address_password_and_photo(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('customer');

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'New Name',
            'phone' => '999888',
            'address' => 'New Address 2',
            'profile_image' => UploadedFile::fake()->image('me.jpg', 100, 100),
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('999888', $user->phone);
        $this->assertTrue(Hash::check('newpassword', $user->password));
        $this->assertSame('New Address 2', $user->customerProfile->address);
        $this->assertNotNull($user->customerProfile->profile_image);
        Storage::disk('public')->assertExists($user->customerProfile->profile_image);
    }

    public function test_customer_update_without_password_or_photo_keeps_existing_values(): void
    {
        $user = $this->makeUser('customer');
        $oldHash = $user->password;

        $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'Same Name',
            'phone' => '111222',
            'address' => 'Changed Address',
        ])->assertRedirect()->assertSessionHas('success');

        $user->refresh();
        $this->assertSame($oldHash, $user->password);
        $this->assertNull($user->customerProfile->profile_image);
        $this->assertSame('Changed Address', $user->customerProfile->address);
    }

    public function test_farmer_can_update_stall_profile_and_photo(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('farmer');

        $this->actingAs($user)->post(route('farmer.profile.update'), [
            'business_name' => 'New Farm',
            'contact_person' => 'New Person',
            'address' => 'New Farm Road',
            'description' => 'Fresh produce daily',
            'profile_image' => UploadedFile::fake()->image('farm.jpg', 100, 100),
        ])->assertRedirect()->assertSessionHas('success');

        $farmer = $user->farmerProfile->refresh();
        $this->assertSame('New Farm', $farmer->business_name);
        $this->assertSame('New Person', $farmer->contact_person);
        $this->assertSame('New Farm Road', $farmer->address);
        $this->assertSame('Fresh produce daily', $farmer->description);
        $this->assertNotNull($farmer->profile_image);
        Storage::disk('public')->assertExists($farmer->profile_image);
    }

    public function test_admin_can_update_profile_and_photo(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('admin');

        $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => 'Admin New',
            'phone' => '555000',
            'profile_photo' => UploadedFile::fake()->image('admin.jpg', 100, 100),
        ])->assertRedirect()->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Admin New', $user->name);
        $this->assertSame('555000', $user->phone);
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_admin_can_change_password_from_profile(): void
    {
        $user = $this->makeUser('admin');

        $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'password' => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Hash::check('brandnewpass', $user->refresh()->password));
    }

    public function test_customer_profile_page_works_when_profile_row_is_missing(): void
    {
        $user = $this->makeUser('customer');
        $user->customerProfile->delete(); // soft-delete the row

        // Edit page renders instead of erroring on a null profile.
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();

        // Saving recreates the row and persists the update.
        $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'Repaired User',
            'phone' => '777000',
            'address' => 'Repaired Address',
        ])->assertRedirect()->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Repaired User', $user->name);
        $this->assertSame('Repaired Address', $user->customerProfile->address);
    }

    public function test_customer_dashboard_works_when_profile_row_is_missing(): void
    {
        $user = $this->makeUser('customer');
        $user->customerProfile->delete();

        $this->actingAs($user)->get(route('customer.dashboard'))->assertOk();
    }

    public function test_farmer_profile_page_works_when_profile_row_is_missing(): void
    {
        $user = $this->makeUser('farmer');
        $user->farmerProfile->delete();

        $this->actingAs($user)->get(route('farmer.profile.edit'))->assertOk();

        $this->actingAs($user)->post(route('farmer.profile.update'), [
            'business_name' => 'Repaired Farm',
            'contact_person' => 'Repaired Person',
            'address' => 'Repaired Farm Road',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Repaired Farm', $user->farmerProfile->refresh()->business_name);
    }

    public function test_farmer_dashboard_works_when_profile_row_is_missing(): void
    {
        $user = $this->makeUser('farmer');
        $user->farmerProfile->delete();

        $this->actingAs($user)->get(route('farmer.dashboard'))->assertOk();
    }

    public function test_rejected_photo_is_reported_with_a_validation_error(): void
    {
        $user = $this->makeUser('customer');

        $response = $this->actingAs($user)->from(route('profile.edit'))->post(route('profile.update'), [
            'name' => 'Bad Photo',
            'phone' => '123',
            'address' => 'Somewhere',
            'profile_image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('profile_image');
    }
}
