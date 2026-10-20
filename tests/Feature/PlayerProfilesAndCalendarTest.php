<?php

use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\CompetitionManager;
use App\Livewire\Admin\PlayerChangeRequestManager;
use App\Livewire\GamesBrowser;
use App\Livewire\PlayerProposalForm;
use App\Models\Article;
use App\Models\Competition;
use App\Models\Game;
use App\Models\LeagueStanding;
use App\Models\Player;
use App\Models\PlayerChangeRequest;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo('2026-10-20 12:00');
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
    $this->competition = Competition::factory()->create(['name' => 'Liga Kadetów', 'season_id' => $this->season->id]);
    $this->competition->teams()->attach($this->team->id, ['display_name' => 'LOMS Joker Piła', 'display_suffix' => '(kadeci)']);
});

function calendarGame(array $attributes = []): Game
{
    return Game::factory()->create(array_merge([
        'team_id' => test()->team->id,
        'season_id' => test()->season->id,
        'competition_id' => test()->competition->id,
        'opponent' => 'UKS Kłos Wronki',
        'sets_score' => null,
    ], $attributes));
}

test('calendar marks home and away games, results with V or X and opens the newest article', function () {
    $won = calendarGame(['is_home' => true, 'game_date' => '2026-10-04 17:00', 'status' => 'finished', 'home_score' => 3, 'away_score' => 1]);
    calendarGame(['is_home' => false, 'game_date' => '2026-10-11 17:00', 'status' => 'finished', 'home_score' => 3, 'away_score' => 0]);
    calendarGame(['is_home' => false, 'game_date' => '2026-10-25 17:00', 'status' => 'scheduled', 'home_score' => null, 'away_score' => null]);

    $author = User::factory()->admin()->create();
    $older = Article::factory()->for($author, 'author')->create(['title' => 'Zapowiedź meczu', 'status' => 'published', 'published_at' => '2026-10-03 10:00']);
    $newer = Article::factory()->for($author, 'author')->create(['title' => 'Relacja z wygranej', 'status' => 'published', 'published_at' => '2026-10-05 10:00']);
    $draft = Article::factory()->for($author, 'author')->create(['title' => 'Szkic', 'status' => 'draft', 'published_at' => '2026-10-06 10:00']);
    $won->articles()->attach([$older->id, $newer->id, $draft->id]);

    Livewire::test(GamesBrowser::class)
        ->set('period', 'wszystkie')
        ->assertSeeHtml('bg-ink text-white')
        ->assertSeeHtml('bg-zinc-200 text-zinc-800')
        ->assertSeeHtml('aria-label="Wygrana"')
        ->assertSeeHtml('aria-label="Porażka"')
        ->assertSeeHtml('href="'.route('articles.show', $newer).'"')
        ->assertDontSeeHtml('href="'.route('articles.show', $older).'"')
        ->assertSee('Wygrana 3:1')
        ->assertSee('Porażka 0:3')
        ->assertSeeHtml('<em class="ml-1')
        ->assertSee('(kadeci)');
});

test('calendar shows birthdays of players and of staff who allowed it', function () {
    calendarGame(['game_date' => '2026-10-25 17:00', 'status' => 'scheduled', 'home_score' => null, 'away_score' => null]);
    Player::factory()->create(['first_name' => 'Jan', 'last_name' => 'Nowak', 'team_id' => $this->team->id, 'birth_date' => '2010-10-22', 'is_active' => true]);
    Player::factory()->create(['first_name' => 'Ukryty', 'last_name' => 'Zawodnik', 'team_id' => $this->team->id, 'birth_date' => '2010-10-23', 'is_active' => true, 'show_birthday' => false]);
    User::factory()->trainer()->create(['name' => 'Trener Kowalski', 'birth_date' => '1982-10-26', 'show_birthday' => true]);
    User::factory()->trainer()->create(['name' => 'Trener Cichy', 'birth_date' => '1980-10-27', 'show_birthday' => false]);

    Livewire::test(GamesBrowser::class)
        ->assertSee('Jan Nowak')
        ->assertSee('kończy 16 lat')
        ->assertSee('Trener Kowalski')
        ->assertSee('44 lata')
        ->assertDontSee('Ukryty Zawodnik')
        ->assertDontSee('Trener Cichy');

    expect(GamesBrowser::yearsLabel(1))->toBe('1 rok')
        ->and(GamesBrowser::yearsLabel(22))->toBe('22 lata')
        ->and(GamesBrowser::yearsLabel(12))->toBe('12 lat');
});

