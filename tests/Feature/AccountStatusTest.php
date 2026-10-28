<?php

use App\AccountStatus;
use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\GameManager;
use App\Livewire\Admin\ParentAssignment;
use App\Livewire\Admin\UserManager;
use App\Models\Article;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\Player;
use App\Models\ProfileChangeRequest;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountRejected;
use App\UserRole;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet']);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

test('registration requires the privacy consents and stores the consent date', function () {
    $data = [
        'name' => 'Ola Kowal', 'email' => 'ola@example.com', 'password' => 'tajnehaslo1', 'password_confirmation' => 'tajnehaslo1',
        'kind' => 'player', 'team_id' => $this->team->id,
    ];

    $this->post('/rejestracja', $data)->assertSessionHasErrors(['privacy', 'policy', 'age']);
    expect(User::where('email', 'ola@example.com')->exists())->toBeFalse();

    $this->post('/rejestracja', $data + ['privacy' => '1', 'policy' => '1', 'age' => '1'])->assertRedirect(route('profile.edit'));
    expect(User::firstWhere('email', 'ola@example.com')->privacy_consent_at)->not->toBeNull();
});

test('an account without consent must give it before using the site', function () {
    $user = User::factory()->create();
    $user->forceFill(['privacy_consent_at' => null])->save();

    $this->actingAs($user)->get('/moj-profil')->assertRedirect(route('account.consent'));
    $this->get(route('account.consent'))->assertOk()->assertSee('Zgoda na przetwarzanie danych');
    $this->post(route('account.consent.store'), ['privacy' => '1'])->assertSessionHasErrors('policy');
    $this->post(route('account.consent.store'), ['privacy' => '1', 'policy' => '1'])->assertRedirect('/moj-profil');
    $this->get('/moj-profil')->assertOk();
});

test('the club rejects an account with a reason and the user resubmits corrected data', function () {
    $user = User::factory()->inactive()->create(['name' => 'Jan', 'requested_role' => UserRole::Parent, 'requested_team_id' => $this->team->id, 'registration_note' => 'syn']);

    Livewire::actingAs($this->admin)->test(ParentAssignment::class)
        ->call('startRejecting', $user->id)
        ->call('reject')
        ->assertHasErrors('rejectionReason')
        ->set('rejectionReason', 'Podaj pełne imię i nazwisko dziecka.')
        ->call('reject')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->exists)->toBeTrue()
        ->and($user->accountStatus())->toBe(AccountStatus::Rejected)
        ->and($user->rejection_reason)->toBe('Podaj pełne imię i nazwisko dziecka.')
        ->and(ParentAssignment::pendingFor($this->admin)->whereKey($user->id)->exists())->toBeFalse();
    Notification::assertSentTo($user, AccountRejected::class);

    $this->actingAs($user)->get('/moj-profil')->assertSee('Podaj pełne imię i nazwisko dziecka.')->assertSee('Wyślij prośbę ponownie');
    $this->post(route('account.resubmit'), ['name' => 'Jan Nowak', 'kind' => 'parent', 'team_id' => $this->team->id, 'note' => 'Piotr Nowak, rocznik 2010'])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Jan Nowak')
        ->and($user->accountStatus())->toBe(AccountStatus::Pending)
        ->and(ParentAssignment::pendingFor($this->admin)->whereKey($user->id)->exists())->toBeTrue();
});

test('the name of an approved account cannot be changed by its owner', function () {
    $user = User::factory()->create(['name' => 'Anna Nowak', 'activated_at' => now()]);

    $this->actingAs($user)->get('/moj-profil')->assertSee('imię i nazwisko zmienia tylko klub');
    $this->post(route('profile.request-change'), ['name' => 'Ktoś Inny', 'email' => $user->email])->assertSessionHasNoErrors();

    expect(ProfileChangeRequest::first()->proposed_data['name'])->toBe('Anna Nowak');
});

test('a user disables the account and turns it back on without a new activation', function () {
    $user = User::factory()->create(['activated_at' => now()]);

    $this->actingAs($user)->post(route('account.disable'), ['password' => 'zle'])->assertSessionHasErrors('password');
    $this->post(route('account.disable'), ['password' => 'password'])->assertSessionHasNoErrors();
    expect($user->fresh()->accountStatus())->toBe(AccountStatus::Disabled)
        ->and($user->fresh()->is_active)->toBeFalse();

    $this->post(route('account.enable'))->assertSessionHasNoErrors();
    expect($user->fresh()->accountStatus())->toBe(AccountStatus::Active);
});

test('withdrawing consent deletes personal data and unlinks the account', function () {
    $user = User::factory()->create(['name' => 'Anna Nowak', 'instagram' => 'anna', 'activated_at' => now()]);
    $player = Player::factory()->create(['user_id' => $user->id, 'team_id' => $this->team->id]);

    $this->actingAs($user)->get(route('account.export'))->assertOk()->assertJsonPath('konto.name', 'Anna Nowak');
    $this->delete(route('account.destroy'), ['password' => 'password', 'reason' => 'consent_withdrawn'])->assertRedirect(route('home'));

    $user->refresh();
    expect($user->name)->toBe('Konto usunięte')
        ->and($user->email)->toEndWith('@konto.invalid')
        ->and($user->instagram)->toBeNull()
        ->and($user->accountStatus())->toBe(AccountStatus::Deleted)
        ->and($player->fresh()->user_id)->toBeNull();
    $this->assertGuest();

    Livewire::actingAs($this->admin)->test(UserManager::class)
        ->set('statusFilter', 'deleted')
        ->assertSee('Usunięte');
});

test('games are filtered by their materials', function () {
    $this->season = Season::factory()->create();
    $withArticle = Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent' => 'Rywal Artykuł']);
    $withArticle->articles()->attach(Article::factory()->create(['author_id' => $this->admin->id]));
    Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent' => 'Rywal Wideo', 'streams' => [['url' => 'https://youtu.be/abcdefghijk', 'label' => '']]]);
    $withGallery = Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent' => 'Rywal Galeria']);
    Gallery::factory()->create(['match_id' => $withGallery->id]);
    Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent' => 'Rywal Pusty']);

    $component = Livewire::actingAs($this->admin)->test(GameManager::class);

    $component->set('mediaFilter', 'wiadomosc')->assertSee('Rywal Artykuł')->assertDontSee('Rywal Pusty');
    $component->set('mediaFilter', 'wideo')->assertSee('Rywal Wideo')->assertDontSee('Rywal Galeria');
    $component->set('mediaFilter', 'galeria')->assertSee('Rywal Galeria')->assertDontSee('Rywal Wideo');
    $component->set('mediaFilter', 'bez')->assertSee('Rywal Pusty')->assertDontSee('Rywal Artykuł')->assertDontSee('Rywal Wideo')->assertDontSee('Rywal Galeria');
});

test('the featured image focus point is saved and used on the article page', function () {
    $article = Article::factory()->create(['author_id' => $this->admin->id, 'status' => 'published', 'published_at' => now()->subDay(), 'featured_image' => 'articles/zdjecie.jpg']);

    Livewire::actingAs($this->admin)->test(ArticleManager::class)
        ->call('openEditModal', $article->id)
        ->assertSet('focusX', 50)
        ->set('focusX', 80)
        ->set('focusY', 25)
        ->call('save')
        ->assertHasNoErrors();

    expect($article->fresh()->featured_image_focus)->toBe('80% 25%')
        ->and($article->fresh()->imageFocus())->toBe('80% 25%');

    $article->update(['featured_image_focus' => 'zły tekst']);
    expect($article->fresh()->imageFocus())->toBe('50% 50%');
});
