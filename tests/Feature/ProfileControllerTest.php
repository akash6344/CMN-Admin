<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_profile_page(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin', 'mobile' => '9876543210']);

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee($admin->email)
            ->assertSee('9876543210');
    }

    public function test_admin_can_update_profile_details(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin', 'mobile' => '9876543210']);

        $this->actingAs($admin)
            ->put(route('profile.update'), [
                'name' => 'Updated Admin',
                'email' => 'updated@cmnhousing.test',
                'mobile' => '9998887776',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Updated Admin',
            'email' => 'updated@cmnhousing.test',
            'mobile' => '9998887776',
        ]);
    }

    public function test_admin_can_upload_and_remove_profile_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['admin_role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
            ])
            ->assertRedirect();

        $admin->refresh();
        $this->assertNotNull($admin->profile_photo_path);
        Storage::disk('public')->assertExists($admin->profile_photo_path);

        $this->actingAs($admin)
            ->delete(route('profile.photo.destroy'))
            ->assertRedirect();

        $this->assertNull($admin->fresh()->profile_photo_path);
    }

    public function test_admin_can_change_password_with_current_password(): void
    {
        $admin = User::factory()->create([
            'admin_role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
    }

    public function test_admin_cannot_change_password_with_wrong_current_password(): void
    {
        $admin = User::factory()->create([
            'admin_role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('current_password');
    }

    public function test_empty_mobile_is_stored_as_null(): void
    {
        $admin = User::factory()->create([
            'admin_role' => 'admin',
            'mobile' => '9876543210',
        ]);

        $this->actingAs($admin)
            ->put(route('profile.update'), [
                'name' => $admin->name,
                'email' => $admin->email,
                'mobile' => '',
            ])
            ->assertRedirect();

        $this->assertNull($admin->fresh()->mobile);
    }
}
