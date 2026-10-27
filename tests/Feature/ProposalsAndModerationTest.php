<?php

use App\Livewire\Admin\CommentModeration;
use App\Livewire\Admin\ContentProposalManager;
use App\Livewire\ContentProposalForm;
use App\Models\Article;
use App\Models\Comment;
use App\Models\ContentProposal;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet']);
    $season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->game = Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $season->id, 'game_date' => now()->subDays(2)]);
});

test('fan comments wait for a moderator, parent comments do not', function () {
    $article = Article::factory()->for(User::factory(), 'author')->create(['status' => 'published', 'published_at' => now()->subDay(), 'allow_comments' => true]);

    $this->actingAs(User::factory()->fan()->create())->post(route('articles.comments.store', $article), ['content' => 'Brawo!']);
    $this->actingAs(User::factory()->parent()->create())->post(route('articles.comments.store', $article), ['content' => 'Super mecz']);

    expect(Comment::where('content', 'Brawo!')->value('is_approved'))->toBeFalse()
        ->and(Comment::where('content', 'Super mecz')->value('is_approved'))->toBeTrue();
    $this->get(route('articles.show', $article))->assertDontSee('Brawo!')->assertSee('Super mecz');

    $this->actingAs(User::factory()->editor()->create());
    Livewire::test(CommentModeration::class)
        ->assertSee('Brawo!')
        ->call('approve', 'article', Comment::where('content', 'Brawo!')->value('id'));

    $this->get(route('articles.show', $article))->assertSee('Brawo!');
});

test('a parent proposes an article that becomes news after approval', function () {
    $parent = User::factory()->parent()->create(['name' => 'Anna Nowak']);
    $this->actingAs($parent);

    Livewire::test(ContentProposalForm::class)
        ->set('type', 'article')
        ->set('title', 'Wygrana kadetów')
        ->set('content', "Pierwszy akapit.\n\nDrugi akapit.")
        ->set('match', 'game:'.$this->game->id)
        ->call('submit')
        ->assertHasNoErrors();

    $proposal = ContentProposal::firstOrFail();
    expect($proposal->status)->toBe('pending');

    $this->actingAs(User::factory()->editor()->create());
    Livewire::test(ContentProposalManager::class)->call('approve', $proposal->id);

    $article = Article::firstWhere('title', 'Wygrana kadetów');
    expect($article->isPublished())->toBeTrue()
        ->and($article->author_id)->toBe($parent->id)
        ->and($article->content)->toBe('<p>Pierwszy akapit.</p><p>Drugi akapit.</p>')
        ->and($article->games->pluck('id')->all())->toBe([$this->game->id])
        ->and($proposal->fresh()->status)->toBe('approved');
});

