<?php

use App\Livewire\Admin\ApprovalEmailToggle;
use App\Livewire\Admin\CommentModeration;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ApprovalsWaiting;
use App\Permission;
use App\Support\Approvals;
use App\UserRole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Once;
use Livewire\Livewire;

beforeEach(function () {
    $season = Season::factory()->create(['is_current' => true]);
    $this->cadets = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet']);
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $this->cadetGame = Game::factory()->create(['team_id' => $this->cadets->id, 'season_id' => $season->id]);
    $this->seniorGame = Game::factory()->create(['team_id' => $this->seniors->id, 'season_id' => $season->id]);
});

afterEach(fn () => Carbon::setTestNow());

test('moderator akceptuje konta i komentarze w całym klubie, fotograf zarządza zdjęciami', function () {
    $moderator = User::factory()->create(['role' => UserRole::Moderator]);
    $photographer = User::factory()->create(['role' => UserRole::Photographer]);

    expect($moderator->hasPermission(Permission::Comments))->toBeTrue()
        ->and($moderator->hasPermission(Permission::ActivateAccounts))->toBeTrue()
        ->and($moderator->managedTeamIds())->toBeNull()
        ->and($moderator->hasPermission(Permission::Teams))->toBeFalse()
        ->and($photographer->hasPermission(Permission::Media))->toBeTrue()
        ->and($photographer->hasPermission(Permission::PhotoTags))->toBeTrue()
        ->and($photographer->hasPermission(Permission::Articles))->toBeFalse()
        ->and(Approvals::sectionsFor($photographer)->keys()->all())->toBe(['oznaczenia']);

    $this->actingAs($photographer)->get('/panel/galerie')->assertOk();
    $this->actingAs($photographer)->get('/panel/articles')->assertForbidden();
});

test('koordynator moderuje tylko komentarze pod meczami swoich drużyn, a panel zapisuje, kto opublikował', function () {
    $coordinator = User::factory()->create(['name' => 'Kasia Koordynator', 'role' => UserRole::Coordinator]);
    $coordinator->coordinatedTeams()->sync([$this->cadets->id]);
    $fan = User::factory()->create();

    $mine = GameComment::create(['game_id' => $this->cadetGame->id, 'user_id' => $fan->id, 'content' => 'Brawo kadeci', 'is_approved' => false]);
    $other = GameComment::create(['game_id' => $this->seniorGame->id, 'user_id' => $fan->id, 'content' => 'Brawo seniorzy', 'is_approved' => false]);
    $article = Comment::create(['article_id' => Article::factory()->create(['author_id' => $fan->id])->id, 'user_id' => $fan->id, 'content' => 'Pod artykułem', 'is_approved' => false]);

    expect(CommentModeration::pendingCount($coordinator))->toBe(1);

    $this->actingAs($coordinator)->get('/panel/komentarze')->assertOk()->assertSee('Brawo kadeci')->assertDontSee('Brawo seniorzy')->assertDontSee('Pod artykułem');

    Livewire::test(CommentModeration::class)->call('approve', 'game', $mine->id);
    expect($mine->fresh()->is_approved)->toBeTrue()->and($mine->fresh()->approved_by)->toBe($coordinator->id);

    expect(fn () => Livewire::test(CommentModeration::class)->call('approve', 'game', $other->id))->toThrow(Exception::class);
    Livewire::test(CommentModeration::class)->call('approve', 'article', $article->id)->assertNotFound();
    expect($article->fresh()->is_approved)->toBeFalse()->and($other->fresh()->is_approved)->toBeFalse();

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(CommentModeration::class)->set('showAll', true)->assertSee('Opublikował(a): Kasia Koordynator');
});

test('e-mail o sprawach do zatwierdzenia przychodzi, gdy przybyło spraw albo minęła doba', function () {
    Notification::fake();
    Carbon::setTestNow('2026-10-28 10:00');
    $moderator = User::factory()->create(['role' => UserRole::Moderator]);
    $quiet = User::factory()->create(['role' => UserRole::Moderator]);
    $fan = User::factory()->create();

    $this->actingAs($moderator);
    Livewire::test(ApprovalEmailToggle::class)->set('enabled', true);
    expect($moderator->fresh()->notify_approvals)->toBeTrue();

    $this->artisan('joker:approval-reminders')->assertSuccessful();
    Notification::assertNothingSent();

    GameComment::create(['game_id' => $this->cadetGame->id, 'user_id' => $fan->id, 'content' => 'Nowy', 'is_approved' => false]);
    Once::flush();
    $this->artisan('joker:approval-reminders');
    Notification::assertSentTo($moderator, ApprovalsWaiting::class, fn (ApprovalsWaiting $mail) => $mail->counts === ['Komentarze' => 1]);
    Notification::assertNotSentTo($quiet, ApprovalsWaiting::class);

    // Nic nowego: bez kolejnego e-maila. Po dobie przypomnienie.
    Once::flush();
    $this->artisan('joker:approval-reminders');
    Notification::assertSentToTimes($moderator, ApprovalsWaiting::class, 1);

    Carbon::setTestNow('2026-10-29 11:00');
    Once::flush();
    $this->artisan('joker:approval-reminders');
    Notification::assertSentToTimes($moderator, ApprovalsWaiting::class, 2);
});
