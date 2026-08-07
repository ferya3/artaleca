<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_panel_is_closed_to_guests(): void
    {
        foreach (['/admin', '/admin/products', '/admin/enquiries', '/admin/settings', '/admin/users'] as $path) {
            $this->get($path)->assertRedirect();
        }
    }

    public function test_a_valid_sign_in_reaches_the_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password-for-tests',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_bad_password_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** A wrong password and an unknown address must be indistinguishable. */
    public function test_the_login_form_does_not_reveal_whether_an_account_exists(): void
    {
        $admin = $this->makeAdmin();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => __('admin.failed')]);

        $this->post('/admin/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => __('admin.failed')]);
    }

    public function test_a_deactivated_account_loses_access_on_its_next_request(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/admin')->assertOk();

        $admin->forceFill(['is_active' => false])->save();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_login_is_rate_limited(): void
    {
        $admin = $this->makeAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_the_panel_is_never_indexable(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin')
            ->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    // ── Policies ────────────────────────────────────────────────────────────

    public function test_an_editor_may_write_content_but_not_delete_it(): void
    {
        $this->makeProduct();
        $editor = $this->makeAdmin(User::ROLE_EDITOR);

        $this->actingAs($editor)->get('/admin/products/create')->assertOk();
        $this->actingAs($editor)->delete('/admin/products/leca-4-10')->assertForbidden();
    }

    public function test_an_admin_may_delete_content(): void
    {
        $this->makeProduct();

        $this->actingAs($this->makeAdmin())
            ->delete('/admin/products/leca-4-10')
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseCount('products', 0);
    }

    public function test_a_viewer_may_read_the_inbox_but_not_edit_content(): void
    {
        $this->makeProduct();
        $viewer = $this->makeAdmin(User::ROLE_VIEWER);

        $this->actingAs($viewer)->get('/admin/enquiries')->assertOk();
        $this->actingAs($viewer)->get('/admin/products')->assertOk();
        $this->actingAs($viewer)->get('/admin/products/create')->assertForbidden();
    }

    public function test_only_an_admin_may_manage_users_and_settings(): void
    {
        $editor = $this->makeAdmin(User::ROLE_EDITOR);

        $this->actingAs($editor)->get('/admin/users')->assertForbidden();
        $this->actingAs($editor)->get('/admin/settings')->assertForbidden();

        $this->actingAs($this->makeAdmin())->get('/admin/users')->assertOk();
        $this->actingAs($this->makeAdmin())->get('/admin/settings')->assertOk();
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /** Otherwise the last administrator can lock everyone out of the panel. */
    public function test_an_admin_cannot_demote_themselves(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_VIEWER,
            'locale' => 'fa',
        ])->assertRedirect();

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->is_active);
    }
}
