<?php

use App\Livewire\Admin\StaffOrder;
use App\Models\MiniGroup;
use App\Models\Team;
use App\Models\User;
use App\Support\Sitemap;
use App\Support\SiteSettings;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior', 'display_order' => 1]);
    $this->coach = User::factory()->create(['name' => 'Marek Trener', 'role' => UserRole::Trainer]);
    $this->seniors->update(['trainer_id' => $this->coach->id]);

    $this->physio = User::factory()->create(['name' => 'Ewa Fizjo', 'role' => UserRole::Staff]);
    $this->seniors->assistantTrainers()->attach($this->physio->id, ['role' => 'Fizjoterapeuta']);

    $this->group = MiniGroup::create(['name' => 'Testowe czwórki', 'players_on_court' => 4]);
    $this->mini = User::factory()->create(['name' => 'Ola Mini', 'role' => UserRole::MiniTrainer]);
    $this->mini->miniGroups()->attach($this->group);
});

test('zakładka Sztab pokazuje trenerów, trenerów mini i resztę sztabu z filtrami', function () {
    $this->get(route('trainers.index'))->assertOk()
        ->assertSeeInOrder(['Marek Trener', 'Ola Mini', 'Ewa Fizjo'])
        ->assertSee('Fizjoterapeuta')
        ->assertSee('Trener minisiatkówki')
        ->assertSee('data-groups="support"', false)
        ->assertSee('data-teams="mini-'.$this->group->id.'"', false)
        ->assertSee('Wszystkie drużyny');

    $this->get('/sztab')->assertRedirect('/staff');
    $this->get(route('trainers.show', $this->mini))->assertOk()->assertSee('Testowe czwórki');
});

test('w panelu ustawia się kolejność, funkcję, ukrycie i grupy mini', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.staff.index'))->assertOk();

    Livewire::actingAs($admin)->test(StaffOrder::class)
        ->call('move', $this->mini->id, -1)
        ->call('move', $this->mini->id, -1)
        ->set('functions.'.$this->coach->id, 'Trener koordynator')
        ->call('toggleHidden', $this->physio->id)
        ->set('miniGroupIds.'.$this->coach->id, [(string) $this->group->id]);

    $this->get(route('trainers.index'))
        ->assertSeeInOrder(['Ola Mini', 'Marek Trener'])
        ->assertSee('Trener koordynator')
        ->assertDontSee('Ewa Fizjo');
    expect($this->coach->miniGroups()->pluck('mini_groups.id')->all())->toBe([$this->group->id]);
});

test('kolejności sztabu nie zmieni osoba bez prawa do drużyn', function () {
    $this->actingAs(User::factory()->fan()->create())->get(route('admin.staff.index'))->assertForbidden();
});

test('mapa strony zawiera zakładkę Sztab i profile, bez ukrytych osób', function () {
    $this->physio->update(['staff_hidden' => true]);
    $xml = collect((new Sitemap)->urls())->pluck('loc');

    expect($xml)->toContain(route('trainers.index'))
        ->toContain(route('trainers.show', $this->mini))
        ->not->toContain(route('trainers.show', $this->physio));
});

test('grupa minisiatkówki pokazuje swoich trenerów', function () {
    $this->group->update(['is_active' => true]);
    $this->get(route('mini.show', $this->group))->assertOk()->assertSee('Ola Mini')->assertSee(route('trainers.show', $this->mini));
})->skip(fn () => ! app(SiteSettings::class)['module_mini'], 'moduł mini wyłączony');

test('profil rodzica pokazuje sylwetkę i media społecznościowe', function () {
    $parent = User::factory()->parent()->create(['name' => 'Marta Rodzic', 'additional_info' => '<p>Mama libero, kibicuje od lat.</p>', 'social_links' => ['instagram' => 'https://www.instagram.com/marta']]);

    $this->actingAs(User::factory()->fan()->create())->get(route('people.show', $parent))->assertOk()
        ->assertSee('Sylwetka')
        ->assertSee('Mama libero, kibicuje od lat.')
        ->assertSee('https://www.instagram.com/marta');
});

test('Sztab i profile trenerów mają dane strukturalne dla Google', function () {
    $this->coach->update(['additional_info' => '<p>Trener z pasją.</p>', 'social_links' => ['instagram' => 'https://www.instagram.com/marek']]);

    $this->get(route('trainers.index'))->assertOk()
        ->assertSee('"@type":"ItemList"', false)
        ->assertSee('"jobTitle":"Fizjoterapeuta"', false);

    $this->get(route('trainers.show', $this->coach))->assertOk()
        ->assertSee('"@type":"Person"', false)
        ->assertSee('"sameAs":["https://www.instagram.com/marek"]', false)
        ->assertSee('"description":"Trener z pasją."', false)
        ->assertSee('Seniorzy', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('<meta property="og:type" content="profile">', false);
});
