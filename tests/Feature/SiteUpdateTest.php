<?php

use App\Livewire\Admin\SiteUpdater;
use App\Models\User;
use App\Support\SiteUpdate;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/**
 * Buduje zip z podanymi wpisami (nazwa => treść) i zwraca jego ścieżkę.
 */
function siteUpdateZip(array $entries): string
{
    $path = SiteUpdate::dir().'/zrodlo-'.uniqid().'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();

    return $path;
}

/**
 * Wysyła plik do panelu w kawałkach, tak jak robi to przeglądarka.
 */
function siteUpdateSend($test, string $path, int $pieces = 3)
{
    $bytes = file_get_contents($path);
    $size = (int) ceil(strlen($bytes) / $pieces);
    $response = null;

    foreach (str_split($bytes, $size) as $index => $piece) {
        $response = $test->post(route('admin.site-update.chunk'), [
            'upload' => 'testupload1',
            'index' => $index,
            'total' => $pieces,
            'chunk' => UploadedFile::fake()->createWithContent('czesc.bin', $piece),
        ], ['Accept' => 'application/json']);
    }

    return $response;
}

beforeEach(function () {
    SiteUpdate::$dirOverride = sys_get_temp_dir().'/joker-aktualizacja-'.uniqid();
    File::ensureDirectoryExists(SiteUpdate::dir());
    $this->admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true, 'password' => bcrypt('tajne-haslo')]);
    $this->valid = ['artisan' => '<?php', 'public/index.php' => '<?php', 'vendor/autoload.php' => '<?php', 'bootstrap/wersja-paczki.txt' => '2099-01-01 10:00:00',
        'database/migrations/2099_01_01_000000_nowa_tabela.php' => '<?php', '.env' => 'APP_KEY=zle', 'storage/logs/x.log' => 'x', 'public/storage/zdjecie.jpg' => 'x'];
});

afterEach(function () {
    File::deleteDirectory(SiteUpdate::dir());
    SiteUpdate::$dirOverride = null;
});

test('only an administrator can open the update screen and send a package', function () {
    $boss = User::factory()->create(['role' => UserRole::Boss, 'is_active' => true]);

    $this->actingAs($boss)->get(route('admin.site-update.index'))->assertForbidden();
    siteUpdateSend($this->actingAs($boss), siteUpdateZip($this->valid))->assertForbidden();
    expect(SiteUpdate::package())->toBeNull();

    $this->actingAs($this->admin)->get(route('admin.site-update.index'))->assertOk()->assertSee('Wgraj paczkę');
});

test('an uploaded package is checked and waits for a password confirmed install', function () {
    siteUpdateSend($this->actingAs($this->admin), siteUpdateZip($this->valid))->assertOk()->assertJson(['done' => true]);

    $package = SiteUpdate::package();
    expect($package['wersja'])->toBe('2099-01-01 10:00:00')
        ->and($package['migracje'])->toBe(['2099_01_01_000000_nowa_tabela.php'])
        ->and($package['sha256'])->toBe(hash_file('sha256', SiteUpdate::zipPath()))
        ->and(SiteUpdate::flag())->toBeNull();

    Livewire::actingAs($this->admin)->test(SiteUpdater::class)
        ->assertSee('Paczka ma migracje bazy')
        ->set('password', 'zle-haslo')->call('install')->assertHasErrors('password');
    expect(SiteUpdate::flag())->toBeNull();

    Livewire::actingAs($this->admin)->test(SiteUpdater::class)->set('password', 'tajne-haslo')->call('install')->assertHasNoErrors()->assertSee('czeka na cron');

    expect(SiteUpdate::flag())->toMatchArray(['typ' => 'instaluj', 'wersja' => '2099-01-01 10:00:00', 'sha256' => $package['sha256']]);
});

test('a file that is not a site package is rejected and removed', function () {
    siteUpdateSend($this->actingAs($this->admin), siteUpdateZip(['readme.txt' => 'x']))->assertStatus(422)->assertJsonFragment(['message' => 'To nie jest paczka strony: brakuje pliku artisan. Zbuduj ją skryptem paczka-na-serwer.bat.']);
    expect(is_file(SiteUpdate::zipPath()))->toBeFalse();

    $nested = collect($this->valid)->mapWithKeys(fn ($content, $name) => ['joker/'.$name => $content])->all();
    $response = siteUpdateSend($this->actingAs($this->admin), siteUpdateZip($nested))->assertStatus(422);
    expect($response->json('message'))->toContain('dodatkowy folder nadrzędny (joker)');
});

test('a package older than the installed one needs an explicit consent', function () {
    siteUpdateSend($this->actingAs($this->admin), siteUpdateZip(['bootstrap/wersja-paczki.txt' => '2000-01-01 10:00:00'] + $this->valid))->assertOk();
    File::put(base_path('bootstrap/wersja-paczki.txt'), '2026-11-12 09:00:00');
    SiteUpdate::inspect($this->admin);

    try {
        Livewire::actingAs($this->admin)->test(SiteUpdater::class)->assertSee('starsza niż zainstalowana')->set('password', 'tajne-haslo')->call('install')->assertHasErrors('allowOlder');
        expect(SiteUpdate::flag())->toBeNull();

        Livewire::actingAs($this->admin)->test(SiteUpdater::class)->set('password', 'tajne-haslo')->set('allowOlder', true)->call('install')->assertHasNoErrors();
        expect(SiteUpdate::flag())->not->toBeNull();
    } finally {
        File::delete(base_path('bootstrap/wersja-paczki.txt'));
    }
});

