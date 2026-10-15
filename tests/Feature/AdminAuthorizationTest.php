<?php
namespace Tests\Feature;

use App\Models\{User,Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    public function test_guest_cannot_access_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
    public function test_staff_cannot_manage_users_or_settings_but_can_edit_catalog(): void
    {
        $staff=User::factory()->create(['role'=>'staff']);
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny',User::class));
        $this->assertTrue(Gate::forUser($staff)->allows('viewAny',Product::class));
        $this->actingAs($staff)->get('/admin/store-settings')->assertForbidden();
    }
    public function test_owner_can_access_settings_and_users(): void
    {
        $owner=User::factory()->create(['role'=>'owner']);
        $this->assertTrue(Gate::forUser($owner)->allows('viewAny',User::class));
        $this->actingAs($owner)->get('/admin/store-settings')->assertOk();
    }
}
