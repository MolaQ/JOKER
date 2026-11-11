<?php

use App\Models\RivalClub;
use App\Models\RivalTeam;
use App\Models\User;
use App\Support\ClubLogos;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the logos page lists club logos, downloads them and stays out of search engines', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logos/sparta.png', UploadedFile::fake()->image('s.png')->getContent());
    $club = RivalClub::create(['name' => 'Sparta Złotów', 'logo_path' => 'logos/sparta.png']);
    RivalTeam::create(['full_name' => 'Sparta Złotów II', 'rival_club_id' => $club->id]);

    $this->get('/logotypy')->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => UserRole::Fan]))->get('/logotypy')->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]));

    $this->get('/logotypy')->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('noindex, nofollow', false)
        ->assertSee('Sparta Złotów')
        ->assertSee('Nasz klub');

    $this->get(route('logos.download', 'klub-'.$club->id))->assertOk()->assertDownload('sparta-zlotow.png');
    $this->get(route('logos.zip'))->assertOk()->assertDownload();
    $this->get('/robots.txt')->assertSee('Disallow: /logotypy');
    $this->get('/sitemap.xml')->assertDontSee('logotypy');

    app(SiteSettings::class)->set(['logos_page' => false]);
    $this->get('/logotypy')->assertNotFound();
});

test('logos sort by downloads, then meetings, and by name both ways', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]));
    Storage::fake('public');
    foreach (['Adria', 'Zawisza', 'Borowiak'] as $name) {
        Storage::disk('public')->put('logos/'.$name.'.png', UploadedFile::fake()->image('x.png')->getContent());
        $clubs[$name] = RivalClub::create(['name' => $name, 'logo_path' => 'logos/'.$name.'.png']);
    }

    $this->get(route('logos.download', 'klub-'.$clubs['Zawisza']->id));
    $this->get(route('logos.download', 'klub-'.$clubs['Zawisza']->id));
    $this->get(route('logos.download', 'klub-'.$clubs['Borowiak']->id));

    $logos = ClubLogos::ranked()->keyBy('name');
    expect($logos['Zawisza']['downloads'])->toBe(2)
        ->and($logos['Zawisza']['popular_rank'])->toBeLessThan($logos['Borowiak']['popular_rank'])
        ->and($logos['Borowiak']['popular_rank'])->toBeLessThan($logos['Adria']['popular_rank'])
        ->and($logos['Adria']['name_rank'])->toBeLessThan($logos['Borowiak']['name_rank']);

    $this->get('/logotypy')->assertSee('Nazwa klubu Z–A');
});

test('a club logo is found by the names of its teams', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logos/turek.png', UploadedFile::fake()->image('t.png')->getContent());
    Storage::disk('public')->put('logos/wlasne.png', UploadedFile::fake()->image('w.png', 20, 20)->getContent());
    $club = RivalClub::create(['name' => 'TKS Turek', 'logo_path' => 'logos/turek.png']);
    RivalTeam::create(['full_name' => 'Piątka Turek', 'short_name' => 'Piątka', 'aliases' => ['MKS Piątka'], 'rival_club_id' => $club->id]);
    RivalTeam::create(['full_name' => 'TKS Turek II', 'rival_club_id' => $club->id, 'logo_path' => 'logos/wlasne.png']);

    $logos = ClubLogos::all();
    expect($logos->firstWhere('key', 'klub-'.$club->id)['teams'])->toContain('Piątka Turek', 'Piątka', 'MKS Piątka')->not->toContain('TKS Turek II')
        ->and($logos->firstWhere('name', 'TKS Turek II')['teams'])->toContain('TKS Turek');

    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]))
        ->get('/logotypy')->assertOk()->assertSee('MKS Pi', false)->assertSee('Szukaj klubu albo drużyny…');
});