test('the unpack script refuses a changed package and never extracts settings, photos or logs', function () {
    siteUpdateSend($this->actingAs($this->admin), siteUpdateZip($this->valid))->assertOk();
    Livewire::actingAs($this->admin)->test(SiteUpdater::class)->set('password', 'tajne-haslo')->call('install');
    $script = base_path('narzedzia/rozpakuj-paczke.php');

    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($script).' '.escapeshellarg(SiteUpdate::dir()), $output, $code);
    $new = SiteUpdate::dir().'/nowa';

    expect($code)->toBe(0)
        ->and(is_file($new.'/artisan'))->toBeTrue()
        ->and(is_file($new.'/database/migrations/2099_01_01_000000_nowa_tabela.php'))->toBeTrue()
        ->and(file_exists($new.'/.env'))->toBeFalse()
        ->and(file_exists($new.'/storage'))->toBeFalse()
        ->and(file_exists($new.'/public/storage'))->toBeFalse();

    File::deleteDirectory($new);
    File::append(SiteUpdate::zipPath(), 'dopisane');
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($script).' '.escapeshellarg(SiteUpdate::dir()), $changed, $changedCode);

    expect($changedCode)->toBe(1)->and(implode("\n", $changed))->toContain('inna suma kontrolna')->and(is_dir($new))->toBeFalse();
});

test('no new package is accepted while an install is waiting or running', function () {
    siteUpdateSend($this->actingAs($this->admin), siteUpdateZip($this->valid))->assertOk();
    Livewire::actingAs($this->admin)->test(SiteUpdater::class)->set('password', 'tajne-haslo')->call('install');

    siteUpdateSend($this->actingAs($this->admin), siteUpdateZip($this->valid))->assertStatus(422);

    File::makeDirectory(SiteUpdate::dir().'/blokada');
    Livewire::actingAs($this->admin)->test(SiteUpdater::class)->assertSee('Trwa instalacja paczki')->call('cancel');
    expect(SiteUpdate::flag())->not->toBeNull();
});

test('the package is checked and unpacked without the php zip extension', function () {
    require_once base_path('narzedzia/rozpakuj-paczke.php');
    $zip = siteUpdateZip($this->valid + ['public/obrazek.bin' => random_bytes(5000), 'public/duzy.txt' => str_repeat('Joker Piła ', 2000)]);

    expect(joker_sprawdz_paczke($zip))->toMatchArray(['wersja' => '2099-01-01 10:00:00', 'migracje' => ['2099_01_01_000000_nowa_tabela.php']]);

    $reczny = SiteUpdate::dir().'/reczny';
    $rozszerzenie = SiteUpdate::dir().'/rozszerzenie';
    joker_rozpakuj_paczke($zip, $reczny, true);
    joker_rozpakuj_paczke($zip, $rozszerzenie);

    foreach (['artisan', 'public/index.php', 'public/obrazek.bin', 'public/duzy.txt', 'database/migrations/2099_01_01_000000_nowa_tabela.php'] as $file) {
        expect(file_get_contents($reczny.'/'.$file))->toBe(file_get_contents($rozszerzenie.'/'.$file));
    }

    expect(file_exists($reczny.'/.env'))->toBeFalse()
        ->and(file_exists($reczny.'/storage'))->toBeFalse()
        ->and(file_exists($reczny.'/public/storage'))->toBeFalse();
});

test('entries starting with ./ are accepted but settings are still never unpacked', function () {
    require_once base_path('narzedzia/rozpakuj-paczke.php');
    $entries = [];

    foreach ($this->valid as $name => $content) {
        $entries['./'.$name] = $content;
    }

    $zip = siteUpdateZip($entries);
    expect(joker_sprawdz_paczke($zip)['wersja'])->toBe('2099-01-01 10:00:00');

    foreach ([true, false] as $bezRozszerzenia) {
        $cel = SiteUpdate::dir().'/cel-'.(int) $bezRozszerzenia;
        joker_rozpakuj_paczke($zip, $cel, $bezRozszerzenia);

        expect(is_file($cel.'/artisan'))->toBeTrue()
            ->and(file_exists($cel.'/.env'))->toBeFalse()
            ->and(file_exists($cel.'/storage'))->toBeFalse();
    }
});

test('a damaged package is rejected by the reader without the zip extension', function () {
    require_once base_path('narzedzia/rozpakuj-paczke.php');
    $path = SiteUpdate::dir().'/zepsuty.zip';
    file_put_contents($path, substr(file_get_contents(siteUpdateZip($this->valid)), 0, 200));

    expect(fn () => joker_sprawdz_paczke($path))->toThrow(RuntimeException::class);
});
