<?php

namespace Tests\Feature;

use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-filter@test.dev', 'phone' => '100',
            'role' => 'admin', 'status' => 'active', 'password' => Hash::make('password'),
        ]);

        // Approved farmer
        $approvedUser = User::create([
            'name' => 'Approved Farmer', 'email' => 'approved@test.dev', 'phone' => '200',
            'role' => 'farmer', 'status' => 'active', 'password' => Hash::make('password'),
        ]);
        FarmerProfile::create([
            'user_id' => $approvedUser->id,
            'business_name' => 'Approved Acres',
            'contact_person' => 'Approved Farmer',
            'address' => '1 Green Rd',
            'approval_status' => 'approved',
        ]);

        // Pending farmer — the case the dashboard link used to miss
        $pendingUser = User::create([
            'name' => 'Pending Farmer', 'email' => 'pending@test.dev', 'phone' => '201',
            'role' => 'farmer', 'status' => 'active', 'password' => Hash::make('password'),
        ]);
        FarmerProfile::create([
            'user_id' => $pendingUser->id,
            'business_name' => 'Pending Pastures',
            'contact_person' => 'Pending Farmer',
            'address' => '2 Green Rd',
            'approval_status' => 'pending',
        ]);
    }

    /** @test */
    public function approval_pending_filter_shows_the_pending_farmer_only(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/users?role=farmer&approval=pending')
            ->assertOk()
            ->assertJsonPath('rows.0.farmer_profile.business_name', 'Pending Pastures')
            ->assertJsonCount(1, 'rows');
    }

    /** @test */
    public function approval_approved_filter_shows_approved_farmers_only(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/users?role=farmer&approval=approved')
            ->assertOk()
            ->assertJsonPath('rows.0.farmer_profile.business_name', 'Approved Acres')
            ->assertJsonCount(1, 'rows');
    }

    /** @test */
    public function account_status_filter_still_works_alongside_role(): void
    {
        // Both farmers have active accounts, so both show up here
        $this->actingAs($this->admin)
            ->getJson('/api/admin/users?role=farmer&status=active')
            ->assertOk()
            ->assertJsonCount(2, 'rows');

        // Suspended accounts match nothing
        $this->actingAs($this->admin)
            ->getJson('/api/admin/users?role=farmer&status=suspended')
            ->assertOk()
            ->assertJsonCount(0, 'rows');
    }

    /** @test */
    public function unfiltered_listing_shows_every_user(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(3, 'rows');
    }
}