test('a player proposes photos and a stream for a match', function () {
    $this->actingAs(User::factory()->player()->create());

    Livewire::test(ContentProposalForm::class)
        ->assertViewHas('types', ['photos', 'stream'])
        ->set('type', 'photos')
        ->set('match', 'game:'.$this->game->id)
        ->set('photos', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
        ->call('submit')
        ->assertHasNoErrors()
        ->set('type', 'stream')
        ->set('match', 'game:'.$this->game->id)
        ->set('url', 'https://youtube.com/live/abc')
        ->call('submit')
        ->assertHasNoErrors()
        ->set('type', 'article')
        ->call('submit')
        ->assertHasErrors('type');

    $this->actingAs(User::factory()->admin()->create());
    ContentProposal::all()->each(fn ($proposal) => Livewire::test(ContentProposalManager::class)->call('approve', $proposal->id));

    $gallery = $this->game->galleries()->first();
    expect($gallery->photos)->toHaveCount(2)
        ->and(Storage::disk('public')->exists($gallery->photos->first()->path))->toBeTrue()
        ->and($this->game->fresh()->streams[0]['url'])->toBe('https://youtube.com/live/abc');
});

test('fans and inactive accounts cannot propose content', function () {
    $this->actingAs(User::factory()->fan()->create());
    Livewire::test(ContentProposalForm::class)->call('submit')->assertForbidden();

    $this->actingAs(User::factory()->parent()->inactive()->create());
    Livewire::test(ContentProposalForm::class)->call('submit')->assertForbidden();
});

test('a rejected proposal removes its photos', function () {
    $user = User::factory()->create(['role' => UserRole::Parent]);
    $path = UploadedFile::fake()->image('x.jpg')->store('content-proposals', 'public');
    $proposal = ContentProposal::create(['user_id' => $user->id, 'type' => 'photos', 'match_type' => $this->game->getMorphClass(), 'match_id' => $this->game->id, 'files' => [$path]]);

    $this->actingAs(User::factory()->editor()->create());
    Livewire::test(ContentProposalManager::class)->set('notes.'.$proposal->id, 'Nieostre')->call('reject', $proposal->id);

    expect(Storage::disk('public')->exists($path))->toBeFalse()
        ->and($proposal->fresh()->review_note)->toBe('Nieostre');
    $this->get('/panel/propozycje')->assertOk();
    $this->actingAs(User::factory()->trainer()->create())->get('/panel/propozycje')->assertForbidden();
});

test('zdjęcia z dopisaną relacją trafiają do galerii, a relacja do aktualności', function () {
    $parent = User::factory()->parent()->create(['name' => 'Dawid Porębiak']);
    $this->actingAs($parent);

    // Rodzic zaczął od artykułu, potem przełączył na „Zdjęcia”: tekst jedzie razem ze zdjęciami.
    Livewire::test(ContentProposalForm::class)
        ->set('type', 'article')
        ->set('title', 'Dreszczowiec z happy endem')
        ->set('content', "Wygraliśmy 3:2!\n\nBrawo chłopaki.")
        ->set('type', 'photos')
        ->set('match', 'game:'.$this->game->id)
        ->set('photos', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
        ->call('submit')
        ->assertHasNoErrors();

    $proposal = ContentProposal::firstOrFail();
    $this->actingAs(User::factory()->editor()->create());

    Livewire::test(ContentProposalManager::class)
        ->assertSee('Zatwierdź: zdjęcia i artykuł')
        ->call('approveWithArticle', $proposal->id);

    $article = Article::firstWhere('title', 'Dreszczowiec z happy endem');
    expect($article->isPublished())->toBeTrue()
        ->and($article->author_id)->toBe($parent->id)
        ->and($article->games->pluck('id')->all())->toBe([$this->game->id])
        ->and(Storage::disk('public')->exists($article->featured_image))->toBeTrue()
        ->and($this->game->galleries()->first()->photos)->toHaveCount(2)
        ->and($proposal->fresh()->status)->toBe('approved');
});

test('relację z wcześniej zatwierdzonych samych zdjęć można opublikować później', function () {
    $parent = User::factory()->parent()->create();
    $this->actingAs($parent);
    Livewire::test(ContentProposalForm::class)
        ->set('type', 'article')->set('title', 'Relacja z derbów')->set('content', 'Świetny mecz.')
        ->set('type', 'photos')->set('match', 'game:'.$this->game->id)
        ->set('photos', [UploadedFile::fake()->image('a.jpg')])
        ->call('submit');
    $proposal = ContentProposal::firstOrFail();

    $this->actingAs(User::factory()->editor()->create());
    Livewire::test(ContentProposalManager::class)->call('approve', $proposal->id);
    expect(Article::where('title', 'Relacja z derbów')->exists())->toBeFalse();

    Livewire::test(ContentProposalManager::class)
        ->set('statusFilter', 'approved')
        ->assertSee('Opublikuj relację jako artykuł')
        ->call('publishReport', $proposal->id)
        ->assertDontSee('Opublikuj relację jako artykuł');

    $article = Article::firstWhere('title', 'Relacja z derbów');
    expect($article->isPublished())->toBeTrue()
        ->and($article->featured_image)->not->toBeNull()
        ->and($article->games->pluck('id')->all())->toBe([$this->game->id]);
});
