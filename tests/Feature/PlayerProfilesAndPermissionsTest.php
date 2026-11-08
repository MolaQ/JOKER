<?php

use App\Livewire\Admin\DataImporter;
use App\Livewire\Admin\PlayerChangeRequestManager;
use App\Livewire\Admin\PlayerManager;
use App\Livewire\Admin\ProfileChangeRequestManager;
use App\Livewire\Admin\TeamList;
use App\Livewire\Admin\UserManager;
use App\Livewire\PlayerProposalForm;
use App\Livewire\ProfilePhotoProposal;
use App\Models\Article;
use App\Models\Player;
use App\Models\ProfileChangeRequest;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ClubAccountCreated;
use App\Permission;
use App\Support\Approvals;
use App\Support\DataImport;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

test('a player gets his own number in an extra team and the career shows it', function () {
    $this->actingAs(User::factory()->admin()->create());
    $juniors = Team::create(['name' => 'Juniorzy II', 'slug' => 'juniorzy-ii', 'category' => 'junior']);
    $player = Player::factory()->create(['team_id' => $this->team->id, 'jersey_number' => 10, 'last_name' => 'Molak']);

    Livewire::test(PlayerManager::class)
        ->call('openEditModal', $player->id)
        ->set('extra_team_ids', [(string) $juniors->id])
        ->set('extra_team_numbers.'.$juniors->id, 7)
        ->call('save')
        ->assertHasNoErrors();

    expect($player->teamsForSeason()->where('teams.id', $juniors->id)->first()->pivot->jersey_number)->toBe(7);
    Livewire::test(PlayerManager::class)->call('openEditModal', $player->id)->assertSet('extra_team_numbers.'.$juniors->id, 7);
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

test('lista drużyn w panelu pokazuje odbiór, a lista zawodników sortuje po ostatnim logowaniu', function () {
    $this->actingAs(User::factory()->admin()->create());
    $recent = User::factory()->create(['last_login_at' => now()->subHour()]);
    $older = User::factory()->create(['last_login_at' => now()->subMonth()]);
    Player::factory()->create(['team_id' => $this->team->id, 'jersey_number' => 9, 'last_name' => 'Dawny', 'user_id' => $older->id]);
    Player::factory()->create(['team_id' => $this->team->id, 'jersey_number' => 1, 'last_name' => 'Niedawny', 'user_id' => $recent->id]);
    $this->team->forceFill(['views_count' => 340])->save();

    Livewire::test(PlayerManager::class)
        ->assertSeeInOrder(['Niedawny', 'Dawny'])
        ->call('sortBy', 'logowanie')
        ->assertSeeInOrder(['Niedawny', 'Dawny'])
        ->call('sortBy', 'logowanie')
        ->assertSeeInOrder(['Dawny', 'Niedawny'])
        ->assertSee('1 godzina temu');

    Livewire::test(TeamList::class)->call('sortBy', 'wyswietlenia')->assertDontSee('Odbiór')->assertSee('340');
});

test('an admin edits a trainer description and social profiles directly', function () {
    $this->actingAs(User::factory()->admin()->create());
    $trainer = User::factory()->create(['role' => UserRole::Trainer, 'name' => 'Anna Trenerka', 'instagram' => '@anna.stara']);

    $screen = Livewire::test(UserManager::class)
        ->call('openEditModal', $trainer->id)
        ->assertSet('social_links.instagram', 'https://www.instagram.com/anna.stara');

    $screen->set('social_links.facebook', 'facebook.com/anna')->call('save')->assertHasErrors('social_links.facebook');

    $screen->set('social_links.instagram', 'https://instagram.com/anna.trener')
        ->set('social_links.facebook', ' https://facebook.com/anna ')
        ->set('social_links.tiktok', '')
        ->set('additional_info', '<p>Trener II klasy, 10 lat w klubie.</p>')
        ->call('save')
        ->assertHasNoErrors();

    $trainer->refresh();
    expect($trainer->social_links)->toBe(['instagram' => 'https://instagram.com/anna.trener', 'facebook' => 'https://facebook.com/anna'])
        ->and($trainer->instagram)->toBe('https://instagram.com/anna.trener')
        ->and($trainer->additional_info)->toBe('<p>Trener II klasy, 10 lat w klubie.</p>')
        ->and($trainer->hasRole(UserRole::Trainer))->toBeTrue();

    $this->get(route('trainers.show', $trainer))->assertOk()
        ->assertSee('Trener II klasy, 10 lat w klubie.')
        ->assertSee('https://facebook.com/anna', false)
        ->assertSee('https://instagram.com/anna.trener', false);
});

test('a trainer proposes every social profile from the settings and the approval applies them', function () {
    $trainer = User::factory()->create(['role' => UserRole::Trainer, 'name' => 'Anna Trenerka', 'activated_at' => now()]);
    $this->actingAs($trainer)->get(route('profile.edit'))->assertOk()
        ->assertSee('social_links[instagram]', false)->assertSee('social_links[youtube]', false)->assertSee('social_links[x]', false);

    $this->post(route('profile.request-change'), [
        'name' => 'Anna Trenerka', 'email' => $trainer->email,
        'social_links' => ['youtube' => ' https://youtube.com/@anna ', 'myspace' => 'https://myspace.com/anna', 'facebook' => ''],
    ])->assertSessionHasNoErrors();

    $request = ProfileChangeRequest::sole();
    expect($request->proposed_data['social_links'])->toBe(['youtube' => 'https://youtube.com/@anna']);

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ProfileChangeRequestManager::class)->call('approve', $request->id);

    expect($trainer->fresh()->social_links)->toBe(['youtube' => 'https://youtube.com/@anna'])
        ->and($trainer->fresh()->instagram)->toBeNull();
});

test('only the boss and the admin approve account profile changes', function () {
    $trainer = User::factory()->create(['role' => UserRole::Trainer, 'activated_at' => now()]);
    $request = ProfileChangeRequest::create(['user_id' => $trainer->id, 'proposed_data' => ['name' => $trainer->name, 'email' => $trainer->email, 'additional_info' => '<p>Nowy opis</p>'], 'status' => 'pending']);

    foreach ([UserRole::Trainer, UserRole::Coordinator, UserRole::Moderator] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]));
        Livewire::test(ProfileChangeRequestManager::class)->assertForbidden();
        expect(Approvals::sectionsFor(auth()->user())->has('profile'))->toBeFalse();
    }

    $this->actingAs(User::factory()->create(['role' => UserRole::Boss]));
    Livewire::test(ProfileChangeRequestManager::class)->call('approve', $request->id);
    expect($trainer->fresh()->additional_info)->toBe('<p>Nowy opis</p>');
});

