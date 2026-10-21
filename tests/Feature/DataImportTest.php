<?php

use App\Livewire\Admin\DataImporter;
use App\Models\Page;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\DataImport;
use App\UserRole;
use Database\Seeders\StartSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('admin imports teams, staff and players from csv files in excel format', function () {
    $this->actingAs(User::factory()->admin()->create());

    $teams = "nazwa;kategoria;rocznik;opis\nKadeci;juniorzy młodsi;2010;Pierwsza drużyna kadetów.\nMłodzicy;Młodzicy;2012;\nZła;dorośli;;\n";
    Livewire::test(DataImporter::class)
        ->set('file', UploadedFile::fake()->createWithContent('druzyny.csv', $teams))
        ->assertSee('nieznana kategoria')
        ->call('import');

    expect(Team::pluck('category', 'name')->all())->toBe(['Kadeci' => 'junior_mlodszy', 'Młodzicy' => 'mlodzik']);

    // Excel w Windows zapisuje CSV w Windows-1250.
    $staff = "imie_nazwisko;email;rola;druzyny;funkcja\nPaweł Lewandowski;pawel@example.com;trener;Kadeci, Młodzicy;trener główny\nEwa Jankowska;;sztab;Kadeci;kierowniczka drużyny\n";
    $staff = iconv('UTF-8', 'WINDOWS-1250', $staff);
    Livewire::test(DataImporter::class)->set('type', 'staff')->set('file', UploadedFile::fake()->createWithContent('sztab.csv', $staff))->call('import');

    $coach = User::firstWhere('email', 'pawel@example.com');
    $manager = User::firstWhere('name', 'Ewa Jankowska');
    expect($coach->role)->toBe(UserRole::Trainer)
        ->and(Team::firstWhere('name', 'Młodzicy')->trainer_id)->toBe($coach->id)
        ->and($manager->role)->toBe(UserRole::Staff)
        ->and(Team::firstWhere('name', 'Kadeci')->assistantTrainers->first()->pivot->role)->toBe('kierowniczka drużyny');

    $players = "imie,nazwisko,druzyna,numer,pozycja,data_urodzenia,wzrost\nAdam,Nowak,kadeci,7,Przyjmujący,14.05.2010,188\nOlek,Wiśniewski,Kadeci,12,libero,2010-02-30,\n";
    Livewire::test(DataImporter::class)->set('type', 'players')->set('text', $players)->call('preview')
        ->assertSee('data urodzenia w formacie')
        ->call('import');

    $adam = Player::firstWhere('last_name', 'Nowak');
    expect(Player::count())->toBe(1)
        ->and($adam->position->value)->toBe('outside_hitter')
        ->and($adam->birth_date->toDateString())->toBe('2010-05-14')
        ->and($adam->height)->toBe(188);

    // Ponowny import aktualizuje zamiast dublować.
    $rows = DataImport::parse('players', "imie;nazwisko;druzyna;numer;pozycja\nAdam;Nowak;Kadeci;9;przyjmujący\n");
    expect($rows[0]['action'])->toBe('aktualizacja')
        ->and(DataImport::import('players', $rows))->toBe(1)
        ->and($adam->fresh()->jersey_number)->toBe(9)
        ->and(Player::count())->toBe(1);
});

test('import templates download with the column headers', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.import.template', 'players'))->assertOk()->assertSee('imie;nazwisko;druzyna;numer;pozycja', false);
    $this->get(route('admin.import.index'))->assertOk()->assertSee('Import danych');
});

test('a clean start has seasons, competition levels and the club pages but no demo teams', function () {
    $this->seed(StartSeeder::class);

    expect(Season::where('is_current', true)->exists())->toBeTrue()
        ->and(Team::count())->toBe(0)
        ->and(Page::pluck('slug')->all())->toContain('o-klubie', 'system-szkolenia', 'osiagniecia', 'wspolpraca', 'nabor');

    $this->get('/')->assertOk()->assertSee('smsjokerpila@gmail.com')->assertSee('System szkolenia');
    $this->get(route('pages.show', 'osiagniecia'))->assertOk()->assertSee('2007 Drawsko Pomorskie');
});
