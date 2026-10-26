<?php

use App\Livewire\Admin\DataImporter;
use App\Livewire\Admin\PlayerChangeRequestManager;
use App\Livewire\Admin\PlayerManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\PlayerProposalForm;
use App\Models\Article;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ClubAccountCreated;
use App\Permission;
use App\Support\DataImport;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'junior_mlodszy']);
});

test('a players file from excel is recognised even when another tab is selected', function () {
    $this->actingAs(User::factory()->admin()->create());

    // Wzór od klubu: tabulatory i Windows-1250, wybrana zakładka „Drużyny”.
    $file = iconv('UTF-8', 'WINDOWS-1250', "imie\tnazwisko\tdruzyna\tnumer\tpozycja\tdata_urodzenia\twzrost\twaga\tzasieg_atak\tzasieg_blok\nJerzy\tPaźnicki\tKadeci\t7\trozgrywający\t\t\t\t\t\nBartosz\tJopek\tKadeci\t8\trozgrywający\t\t\t\t\t\n");

    Livewire::test(DataImporter::class)
        ->set('file', UploadedFile::fake()->createWithContent('wzor-players.csv', $file))
        ->assertSet('type', 'players')
        ->assertDontSee('brak kolumny')
        ->call('import');

    expect(Player::orderBy('jersey_number')->pluck('last_name')->all())->toBe(['Paźnicki', 'Jopek'])
        ->and($this->team->seasonRoster($this->season->id)->count())->toBe(2);
});

test('only first and last name are required, accounts are created from emails', function () {
    Notification::fake();

    $rows = DataImport::parse('players', "imie;nazwisko;email;rodzice;kapitan;zainteresowania\nAdam;Nowak;;;;\nOla;Kowalska;ola@example.com;Anna Kowalska <anna@example.com>, piotr@example.com;;gitara, góry\n");
    expect(collect($rows)->pluck('errors')->flatten()->all())->toBe([])
        ->and(DataImport::import('players', $rows))->toBe(2);

    $adam = Player::firstWhere('last_name', 'Nowak');
    $ola = Player::firstWhere('last_name', 'Kowalska');

    expect($adam->team_id)->toBeNull()
        ->and($adam->position)->toBeNull()
        ->and($ola->user->email)->toBe('ola@example.com')
        ->and($ola->user->hasRole(UserRole::Player))->toBeTrue()
        ->and($ola->user->is_active)->toBeTrue()
        ->and($ola->interests)->toBe('gitara, góry')
        ->and($ola->guardians->pluck('email')->sort()->values()->all())->toBe(['anna@example.com', 'piotr@example.com'])
        ->and($ola->guardians->firstWhere('email', 'anna@example.com')->name)->toBe('Anna Kowalska');

    Notification::assertSentTo($ola->user, ClubAccountCreated::class);

    // Drugi import z drużyną i opaską uzupełnia dane tej samej osoby.
    DataImport::import('players', DataImport::parse('players', "imie;nazwisko;druzyna;numer;kapitan\nAdam;Nowak;Kadeci;5;tak\n"));
    expect(Player::count())->toBe(2)
        ->and($adam->fresh()->team_id)->toBe($this->team->id)
        ->and($adam->fresh()->isCaptain())->toBeTrue();

    // Numer w drużynie jest unikalny: wiersz z zajętym numerem (także drugi taki w pliku) nie wchodzi, reszta tak.
    $rows = DataImport::parse('players', "imie;nazwisko;druzyna;numer\nJan;Zając;Kadeci;5\nPiotr;Lis;Kadeci;9\nOskar;Kos;Kadeci;9\n");
    expect($rows[0]['errors'])->toContain('numer 5 w drużynie Kadeci ma już Adam Nowak')
        ->and($rows[1]['errors'])->toBe([])
        ->and($rows[2]['errors'])->toContain('numer 9 w drużynie Kadeci jest już w wierszu 3')
        ->and(DataImport::import('players', $rows))->toBe(1)
        ->and(Player::count())->toBe(3);
});

test('admin sets captain, interests, social links and the player account in the editor', function () {
    Notification::fake();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(PlayerManager::class)
        ->call('openCreateModal')
        ->set('first_name', 'Adam')
        ->set('last_name', 'Nowak')
        ->set('team_id', $this->team->id)
        ->set('is_captain', true)
        ->set('interests', 'szachy, rower')
        ->set('social_links.instagram', 'https://instagram.com/adam')
        ->set('account_email', 'adam@example.com')
        ->call('addGuardian')
        ->set('new_guardians.0.email', 'mama@example.com')
        ->set('new_guardians.0.name', 'Ewa Nowak')
        ->call('save')
        ->assertHasNoErrors();

    $player = Player::firstWhere('last_name', 'Nowak');
    expect($player->isCaptain())->toBeTrue()
        ->and($player->user->email)->toBe('adam@example.com')
        ->and($player->guardians->first()->name)->toBe('Ewa Nowak');

    $this->get(route('players.show', $player))
        ->assertSee('Kapitan drużyny')
        ->assertSee('https://instagram.com/adam', false)
        ->assertSee('szachy');
});

test('a parent proposes the child email and approval creates the account', function () {
    Notification::fake();
    $player = Player::factory()->create(['team_id' => $this->team->id]);
    $parent = User::factory()->parent()->create();
    $player->guardians()->attach($parent);

    $this->actingAs($parent);
    Livewire::test(PlayerProposalForm::class, ['player' => $player])
        ->set('data.email', 'syn@example.com')
        ->set('data.social_links.tiktok', 'https://tiktok.com/@syn')
        ->call('submit')
        ->assertHasNoErrors();

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(PlayerChangeRequestManager::class)->call('approve', $player->changeRequests()->first()->id);

    expect($player->fresh()->user->email)->toBe('syn@example.com')
        ->and($player->fresh()->social_links)->toBe(['tiktok' => 'https://tiktok.com/@syn']);
});

test('commenting can be taken away from one account in the user editor', function () {
    $article = Article::factory()->for(User::factory(), 'author')->create(['status' => 'published', 'published_at' => now()->subDay(), 'allow_comments' => true]);
    $fan = User::factory()->fan()->create();
    expect($fan->canComment())->toBeTrue()
        ->and($fan->canAccessPanel())->toBeFalse();

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(UserManager::class)
        ->call('openEditModal', $fan->id)
        ->set('modalTab', 'uprawnienia')
        ->assertSee('Pisanie komentarzy')
        ->call('setOverride', Permission::WriteComments->value, 'deny');

    $this->actingAs($fan->fresh());
    $this->post(route('articles.comments.store', $article), ['content' => 'Hej'])->assertSessionHas('error');
    expect($article->comments()->count())->toBe(0);
    $this->get(route('articles.show', $article))->assertSee('Klub wyłączył Ci możliwość komentowania.');
});
