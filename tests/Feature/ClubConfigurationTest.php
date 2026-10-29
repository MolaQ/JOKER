<?php

use App\Models\Competition;
use App\Models\Game;
use App\Models\User;
use App\Support\ClubAccounts;
use App\Support\SiteSettings;

test('nazwy naszych drużyn w rozgrywkach pochodzą z ustawień', function () {
    $site = app(SiteSettings::class);

    expect(Competition::defaultTeamLabel('Kadeci', 'kadeci', 'UKS SMS Joker Piła')['display_name'])->toBe('LOMS Joker Piła')
        ->and(Competition::defaultTeamLabel('Kadeci II', 'kadeci-ii', 'KS ASBL Piła')['display_name'])->toBe('KS ASBL Piła');

    $site->set(['competition_club_name' => 'MKS Orzeł Kalisz', 'competition_other_names' => "Orzełek = UKS Orzełek Kalisz\nbez znaku równości"]);

    expect(Competition::defaultTeamLabel('Kadeci', 'kadeci', 'MKS Orzeł Kalisz')['display_name'])->toBe('MKS Orzeł Kalisz')
        ->and(Competition::defaultTeamLabel('Młodzicy', 'mlodzicy', 'UKS ORZEŁEK Kalisz')['display_name'])->toBe('UKS Orzełek Kalisz');
});

test('derby rozpoznaje drużyny z ustawień i można je wyłączyć', function () {
    $site = app(SiteSettings::class);
    $game = Game::factory()->make(['opponent' => 'KS ASBL Piła']);

    expect($game->isClubDerby())->toBeTrue();

    $site->set(['competition_name_keys' => 'orzeł']);
    expect($game->isClubDerby())->toBeFalse()
        ->and(Game::factory()->make(['opponent' => 'UKS Orzeł II'])->isClubDerby())->toBeTrue();

    $site->set(['module_derby' => false]);
    expect(Game::factory()->make(['opponent' => 'UKS Orzeł II'])->isClubDerby())->toBeFalse();
});

test('cykliczny turniej i wychowankowie wyłączają się w ustawieniach razem z linkami w menu', function () {
    $site = app(SiteSettings::class);
    $this->get('/memorial')->assertOk()->assertSee('Memoriał im. Włodzimierza Winklera');
    $this->get('/alumni')->assertOk();

    $site->set(['featured_tournament_name' => 'Puchar Prezesa', 'featured_tournament_eyebrow' => 'Turniej w Kaliszu']);
    $this->get('/memorial')->assertOk()->assertSee('Puchar Prezesa')->assertSee('Turniej w Kaliszu');

    $site->set(['featured_tournament_name' => '', 'module_alumni' => false, 'nav_items' => [['label' => 'Memoriał', 'url' => '/memorial'], ['label' => 'Wychowankowie', 'url' => '/wychowankowie'], ['label' => 'Drużyny', 'url' => '/teams']]]);
    $this->get('/memorial')->assertNotFound();
    $this->get('/alumni')->assertNotFound();
    expect(collect($site->links('nav_items'))->pluck('url')->all())->toBe(['/teams']);
});

test('przycisk na planie treningów i plakietka klubu są z ustawień', function () {
    $site = app(SiteSettings::class);
    $site->set(['trainings_extra_label' => 'Dojazd z Kalisza', 'trainings_extra_url' => '/page/dojazd']);
    $this->get(route('trainings.index'))->assertSee('Dojazd z Kalisza')->assertDontSee('Jesteś spoza Piły?');

    $site->set(['trainings_extra_label' => '']);
    $this->get(route('trainings.index'))->assertDontSee('Dojazd z Kalisza');
});

test('adres zastępczy konta i plik eksportu danych biorą domenę i nazwę klubu ze strony', function () {
    config(['app.url' => 'https://www.orzel-kalisz.pl']);
    $email = ClubAccounts::placeholderEmail('Jan Kowalski');

    expect($email)->toMatch('/^jan\.kowalski\.[a-z0-9]{4}@brak-emaila\.orzel-kalisz\.pl$/')
        ->and(ClubAccounts::isPlaceholderEmail($email))->toBeTrue()
        ->and(ClubAccounts::isPlaceholderEmail('jan@brak-emaila.jokerpila.pl'))->toBeTrue();

    app(SiteSettings::class)->set(['club_name' => 'Orzeł Kalisz']);
    $this->actingAs(User::factory()->create())
        ->get(route('account.export'))
        ->assertHeader('Content-Disposition', 'attachment; filename="moje-dane-orzel-kalisz.json"');
});
