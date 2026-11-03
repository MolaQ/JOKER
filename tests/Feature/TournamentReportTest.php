<?php

use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\TournamentEditor;
use App\Models\Article;
use App\Models\Gallery;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Support\TournamentReport;
use Livewire\Livewire;

beforeEach(function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $team = Team::create(['name' => 'Kadetki', 'slug' => 'kadetki', 'category' => 'youth']);
    $this->tournament = Tournament::create(['name' => 'Memoriał', 'edition' => 'XXV', 'start_date' => '2026-11-06', 'end_date' => '2026-11-08', 'has_page' => true]);
    $category = $this->tournament->categories()->create(['name' => 'Kadetki', 'display_order' => 0]);
    $group = $category->groups()->create(['name' => 'Grupa A', 'display_order' => 0]);
    $joker = $category->participants()->create(['tournament_group_id' => $group->id, 'competitor_type' => 'team', 'competitor_id' => $team->id]);
    $kalisz = $category->participants()->create(['tournament_group_id' => $group->id, 'competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'UKS Dwójka Kalisz'])->id]);
    $this->match = $group->matches()->create(['tournament_category_id' => $category->id, 'stage' => TournamentMatch::STAGE_GROUP, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id]);
    $category->awards()->create(['name' => 'MVP turnieju', 'person_name' => 'Anna Nowak', 'tournament_participant_id' => $joker->id]);
});

test('the results report waits for the last match and then drafts podium and awards once', function () {
    $this->actingAs(User::factory()->admin()->create());

    expect(TournamentReport::draft($this->tournament))->toBeNull();

    $this->match->update(['home_sets' => 2, 'away_sets' => 0, 'sets_score' => [[25, 20], [25, 18]]]);
    $article = TournamentReport::draft($this->tournament->fresh());

    expect($article->status)->toBe('draft')
        ->and($article->tournament_id)->toBe($this->tournament->id)
        ->and($article->title)->toBe('XXV Memoriał: wyniki i klasyfikacja')
        ->and($article->content)->toContain('<h2>Kadetki</h2>')->toContain('🥇 LOMS Joker Piła (kadetki)')->toContain('🥈 UKS Dwójka Kalisz')->toContain('MVP turnieju: Anna Nowak, LOMS Joker Piła (kadetki)')
        ->and(TournamentReport::draft($this->tournament->fresh())->id)->toBe($article->id)
        ->and(Article::count())->toBe(1);
});

test('the organizer opens the report draft straight from the tournament panel', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->call('prepareReport')->assertNoRedirect();

    $this->match->update(['home_sets' => 2, 'away_sets' => 1, 'sets_score' => [[25, 20], [20, 25], [15, 10]]]);
    $article = null;
    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament->fresh()])->call('prepareReport')
        ->assertRedirect(route('admin.articles.index', ['edytuj' => ($article = Article::first())->id]));

    Livewire::withQueryParams(['edytuj' => $article->id])->test(ArticleManager::class)
        ->assertSet('showModal', true)->assertSet('editingId', $article->id)->assertSet('tournamentId', (string) $this->tournament->id)
        ->set('status', 'published')->call('save')->assertHasNoErrors();

    expect($article->fresh()->isPublished())->toBeTrue()->and($article->fresh()->tournament_id)->toBe($this->tournament->id);
});

test('the tournament page lists its reports and match photos, and the report links back', function () {
    $article = Article::factory()->create(['title' => 'Memoriał: wyniki', 'status' => 'published', 'published_at' => now()->subHour(), 'tournament_id' => $this->tournament->id, 'author_id' => User::factory()->create()->id]);
    $gallery = Gallery::create(['title' => 'Kadetki – Kalisz', 'is_published' => true, 'match_type' => 'tournament_match', 'match_id' => $this->match->id]);
    $gallery->photos()->create(['path' => 'galleries/1/1.jpg', 'display_order' => 1]);
    Gallery::create(['title' => 'Pusta galeria', 'is_published' => true, 'match_type' => 'tournament_match', 'match_id' => $this->match->id]);

    $this->get(route('tournaments.show', $this->tournament))->assertOk()
        ->assertSee('Relacje z turnieju')->assertSee('Memoriał: wyniki')
        ->assertSee('Zdjęcia z turnieju')->assertSee('Kadetki – Kalisz')->assertDontSee('Pusta galeria');

    $this->get(route('articles.show', $article))->assertOk()->assertSee('Strona turnieju')->assertSee(route('tournaments.show', $this->tournament), false);
});
