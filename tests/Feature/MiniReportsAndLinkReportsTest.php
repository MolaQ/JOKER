<?php

use App\Livewire\Admin\ContentProposalManager;
use App\Livewire\Admin\LinkReportReview;
use App\Livewire\MiniSubmissions;
use App\Models\Article;
use App\Models\ContentProposal;
use App\Models\Game;
use App\Models\LinkReport;
use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function linkTestGame(array $attributes): Game
{
    $season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $team = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy-linki', 'category' => 'senior']);

    return Game::factory()->create(['team_id' => $team->id, 'season_id' => $season->id] + $attributes);
}

beforeEach(function () {
    Storage::fake('public');
    MiniGroup::query()->update(['is_active' => true]);
    $this->group = MiniGroup::firstWhere('slug', 'czworki');
    $this->child = Player::create(['mini_group_id' => $this->group->id, 'first_name' => 'Antek', 'last_name' => 'Kowalski', 'is_active' => true]);
    $this->trip = MiniTrip::create(['title' => 'Turniej w Lesznie', 'city' => 'Leszno', 'starts_on' => now()->subDay(), 'status' => MiniTrip::STATUS_PUBLISHED]);
    $this->trip->teams()->create(['mini_group_id' => $this->group->id, 'name' => 'Joker 1', 'place' => 2]);
    $this->parent = User::factory()->parent()->create();
    $this->parent->children()->attach($this->child->id);
});

test('a parent of a mini child gets only the report and photos tabs', function () {
    $this->actingAs($this->parent)->get(route('mini.show', $this->group))->assertOk()->assertSee('Zgłoś relację')->assertDontSee('Turnieje, wyniki, dzieci');

    Livewire::actingAs($this->parent)->test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->assertSet('section', 'relacja')->assertSee('Zdjęcia')->assertDontSee('Nowy turniej')
        ->set('section', 'dzieci')->assertSet('section', 'relacja')
        ->call('saveTrip')->assertForbidden();

    $other = User::factory()->parent()->create();
    Livewire::actingAs($other)->test(MiniSubmissions::class)->assertForbidden();
});

test('a parent report with a youtube film becomes an article with the film after approval', function () {
    Livewire::actingAs($this->parent)->test(MiniSubmissions::class)
        ->set('reportTrip', (string) $this->trip->id)->set('reportTitle', 'Srebro w Lesznie')
        ->set('reportContent', 'Nasze czwórki zagrały świetny turniej i wróciły z medalem.')
        ->set('reportVideo', 'https://vimeo.com/123')->call('sendReport')->assertHasErrors('reportVideo')
        ->set('reportVideo', 'https://youtu.be/dQw4w9WgXcQ')->call('sendReport')->assertHasNoErrors();

    $proposal = ContentProposal::sole();
    expect($proposal->url)->toBe('https://youtu.be/dQw4w9WgXcQ');

    Livewire::actingAs(User::factory()->admin()->create())->test(ContentProposalManager::class)->call('approve', $proposal->id);

    $article = Article::sole();
    expect($article->mini_trip_id)->toBe($this->trip->id)
        ->and($this->trip->fresh()->normalizedStreams()[0]['youtube_id'])->toBe('dQw4w9WgXcQ');

    $this->get(route('articles.show', $article))->assertOk()->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    $this->get(route('mini.show', $this->group))->assertOk()->assertSee('https://youtu.be/dQw4w9WgXcQ', false);
});

test('photos from a mini tournament wait for approval and only the selected ones go to the tournament gallery', function () {
    Livewire::actingAs($this->parent)->test(MiniSubmissions::class)
        ->set('photosTrip', (string) $this->trip->id)
        ->set('photos', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')])
        ->call('sendPhotos')->assertHasNoErrors();

    $proposal = ContentProposal::sole();
    expect($proposal->type)->toBe(ContentProposal::TYPE_PHOTOS)->and($proposal->files)->toHaveCount(3)->and($this->trip->galleries()->count())->toBe(0);
    $skippedPath = $proposal->files[1];

    Livewire::actingAs(User::factory()->admin()->create())->test(ContentProposalManager::class)
        ->assertSee('Zatwierdź wszystkie (3)')
        ->call('setAllPhotos', $proposal->id, false)->call('approve', $proposal->id)
        ->assertDispatched('toast', type: 'error')
        ->call('setAllPhotos', $proposal->id, true)->call('toggleSkip', $proposal->id, 1)
        ->assertSee('Zatwierdź wybrane (2)')
        ->call('approve', $proposal->id);

    $gallery = $this->trip->galleries()->sole();
    expect($gallery->photos()->count())->toBe(2)
        ->and($gallery->event_date->toDateString())->toBe($this->trip->starts_on->toDateString())
        ->and(Storage::disk('public')->exists($skippedPath))->toBeFalse()
        ->and($proposal->fresh()->status)->toBe(ContentProposal::STATUS_APPROVED);

    $this->get(route('mini.show', ['miniGroup' => $this->group, 'tab' => 'wyniki']))->assertOk();
    $this->get(route('galleries.show', $gallery))->assertOk()->assertSee('Turniej');
});

test('a logged in user reports a broken film link and an admin unpins it', function () {
    $game = linkTestGame(['streams' => [['url' => 'https://youtu.be/dQw4w9WgXcQ', 'label' => ''], ['url' => 'https://www.facebook.com/joker/videos/1', 'label' => '']]]);
    $fan = User::factory()->create(['role' => UserRole::Fan]);

    $this->postJson(route('links.report'), ['key' => 'game:'.$game->id, 'url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertUnauthorized();
    $this->actingAs($fan)->get(route('games.show', $game))->assertOk()->assertSee('Zgłoś niedziałający link');
    $this->actingAs($fan)->postJson(route('links.report'), ['key' => 'game:'.$game->id, 'url' => 'https://example.com/obcy'])->assertStatus(422);
    $this->actingAs($fan)->postJson(route('links.report'), ['key' => 'game:'.$game->id, 'url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertOk();
    $this->actingAs(User::factory()->create(['role' => UserRole::Fan]))->postJson(route('links.report'), ['key' => 'game:'.$game->id, 'url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertOk();

    expect(LinkReport::count())->toBe(2);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.link-reports.index'))->assertOk()->assertSee('Niedziałające linki');

    Livewire::actingAs($admin)->test(LinkReportReview::class)->assertSee('https://youtu.be/dQw4w9WgXcQ')
        ->call('remove', LinkReport::first()->id)->assertDontSee('https://youtu.be/dQw4w9WgXcQ');

    expect(collect($game->fresh()->normalizedStreams())->pluck('url')->all())->toBe(['https://www.facebook.com/joker/videos/1'])
        ->and(LinkReport::pending()->count())->toBe(0);

    Livewire::actingAs($fan)->test(LinkReportReview::class)->assertForbidden();
});

test('a reported link that works is kept', function () {
    $game = linkTestGame(['streams' => [['url' => 'https://youtu.be/dQw4w9WgXcQ', 'label' => '']]]);
    $this->actingAs(User::factory()->create(['role' => UserRole::Fan]))->postJson(route('links.report'), ['key' => 'game:'.$game->id, 'url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertOk();

    Livewire::actingAs(User::factory()->admin()->create())->test(LinkReportReview::class)->call('dismiss', LinkReport::sole()->id);

    expect($game->fresh()->normalizedStreams())->toHaveCount(1)->and(LinkReport::sole()->status)->toBe(LinkReport::STATUS_DISMISSED);
});
