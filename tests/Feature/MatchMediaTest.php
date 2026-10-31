<?php

use App\Livewire\Admin\MatchMediaManager;
use App\Livewire\TournamentPage;
use App\Models\Article;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Support\StreamLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Mecz turnieju „Półfinał: Joker – Sparta” w kategorii Kadetki.
 */
function semifinal(): TournamentMatch
{
    $team = Team::create(['name' => 'Kadetki', 'slug' => 'kadetki', 'category' => 'youth']);
    $tournament = Tournament::create(['name' => 'Memoriał', 'edition' => 'XXV', 'start_date' => '2026-11-15', 'has_page' => true]);
    $category = $tournament->categories()->create(['name' => 'Kadetki', 'display_order' => 0]);
    $joker = $category->participants()->create(['competitor_type' => 'team', 'competitor_id' => $team->id, 'display_name' => 'Joker']);
    $sparta = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Sparta'])->id]);

    return $category->matches()->create([
        'stage' => TournamentMatch::STAGE_PLAYOFF,
        'label' => 'Półfinał',
        'home_participant_id' => $joker->id,
        'away_participant_id' => $sparta->id,
        'starts_at' => '2026-11-15 10:00',
    ]);
}

function leagueGame(array $attributes = []): Game
{
    $team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
    $season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);

    return Game::factory()->create($attributes + [
        'team_id' => $team->id,
        'season_id' => $season->id,
        'opponent' => 'MKS Kalisz',
        'is_home' => true,
        'game_date' => '2026-10-21 15:40',
        'league' => 'III liga',
        'status' => 'finished',
        'home_score' => 3,
        'away_score' => 1,
    ]);
}

