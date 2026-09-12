<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The reason this screen exists. The users screen can change anyone's
     * password, but it is administrator-only — so before this, an editor or a
     * viewer had no page at all on which to change their own, and "ask the
     * administrator" was the whole procedure.
     */
    public function test_every_role_can_reach_their_own_account(): void
    {
        foreach (User::ROLES as $role) {
            $this->flushSession();

            $this->actingAs($this->makeAdmin($role))
                ->get('/admin/profile')
                ->assertOk()
                ->assertSee(__('admin.change_password'));
        }
    }

    public function test_an_editor_changes_their_own_password(): void
    {
        $editor = $this->makeAdmin(User::ROLE_EDITOR);

        $this->actingAs($editor)
            ->put('/admin/profile/password', [
                'current_password' => 'password-for-tests',
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('a-brand-new-password', $editor->fresh()->password));
    }

    /**
     * Without this, an unlocked screen is enough to take an account: anyone
     * walking past could set a new password and lock the owner out.
     */
    public function test_the_current_password_has_to_be_right(): void
    {
        $user = $this->makeAdmin(User::ROLE_EDITOR);

        $this->actingAs($user)
            ->put('/admin/profile/password', [
                'current_password' => 'not-the-current-one',
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password-for-tests', $user->fresh()->password));
    }

    public function test_the_new_password_has_to_be_typed_twice(): void
    {
        $user = $this->makeAdmin(User::ROLE_EDITOR);

        $this->actingAs($user)
            ->put('/admin/profile/password', [
                'current_password' => 'password-for-tests',
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-different-thing',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password-for-tests', $user->fresh()->password));
    }

    public function test_the_details_can_be_edited(): void
    {
        $user = $this->makeAdmin(User::ROLE_VIEWER);

        $this->actingAs($user)
            ->put('/admin/profile', [
                'name' => 'نام تازه',
                'email' => 'someone-else@artaleca.com',
                'locale' => 'en',
            ])
            ->assertRedirect(route('admin.profile.edit'));

        $user->refresh();

        $this->assertSame('نام تازه', $user->name);
        $this->assertSame('someone-else@artaleca.com', $user->email);
        $this->assertSame('en', $user->locale);
    }

    /**
     * Role and active state belong to whoever administers the site. A viewer
     * posting them at this route must not be able to promote themselves, and
     * the controller's answer is simply not to read those fields.
     */
    public function test_a_viewer_cannot_promote_themselves_here(): void
    {
        $user = $this->makeAdmin(User::ROLE_VIEWER);

        $this->actingAs($user)->put('/admin/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'fa',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->assertSame(User::ROLE_VIEWER, $user->fresh()->role);
    }

    public function test_the_address_cannot_collide_with_another_account(): void
    {
        $other = $this->makeAdmin(User::ROLE_EDITOR);
        $this->flushSession();
        $user = $this->makeAdmin(User::ROLE_EDITOR);

        $this->actingAs($user)
            ->put('/admin/profile', [
                'name' => $user->name,
                'email' => $other->email,
                'locale' => 'fa',
            ])
            ->assertSessionHasErrors('email');
    }

    /** Guests get the login page, not the form. */
    public function test_it_is_closed_to_guests(): void
    {
        $this->get('/admin/profile')->assertRedirect();
        $this->put('/admin/profile/password')->assertRedirect();
    }
}