test('standings use the team name and italic suffix set for the competition', function () {
    LeagueStanding::create([
        'competition_id' => $this->competition->id,
        'season_id' => $this->season->id,
        'competitor_type' => 'team',
        'competitor_id' => $this->team->id,
        'position' => 1, 'points' => 9,
    ]);

    $this->get('/tabele')
        ->assertOk()
        ->assertSeeText('LOMS Joker Piła')
        ->assertSeeHtml('(kadeci)</em>');

    expect(Competition::defaultTeamLabel('Seniorzy', 'seniorzy', 'LOMS Joker Piła'))->toBe(['display_name' => 'LOMS Joker Piła', 'display_suffix' => null])
        ->and(Competition::defaultTeamLabel('Kadeci II', 'kadeci-ii', 'ASBL; KS ASBL Piła'))->toBe(['display_name' => 'KS ASBL Piła', 'display_suffix' => '(kadeci II)']);
});

test('admin sets the table name and suffix of a team in a competition', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CompetitionManager::class)
        ->call('openEditModal', $this->competition->id)
        ->assertSet("display_suffixes.{$this->team->id}", '(kadeci)')
        ->set("display_names.{$this->team->id}", 'KS ASBL Piła')
        ->set("display_suffixes.{$this->team->id}", '(kadet II)')
        ->call('save')
        ->assertHasNoErrors();

    $pivot = $this->competition->teams()->first()->pivot;
    expect($pivot->display_name)->toBe('KS ASBL Piła')->and($pivot->display_suffix)->toBe('(kadet II)');
});