test('stream links are recognised and youtube links get an embed address', function () {
    expect(StreamLink::youtubeEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10'))->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and(StreamLink::youtubeId('https://youtu.be/dQw4w9WgXcQ?si=abc'))->toBe('dQw4w9WgXcQ')
        ->and(StreamLink::youtubeId('https://www.youtube.com/live/dQw4w9WgXcQ?feature=share'))->toBe('dQw4w9WgXcQ')
        ->and(StreamLink::youtubeId('https://m.youtube.com/shorts/dQw4w9WgXcQ'))->toBe('dQw4w9WgXcQ')
        ->and(StreamLink::youtubeId('https://www.youtube.com/@jokerpila/live'))->toBeNull()
        ->and(StreamLink::platform('https://www.facebook.com/jokerpila/videos/123'))->toBe(StreamLink::FACEBOOK)
        ->and(StreamLink::platform('https://fb.watch/abc/'))->toBe(StreamLink::FACEBOOK)
        ->and(StreamLink::platform('https://notyoutube.com/watch?v=dQw4w9WgXcQ'))->toBe(StreamLink::OTHER);

    $streams = StreamLink::normalize([
        ['url' => 'https://youtu.be/dQw4w9WgXcQ', 'label' => 'Nagranie rodziców'],
        ['url' => 'https://www.facebook.com/jokerpila/videos/123', 'label' => ''],
        ['url' => 'nie-link', 'label' => 'Zły'],
        ['url' => 'https://youtu.be/dQw4w9WgXcQ', 'label' => 'Duplikat'],
    ]);

    expect($streams)->toHaveCount(2)
        ->and($streams[0])->toMatchArray(['label' => 'Nagranie rodziców', 'platform' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ'])
        ->and($streams[1])->toMatchArray(['label' => 'Facebook', 'platform' => 'facebook', 'embed_url' => null]);
});

test('game streams include the older video link field', function () {
    $game = leagueGame(['streams' => [['url' => 'https://www.facebook.com/jokerpila/videos/1', 'label' => 'Klub']], 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

    expect(collect($game->fresh()->normalizedStreams())->pluck('platform')->all())->toBe(['facebook', 'youtube']);
});

test('match picker labels identify league and tournament matches', function () {
    $game = leagueGame();
    $match = semifinal();

    expect($game->fresh()->mediaLabel())->toBe('21.10.2026 15:40 · Kadeci · Joker Piła – MKS Kalisz (III liga)')
        ->and($match->fresh()->mediaLabel())->toBe('15.11.2026 10:00 · XXV Memoriał · Kadetki · Półfinał: Joker – Sparta');

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(MatchMediaManager::class)
        ->set('search', 'kalisz')
        ->assertSee('Mecze ligowe')
        ->assertSee('Joker Piła – MKS Kalisz')
        ->assertDontSee('Półfinał: Joker – Sparta')
        ->set('search', 'memoriał sparta')
        ->assertSee('Półfinał: Joker – Sparta')
        ->assertDontSee('MKS Kalisz (III liga)');
});

test('media manager page is only for staff', function () {
    $this->get(route('admin.media.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/panel/media')
        ->assertOk()
        ->assertSee('Transmisje i galerie');
});

test('media manager saves streams and creates a gallery with photos for a game', function () {
    Storage::fake('public');
    $game = leagueGame();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(MatchMediaManager::class)
        ->set('selected', 'game:'.$game->id)
        ->call('addStream')
        ->set('streams.0.url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->set('streams.0.label', 'Nagranie rodziców')
        ->call('addStream')
        ->set('streams.1.url', 'https://www.facebook.com/jokerpila/videos/123')
        ->call('saveStreams')
        ->assertHasNoErrors()
        ->assertDispatched('toast')
        ->set('galleryTitle', 'Zdjęcia z hali')
        ->set('galleryPhotos', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
        ->call('createGallery')
        ->assertHasNoErrors()
        ->assertSee('Zdjęcia z hali');

    expect($game->fresh()->streams)->toBe([
        ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'label' => 'Nagranie rodziców'],
        ['url' => 'https://www.facebook.com/jokerpila/videos/123', 'label' => ''],
    ]);

    $gallery = $game->galleries()->with('photos')->sole();
    expect($gallery->title)->toBe('Zdjęcia z hali')
        ->and($gallery->is_published)->toBeTrue()
        ->and($gallery->photos)->toHaveCount(2)
        ->and($gallery->photos->pluck('display_order')->all())->toBe([0, 1]);
    $gallery->photos->each(fn ($photo) => Storage::disk('public')->assertExists($photo->path));
});

test('media manager rejects invalid stream links', function () {
    $game = leagueGame();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(MatchMediaManager::class)
        ->set('selected', 'game:'.$game->id)
        ->set('streams', [['url' => 'nie-link', 'label' => '']])
        ->call('saveStreams')
        ->assertHasErrors('streams.0.url');

    expect($game->fresh()->streams)->toBeNull();
});

test('media manager edits, reorders and deletes photos and galleries', function () {
    Storage::fake('public');
    $game = leagueGame();
    $this->actingAs(User::factory()->admin()->create());

    $component = Livewire::test(MatchMediaManager::class)
        ->set('selected', 'game:'.$game->id)
        ->set('galleryPhotos', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
        ->call('createGallery');

    $gallery = $game->galleries()->sole();
    [$first, $second] = $gallery->photos->all();

    $component->call('editGallery', $gallery->id)
        ->set('captions.'.$first->id, 'Blok na siatce')
        ->set('morePhotos', [UploadedFile::fake()->image('c.jpg')])
        ->call('saveGallery')
        ->assertHasNoErrors()
        ->call('movePhoto', $second->id, -1)
        ->call('togglePublished', $gallery->id);

    $gallery->refresh();
    expect($gallery->is_published)->toBeFalse()
        ->and($gallery->photos)->toHaveCount(3)
        ->and($gallery->photos->first()->id)->toBe($second->id)
        ->and($first->fresh()->caption)->toBe('Blok na siatce');

    $component->call('deletePhoto', $first->id);
    Storage::disk('public')->assertMissing($first->path);

    $component->call('deleteGallery', $gallery->id);
    expect(Gallery::count())->toBe(0);
    Storage::disk('public')->assertMissing($second->path);
});

test('media manager saves streams and galleries for a tournament match', function () {
    Storage::fake('public');
    $match = semifinal();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(MatchMediaManager::class)
        ->set('selected', 'tournament_match:'.$match->id)
        ->set('streams', [['url' => 'https://youtu.be/dQw4w9WgXcQ', 'label' => 'Transmisja klubu']])
        ->call('saveStreams')
        ->set('galleryPhotos', [UploadedFile::fake()->image('a.jpg')])
        ->call('createGallery')
        ->assertHasNoErrors();

    expect($match->fresh()->streams)->toBe([['url' => 'https://youtu.be/dQw4w9WgXcQ', 'label' => 'Transmisja klubu']]);
    $gallery = Gallery::sole();
    expect($gallery->match_type)->toBe('tournament_match')
        ->and($gallery->match->is($match))->toBeTrue()
        ->and($gallery->title)->toBe('Zdjęcia: Joker – Sparta');
});

test('game page shows result, streams, galleries and linked articles', function () {
    $game = leagueGame(['streams' => [
        ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'label' => 'Nagranie rodziców'],
        ['url' => 'https://www.facebook.com/jokerpila/videos/123', 'label' => 'Transmisja klubu'],
    ], 'sets_score' => [[25, 20], [23, 25], [25, 18], [25, 21]]]);
    $gallery = Gallery::factory()->create(['match_type' => $game->getMorphClass(), 'match_id' => $game->id, 'title' => 'Zdjęcia z hali']);
    $gallery->photos()->create(['path' => 'galleries/1/a.jpg', 'caption' => 'Atak z drugiej linii']);
    Gallery::factory()->unpublished()->create(['match_type' => $game->getMorphClass(), 'match_id' => $game->id, 'title' => 'Ukryta galeria'])
        ->photos()->create(['path' => 'galleries/2/a.jpg']);
    $article = Article::factory()->for(User::factory(), 'author')->create(['title' => 'Relacja z wygranej', 'status' => 'published', 'published_at' => now()->subDay()]);
    $article->games()->attach($game);

    $this->get('/matches/'.$game->id)
        ->assertOk()
        ->assertSee('MKS Kalisz')
        ->assertSee('23:25')
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
        ->assertSee('Nagranie rodziców')
        ->assertSee('https://www.facebook.com/jokerpila/videos/123', false)
        ->assertSee('Zdjęcia z hali')
        ->assertSee('Atak z drugiej linii')
        ->assertDontSee('Ukryta galeria')
        ->assertSee('Relacja z wygranej')
        ->assertSee(route('articles.show', $article), false);

    $this->get(route('games.calendar', $game))->assertOk();
});

test('article linked to a game shows its streams and galleries', function () {
    $game = leagueGame(['streams' => [['url' => 'https://youtu.be/dQw4w9WgXcQ', 'label' => 'Cały mecz']]]);
    Gallery::factory()->create(['match_type' => $game->getMorphClass(), 'match_id' => $game->id, 'title' => 'Fotorelacja'])
        ->photos()->create(['path' => 'galleries/1/a.jpg']);
    $article = Article::factory()->for(User::factory(), 'author')->create(['status' => 'published', 'published_at' => now()->subDay()]);
    $article->games()->attach($game);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('Cały mecz')
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
        ->assertSee('Fotorelacja')
        ->assertSee(route('games.show', $game), false);
});

test('tournament match card shows stream and gallery icons and the match page shows the gallery', function () {
    $match = semifinal();
    $match->update(['streams' => [['url' => 'https://youtu.be/dQw4w9WgXcQ', 'label' => '']]]);
    $gallery = $match->galleries()->create(['title' => 'Półfinał w obiektywie']);
    $gallery->photos()->createMany([['path' => 'galleries/1/a.jpg'], ['path' => 'galleries/1/b.jpg']]);
    $tournament = $match->category->tournament;

    $this->get(route('tournaments.show', $tournament))->assertOk();

    Livewire::test(TournamentPage::class, ['tournament' => $tournament])
        ->call('selectTab', 'matches')
        ->assertSeeText('Transmisja')
        ->assertSee('https://youtu.be/dQw4w9WgXcQ', false)
        ->assertSee('📷 2')
        ->assertSee(route('tournaments.matches.show', [$tournament, $match]), false);

    $this->get(route('tournaments.matches.show', [$tournament, $match]))
        ->assertOk()
        ->assertSee('Półfinał w obiektywie')
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

    $other = Tournament::create(['name' => 'Inny turniej', 'start_date' => '2026-12-01', 'has_page' => true]);
    $this->get(route('tournaments.matches.show', [$other, $match]))->assertNotFound();
});
