<?php

use App\Livewire\Admin\ContactMessages;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use App\Support\Approvals;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

$payload = ['name' => 'Jan Kibic', 'email' => 'jan@example.test', 'subject' => 'Bilety', 'message' => 'Gdzie kupić bilety na mecz?'];

test('wiadomość z formularza zapisuje się, idzie e-mailem do klubu i czeka w panelu', function () use ($payload) {
    Notification::fake();

    $this->post(route('contact.send'), $payload)->assertRedirect(route('contact.index'));
    $this->get(route('contact.index'))->assertSee('Dziękujemy, wiadomość dotarła do klubu.');

    expect(ContactMessage::sole())->subject->toBe('Bilety')->read_at->toBeNull();
    Notification::assertSentTo(new AnonymousNotifiable, ContactMessageReceived::class);

    $boss = User::factory()->boss()->create();
    expect(Approvals::sectionsFor($boss)->get('kontakt')['count'])->toBe(1);
    $this->actingAs($boss)->get(route('admin.contact-messages.index'))->assertOk()->assertSee('Gdzie kupić bilety na mecz?');

    Livewire::actingAs($boss)->test(ContactMessages::class)->call('markRead', ContactMessage::sole()->id);
    expect(ContactMessages::unreadCount())->toBe(0);
});

test('robot, który wypełni pole-pułapkę, niczego nie zapisuje', function () use ($payload) {
    $this->post(route('contact.send'), $payload + ['website' => 'spam.example'])->assertRedirect();
    expect(ContactMessage::count())->toBe(0);
});

test('skrzynki formularza nie widzi trener ani moderator', function () {
    foreach ([UserRole::Trainer, UserRole::Moderator] as $role) {
        $user = User::factory()->create(['role' => $role]);
        expect(Approvals::sectionsFor($user)->has('kontakt'))->toBeFalse();
        $this->actingAs($user)->get(route('admin.contact-messages.index'))->assertForbidden();
    }
});

test('strona Kontakt pokazuje administratora strony', function () {
    $admin = User::factory()->admin()->create(['name' => 'Ada Administratorka']);

    $this->get(route('contact.index'))->assertOk()->assertSee('Administrator strony')->assertSee('Ada Administratorka');
});

test('w ustawieniach można wskazać jedno konto administratora strony', function () {
    User::factory()->admin()->create(['name' => 'Ada Administratorka', 'email' => 'ada@example.test']);
    User::factory()->admin()->create(['name' => 'Konto Testowe']);
    app(SiteSettings::class)->set(['contact_site_admin' => 'ADA@example.test']);

    $response = $this->get(route('contact.index'));
    preg_match_all('/data-site-admin="([^"]*)"/', $response->getContent(), $found);
    expect($found[1])->toBe(['Ada Administratorka']);
});