test('articles can be linked to several matches', function () {
    $this->actingAs(User::factory()->admin()->create());
    $first = calendarGame(['game_date' => '2026-10-04 17:00', 'opponent' => 'Wilki Wronki']);
    $second = calendarGame(['game_date' => '2026-10-11 17:00', 'opponent' => 'Orły Ostrów']);

    Livewire::test(ArticleManager::class)
        ->call('openCreateModal')
        ->set('gameSearch', 'wilki')
        ->assertSee('Wilki Wronki')
        ->assertDontSee('Orły Ostrów')
        ->call('addGame', $first->id)
        ->call('addGame', $second->id)
        ->set('title', 'Weekend kadetów')
        ->set('content', '<p>Dwa mecze</p>')
        ->set('status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    expect(Article::where('title', 'Weekend kadetów')->first()->games->pluck('id')->sort()->values()->all())->toBe([$first->id, $second->id]);
});

test('player and linked parent propose changes which the team trainer approves', function () {
    $trainer = User::factory()->trainer()->create();
    $this->team->update(['trainer_id' => $trainer->id]);
    $otherTrainer = User::factory()->trainer()->create();
    $parent = User::factory()->parent()->create();
    $stranger = User::factory()->fan()->create();
    $player = Player::factory()->create(['team_id' => $this->team->id, 'height' => 180, 'block_reach' => null, 'bio' => '<p>Stary opis</p>']);
    $player->guardians()->attach($parent->id);

    $this->actingAs($stranger);
    Livewire::test(PlayerProposalForm::class, ['player' => $player])->call('submit')->assertForbidden();

    $this->actingAs($parent);
    Livewire::test(PlayerProposalForm::class, ['player' => $player])
        ->assertSet('data.bio', 'Stary opis')
        ->set('data.height', 186)
        ->set('data.block_reach', 312)
        ->set('data.bio', "Atakujący.\n\nKapitan <b>kadetów</b>")
        ->call('submit')
        ->assertHasNoErrors();

    $request = PlayerChangeRequest::sole();
    expect($request->proposed_data)->toBe(['height' => 186, 'block_reach' => 312, 'bio' => "Atakujący.\n\nKapitan <b>kadetów</b>"])
        ->and($player->fresh()->height)->toBe(180);

    $this->actingAs($otherTrainer);
    Livewire::test(PlayerChangeRequestManager::class)->assertDontSee($player->full_name)->call('approve', $request->id)->assertForbidden();

    $this->actingAs($trainer);
    $this->get(route('admin.players.index'))->assertSeeHtml('title="Oczekuje na zatwierdzenie">1</span>');
    Livewire::test(PlayerChangeRequestManager::class)->assertSee($player->full_name)->call('approve', $request->id);

    $player->refresh();
    expect($player->height)->toBe(186)
        ->and($player->block_reach)->toBe(312)
        ->and($player->bio)->toBe('<p>Atakujący.</p><p>Kapitan &lt;b&gt;kadetów&lt;/b&gt;</p>')
        ->and($request->fresh()->status)->toBe('approved');
});

test('a player uploads a cropped portrait photo that goes live after approval', function () {
    Storage::fake('public');
    $account = User::factory()->player()->create();
    $player = Player::factory()->create(['team_id' => $this->team->id, 'user_id' => $account->id, 'photo_path' => null]);

    $this->actingAs($account);
    Livewire::test(PlayerProposalForm::class, ['player' => $player])
        ->set('photo', UploadedFile::fake()->image('ja.jpg', 1200, 900))
        ->call('submit')
        ->assertHasErrors('photo');

    Livewire::test(PlayerProposalForm::class, ['player' => $player])
        ->set('photo', UploadedFile::fake()->image('ja.jpg', 800, 1000))
        ->call('submit')
        ->assertHasNoErrors();

    $request = PlayerChangeRequest::sole();
    expect($request->type)->toBe('photo');
    Storage::disk('public')->assertExists($request->photo_path);

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(PlayerChangeRequestManager::class)->call('approve', $request->id);

    $photo = $player->fresh()->photo_path;
    expect($photo)->toStartWith('players/');
    Storage::disk('public')->assertExists($photo);
    Storage::disk('public')->assertMissing($request->photo_path);
});

test('player profile shows physical data, career, achievements and recent matches', function () {
    $player = Player::factory()->create(['first_name' => 'Jan', 'last_name' => 'Nowak', 'team_id' => $this->team->id, 'height' => 192, 'spike_reach' => 330, 'block_reach' => 310]);
    $player->teams()->attach($this->team->id, ['season_id' => $this->season->id, 'jersey_number' => 7, 'is_captain' => true]);
    $player->achievements()->create(['season' => '2025/26', 'title' => 'Mistrzostwo Wielkopolski kadetów']);
    calendarGame(['is_home' => true, 'game_date' => '2026-10-04 17:00', 'status' => 'finished', 'home_score' => 3, 'away_score' => 2]);

    $this->get(route('players.show', $player))
        ->assertOk()
        ->assertSee('Zasięg w bloku')
        ->assertSee('310 cm')
        ->assertSee('Kariera klubowa')
        ->assertSee('2026/2027')
        ->assertSee('Liga Kadetów')
        ->assertSee('Mistrzostwo Wielkopolski kadetów')
        ->assertSee('Ostatnie mecze')
        ->assertSee('UKS Kłos');
});

test('main menu starts with news, matches and standings', function () {
    $this->get('/kontakt')->assertSeeInOrder(['href="'.url('/aktualnosci').'"', 'href="'.url('/mecze').'"', 'href="'.url('/tabele').'"', 'href="'.url('/druzyny').'"'], false);
});
