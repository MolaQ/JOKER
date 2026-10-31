<?php

use App\Models\RivalClub;
use App\Models\RivalTeam;
use App\Support\ClubLogos;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the logos page lists club logos, downloads them and stays out of search engines', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logos/sparta.png', UploadedFile::fake()->image('s.png')->getContent());
    $club = RivalClub::create(['name' => 'Sparta Złotów', 'logo_path' => 'logos/sparta.png']);
    RivalTeam::create(['full_name' => 'Sparta Złotów II', 'rival_club_id' => $club->id]);

    $this->get('/logotypy')->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('noindex, nofollow', false)
        ->assertSee('Sparta Złotów')
        ->assertSee('Nasz klub');

    $this->get(route('logos.download', 'klub-'.$club->id))->assertOk()->assertDownload('sparta-zlotow.png');
    $this->get(route('logos.zip'))->assertOk()->assertDownload();
    $this->get('/robots.txt')->assertSee('Disallow: /logotypy');
    $this->get('/sitemap.xml')->assertDontSee('logotypy');

    app(SiteSettings::class)->set(['logos_login' => true]);
    $this->get('/logotypy')->assertRedirect(route('login'));

    app(SiteSettings::class)->set(['logos_page' => false]);
    $this->get('/logotypy')->assertNotFound();
});

test('logos sort by downloads, then meetings, and by name both ways', function () {
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
