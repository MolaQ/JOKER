<?php

use App\Livewire\Admin\SetupWizard;
use App\Models\Team;
use App\Models\User;
use App\Support\ClubNameForms;
use App\Support\LogoColors;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function wizardLogo(): UploadedFile
{
    $image = imagecreatetruecolor(200, 200);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagefilledellipse($image, 100, 100, 190, 190, imagecolorallocate($image, 200, 16, 46));
    imagefilledrectangle($image, 70, 70, 130, 130, imagecolorallocate($image, 250, 200, 20));
    ob_start();
    imagepng($image);

    return UploadedFile::fake()->createWithContent('herb.png', (string) ob_get_clean());
}

it('opens the setup wizard from the system page for administrators only', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.settings.index'))->assertSee(route('admin.settings.setup'), false);
    $this->actingAs($admin)->get(route('admin.settings.setup'))->assertOk()->assertSee('Jak nazywa się klub?');

    $editor = User::factory()->create(['role' => UserRole::Editor]);
    $this->actingAs($editor)->get(route('admin.settings.setup'))->assertForbidden();
});

it('suggests the short name, its forms and the Google title from the club name', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(SetupWizard::class)
        ->set('values.club_name', 'Orlik Gniezno')
        ->assertSet('values.club_short_name', 'Orlik')
        ->assertSet('values.club_name_genitive', 'Orlika')
        ->assertSet('values.club_name_locative', 'Orliku')
        ->assertSet('values.site_title', 'Orlik Gniezno - Klub Siatkówki')
        ->assertSet('values.footer_button_label', 'Dołącz do Orlika')
        ->set('values.club_name_genitive', 'Orlików')
        ->set('values.club_short_name', 'Orzeł')
        ->assertSet('values.club_name_genitive', 'Orlików');
});

it('picks the site colours from the uploaded crest', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(SetupWizard::class)
        ->set('logo', wizardLogo())
        ->assertSet('colorsFromLogo', true)
        ->assertSet('values.color_accent', '#c8102e')
        ->assertSet('values.color_topbar_bg', '#fac814')
        ->assertSet('values.color_topbar_text', '#12141a');
});

it('does not move to the next step until the club name is filled in', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(SetupWizard::class)
        ->set('values.club_name', '')
        ->call('next')
        ->assertHasErrors('values.club_name')
        ->assertSet('step', 0);
});

it('saves the club settings, the crest and the new teams in one go', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    Team::factory()->create(['name' => 'Orlik Seniorzy', 'category' => 'senior']);

    Livewire::test(SetupWizard::class)
        ->set('values.club_name', 'Orlik Gniezno')
        ->set('logo', wizardLogo())
        ->set('values.contact_email', 'klub@orlik.example')
        ->set('teams', [
            ['name' => 'Orlik Seniorzy', 'category' => 'senior'],
            ['name' => 'Orlik Kadeci', 'category' => 'junior_mlodszy'],
            ['name' => '', 'category' => 'mlodzik'],
        ])
        ->set('step', 3)
        ->call('finish')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.settings.setup', ['gotowe' => 1]));

    $settings = app(SiteSettings::class);
    $settings->flush();

    expect($settings->get('club_name'))->toBe('Orlik Gniezno')
        ->and($settings->get('club_name_genitive'))->toBe('Orlika')
        ->and($settings->get('contact_email'))->toBe('klub@orlik.example')
        ->and($settings->get('color_accent'))->toBe('#c8102e')
        ->and($settings->get('color_like'))->toBe('#c8102e')
        ->and($settings->get('color_nav_button_bg'))->toBe('#fac814')
        ->and($settings->get('logo'))->toStartWith('settings/')
        ->and(Team::query()->where('name', 'Orlik Seniorzy')->count())->toBe(1)
        ->and(Team::query()->where('name', 'Orlik Kadeci')->value('category'))->toBe('junior_mlodszy');

    Storage::disk('public')->assertExists($settings->get('logo'));
});

it('suggests Polish forms of common club names', function (string $name, string $genitive, string $locative) {
    expect(ClubNameForms::suggest($name))->toBe(['genitive' => $genitive, 'locative' => $locative]);
})->with([
    ['Joker', 'Jokera', 'Jokerze'],
    ['Chemik', 'Chemika', 'Chemiku'],
    ['Sparta', 'Sparty', 'Sparcie'],
    ['Wisła', 'Wisły', 'Wiśle'],
    ['Gwardia', 'Gwardii', 'Gwardii'],
    ['Lotto', 'Lotto', 'Lotto'],
]);

it('finds no colours in a black and white crest', function () {
    $image = imagecreatetruecolor(50, 50);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagefilledrectangle($image, 10, 10, 40, 40, imagecolorallocate($image, 0, 0, 0));
    $path = tempnam(sys_get_temp_dir(), 'herb');
    imagepng($image, $path);

    expect(LogoColors::suggest($path))->toBeNull();
});
