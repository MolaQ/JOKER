<?php

use App\Livewire\Admin\GalleryManager;
use App\Livewire\Admin\PhotoTagReview;
use App\Livewire\PhotoTagProposal;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\PhotoTag;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet']);
    $this->game = Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'game_date' => now()->subDays(3)]);
    $this->player = Player::factory()->create(['first_name' => 'Marcel', 'last_name' => 'Molak', 'team_id' => $this->team->id]);
});

function galleryWithPhotos(int $count, array $attributes = []): Gallery
{
    $gallery = Gallery::create($attributes + ['title' => 'Turniej w Pile', 'is_published' => true, 'event_date' => now()->toDateString()]);

    foreach (range(1, $count) as $order) {
        $gallery->photos()->create(['path' => "galleries/{$gallery->id}/{$order}.jpg", 'display_order' => $order]);
    }

    return $gallery->fresh('photos');
}

test('an event gallery without a match has its own page and listing', function () {
    $gallery = galleryWithPhotos(2, ['description' => 'Obóz letni']);

    $this->get('/galleries')->assertOk()->assertSee('Turniej w Pile');
    $this->get(route('galleries.show', $gallery))->assertOk()->assertSee('Obóz letni')->assertSee('ImageGallery');
    $this->get('/galleries/'.$gallery->id)->assertRedirect(route('galleries.show', $gallery));
    $this->get('/galerie/'.$gallery->id)->assertRedirect('/galleries/'.$gallery->id);
    $this->get('/sitemap.xml')->assertSee(route('galleries.show', $gallery), false);

    $gallery->update(['is_published' => false]);
    $this->get(route('galleries.show', $gallery))->assertNotFound();
});

test('match details show a few thumbnails and link to the gallery, the gallery links back', function () {
    $gallery = galleryWithPhotos(12, ['match_type' => $this->game->getMorphClass(), 'match_id' => $this->game->id]);

    $response = $this->get(route('games.show', $this->game))->assertOk()
        ->assertSee(route('galleries.show', $gallery), false)
        ->assertSee('Zobacz całą galerię (12)');
    expect(preg_match_all('#galleries/'.$gallery->id.'/\d+\.jpg#', $response->getContent()))->toBe(8);

    $this->get(route('galleries.show', $gallery))->assertSee(route('games.show', $this->game), false);
});

test('tags link to profiles and hide players without image consent', function () {
    $gallery = galleryWithPhotos(1);
    $photo = $gallery->photos->first();
    $photo->tags()->create(['taggable_type' => $this->player->getMorphClass(), 'taggable_id' => $this->player->id, 'status' => 'approved']);
    $photo->tags()->create(['taggable_type' => $this->team->getMorphClass(), 'taggable_id' => $this->team->id, 'status' => 'approved']);

    $this->get(route('galleries.show', $gallery))->assertSee('Marcel Molak')->assertSee(route('players.show', $this->player), false)->assertSee(route('teams.show', $this->team), false);
    $this->get(route('players.show', $this->player))->assertSee('id="zdjecia"', false);

    $this->player->update(['image_consent' => false]);

    $this->get(route('galleries.show', $gallery))->assertDontSee('Marcel Molak')->assertSee(route('teams.show', $this->team), false);
    $this->get(route('players.show', $this->player))->assertDontSee('id="zdjecia"', false);
});

test('a parent withdraws image consent for a child in my profile', function () {
    $parent = User::factory()->parent()->create();
    $parent->children()->attach($this->player->id);

    $this->actingAs($parent)->put(route('profile.consent', $this->player), ['image_consent' => '0'])->assertSessionHas('success');
    expect($this->player->fresh()->image_consent)->toBeFalse();

    $this->actingAs(User::factory()->parent()->create())->put(route('profile.consent', $this->player), ['image_consent' => '1'])->assertForbidden();
});

test('a parent proposes a tag and the team trainer approves it', function () {
    $gallery = galleryWithPhotos(1);
    $photo = $gallery->photos->first();
    $parent = User::factory()->parent()->create();
    $parent->children()->attach($this->player->id);

    $this->actingAs($parent);
    Livewire::test(PhotoTagProposal::class)
        ->dispatch('propose-tag', photo: $photo->id)
        ->assertSee('Marcel Molak')
        ->call('propose', $this->player->id);

    $tag = PhotoTag::firstOrFail();
    expect($tag->status)->toBe('pending');
    $this->get(route('galleries.show', $gallery))->assertDontSee(route('players.show', $this->player), false);

    $otherTrainer = User::factory()->trainer()->create();
    expect(PhotoTagReview::pendingFor($otherTrainer))->toBeEmpty();

    $trainer = User::factory()->trainer()->create();
    $this->team->update(['trainer_id' => $trainer->id]);
    $this->actingAs($trainer);
    Livewire::test(PhotoTagReview::class)->assertSee('Marcel Molak')->call('approve', $tag->id);

    expect($tag->fresh()->status)->toBe('approved');
});

test('the panel creates an event gallery, uploads photos, captions and tags them', function () {
    $this->actingAs(User::factory()->editor()->create());

    $component = Livewire::test(GalleryManager::class)
        ->call('create')
        ->set('title', 'Obóz w Wałczu')
        ->set('team_id', $this->team->id)
        ->set('uploads', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    $gallery = Gallery::firstWhere('title', 'Obóz w Wałczu');
    $photo = $gallery->photos->first();
    expect($gallery->photos)->toHaveCount(2)->and($gallery->match_id)->toBeNull();

    $component->set("captions.{$photo->id}", 'Rozgrzewka')
        ->call('startTagging', $photo->id)
        ->set('tagSearch', 'Molak')
        ->assertSee('Marcel Molak')
        ->call('addTag', 'player:'.$this->player->id);

    expect($photo->fresh()->caption)->toBe('Rozgrzewka')
        ->and($photo->tags()->approved()->count())->toBe(1);

    $this->get('/panel/galleries')->assertOk();
});
