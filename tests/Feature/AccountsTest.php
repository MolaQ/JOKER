<?php

use App\Livewire\Admin\AccountActivation;
use App\Livewire\Admin\ParentAssignment;
use App\Livewire\LikeButton;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountActivated;
use App\UserRole;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet']);
});

test('a parent registers and waits for activation', function () {
    $this->post('/rejestracja', [
        'name' => 'Anna Nowak',
        'email' => 'anna@example.com',
        'password' => 'tajnehaslo1',
        'password_confirmation' => 'tajnehaslo1',
        'kind' => 'parent',
        'team_id' => $this->team->id,
        'note' => 'Jan Nowak',
        'privacy' => '1',
        'policy' => '1',
    ])->assertRedirect(route('profile.edit'));

    $user = User::firstWhere('email', 'anna@example.com');
    expect($user->is_active)->toBeFalse()
        ->and($user->requested_role)->toBe(UserRole::Parent)
        ->and($user->roles())->toBeEmpty();
    Notification::assertSentTo($user, VerifyEmail::class);

    $this->get('/moj-profil')->assertSee('Konto czeka na aktywację');
    $this->get('/panel')->assertForbidden();
});

test('a fan account works after confirming the email', function () {
    $user = User::factory()->inactive()->unverified()->create(['requested_role' => UserRole::Fan]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->actingAs($user)->get($url)->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->is_active)->toBeTrue()->and($user->roles()->all())->toBe([UserRole::Fan]);
});

test('an inactive account cannot like or comment', function () {
    $user = User::factory()->inactive()->create();
    $article = Article::factory()->for(User::factory(), 'author')->create(['status' => 'published', 'published_at' => now()->subDay(), 'allow_comments' => true]);

    $this->actingAs($user);
    Livewire::test(LikeButton::class, ['type' => 'article', 'id' => $article->id])->call('toggle');
    $this->post(route('articles.comments.store', $article), ['content' => 'Hej'])->assertSessionHas('error');

    expect($article->likes()->count())->toBe(0)->and($article->comments()->count())->toBe(0);
});

test('a trainer activates a parent of own team and links the child', function () {
    $trainer = User::factory()->trainer()->create();
    $this->team->update(['trainer_id' => $trainer->id]);
    $child = Player::factory()->create(['first_name' => 'Jan', 'last_name' => 'Nowak', 'team_id' => $this->team->id]);
    $parent = User::factory()->inactive()->create(['name' => 'Anna Nowak', 'requested_role' => UserRole::Parent, 'requested_team_id' => $this->team->id]);
    $otherTeam = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $stranger = User::factory()->inactive()->create(['name' => 'Obca Osoba', 'requested_role' => UserRole::Parent, 'requested_team_id' => $otherTeam->id]);

    $this->actingAs($trainer)->get('/panel/rodzice-do-przypisania')->assertOk()->assertSee('Anna Nowak')->assertDontSee('Obca Osoba');

    Livewire::test(ParentAssignment::class)->call('assign', $parent->id, $child->id);

    $parent->refresh();
    expect($parent->is_active)->toBeTrue()
        ->and($parent->roles()->all())->toBe([UserRole::Parent])
        ->and($parent->children->pluck('id')->all())->toBe([$child->id])
        ->and(AuditLog::where('action', 'account.activated')->exists())->toBeTrue();
    Notification::assertSentTo($parent, AccountActivated::class);

    expect(fn () => Livewire::test(ParentAssignment::class)->call('assign', $stranger->id, $child->id))->toThrow(Exception::class);
});

test('an administrator activates a player and links the player profile', function () {
    $player = Player::factory()->create(['team_id' => $this->team->id]);
    $account = User::factory()->inactive()->create(['requested_role' => UserRole::Player, 'requested_team_id' => $this->team->id]);

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(AccountActivation::class)
        ->call('start', $account->id)
        ->set('playerId', $player->id)
        ->call('activate')
        ->assertHasNoErrors();

    expect($player->fresh()->user_id)->toBe($account->id)->and($account->fresh()->is_active)->toBeTrue()->and($account->fresh()->hasRole(UserRole::Player))->toBeTrue();
});

test('a parent goes inactive with the last child and comes back with it', function () {
    $parent = User::factory()->parent()->create();
    $first = Player::factory()->create(['team_id' => $this->team->id, 'is_active' => true]);
    $second = Player::factory()->create(['team_id' => $this->team->id, 'is_active' => true]);
    $parent->children()->attach([$first->id, $second->id]);

    $first->update(['is_active' => false]);
    expect($parent->fresh()->is_active)->toBeTrue();

    $second->update(['is_active' => false]);
    expect($parent->fresh()->is_active)->toBeFalse();

    $second->update(['is_active' => true]);
    expect($parent->fresh()->is_active)->toBeTrue();

    $coachParent = User::factory()->create(['role' => [UserRole::Trainer, UserRole::Parent]]);
    $coachParent->children()->attach($first->id);
    $first->update(['is_active' => true]);
    $first->update(['is_active' => false]);
    expect($coachParent->fresh()->is_active)->toBeTrue();
});

test('password can be reset by email link', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->post('/haslo/przypomnij', ['email' => 'reset@example.com'])->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->post('/haslo/nowe', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'nowehaslo123',
            'password_confirmation' => 'nowehaslo123',
        ])->assertRedirect(route('login'));

        return true;
    });

    $this->post('/login', ['email' => 'reset@example.com', 'password' => 'nowehaslo123'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

test('users change their password in my profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/moj-profil/haslo', ['current_password' => 'zle', 'password' => 'nowehaslo123', 'password_confirmation' => 'nowehaslo123'])->assertSessionHasErrors('current_password');
    $this->actingAs($user)->put('/moj-profil/haslo', ['current_password' => 'password', 'password' => 'nowehaslo123', 'password_confirmation' => 'nowehaslo123'])->assertSessionHasNoErrors();

    expect(Hash::check('nowehaslo123', $user->fresh()->password))->toBeTrue();
});

test('the join button in the menu leads guests to registration', function () {
    $this->get('/')->assertOk()->assertSee('href="'.url('/rejestracja').'"', false);

    $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertDontSee('href="'.url('/rejestracja').'"', false);
});
