<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration screen includes team invitation context', function () {
    $owner = User::factory()->create();
    $teamName = 'Laravel Team';
    $team = Team::factory()->create(['name' => $teamName]);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('register', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('teamInvitation.code', $invitation->code)
        ->where('teamInvitation.teamName', $teamName),
    );
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();

    $response->assertRedirect(route('inicio'));
});

test('new users from the legacy registration screen are sent to the interface', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Legacy User',
        'email' => 'legacy@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'legacy' => '1',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('inicio'));
});