test('a trainer photo is set by the admin or proposed by the trainer in a 4:5 crop', function () {
    Storage::fake('public');
    $trainer = User::factory()->create(['role' => UserRole::Trainer, 'activated_at' => now()]);

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(UserManager::class)->call('openEditModal', $trainer->id)
        ->set('photo', UploadedFile::fake()->image('kwadrat.jpg', 800, 800))->call('save')->assertHasErrors('photo')
        ->set('photo', UploadedFile::fake()->image('trener.jpg', 800, 1000))->call('save')->assertHasNoErrors();
    $first = $trainer->fresh()->avatar_path;
    Storage::disk('public')->assertExists($first);

    $this->actingAs($trainer);
    Livewire::test(ProfilePhotoProposal::class)
        ->set('photo', UploadedFile::fake()->image('nowe.jpg', 800, 1000))->call('submit')->assertHasNoErrors();
    $request = ProfileChangeRequest::sole();
    expect($trainer->fresh()->avatar_path)->toBe($first);

    $this->actingAs(User::factory()->create(['role' => UserRole::Boss]));
    Livewire::test(ProfileChangeRequestManager::class)->assertSee('Nowe zdjęcie')->call('approve', $request->id);

    expect($trainer->fresh()->avatar_path)->toBe($request->proposed_data['avatar_path'])
        ->and($trainer->fresh()->name)->toBe($trainer->name);
    Storage::disk('public')->assertMissing($first);

    $this->actingAs(User::factory()->create(['role' => UserRole::Fan]));
    Livewire::test(ProfilePhotoProposal::class)->assertForbidden();
});

test('profile change requests show readable changes instead of raw data', function () {
    $trainer = User::factory()->create(['role' => UserRole::Trainer, 'activated_at' => now(), 'name' => 'Szymon Pietraszak', 'additional_info' => '<p>Stary opis</p>']);
    ProfileChangeRequest::create(['user_id' => $trainer->id, 'status' => 'pending', 'proposed_data' => [
        'email' => $trainer->email, 'name' => 'Szymon Pietraszak', 'social_links' => [],
        'additional_info' => '<p><strong>Szymon</strong> zdobywał doświadczenie</p><script>alert(1)</script>',
    ]]);

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ProfileChangeRequestManager::class)
        ->assertSee('O mnie')->assertSeeHtml('<strong>Szymon</strong> zdobywał doświadczenie')->assertSee('Stary opis')
        ->assertSee('bez zmian')->assertSee('Oczekuje')
        ->assertDontSeeHtml('<script>alert(1)</script>')->assertDontSee('"additional_info"')->assertDontSee('social_links');
});
