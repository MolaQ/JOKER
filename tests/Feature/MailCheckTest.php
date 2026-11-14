<?php

use App\Livewire\Admin\MailCheck;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('administrator wysyła testowy e-mail do siebie', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create(['email' => 'admin@example.test']);

    $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Wyślij testowy e-mail do mnie');
    Livewire::actingAs($admin)->test(MailCheck::class)->call('sendTest')->assertSet('ok', true);
});

test('błąd poczty pokazuje się zamiast strony błędu', function () {
    Mail::shouldReceive('raw')->andThrow(new RuntimeException('Connection refused'));
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(MailCheck::class)->call('sendTest')
        ->assertSet('ok', false)
        ->assertSee('Connection refused')->assertSee('MAIL_HOST');
});

test('wpis z logu pokazuje właściwy błąd serwera poczty i wskazówkę', function () {
    $log = storage_path('logs/laravel-test-mailcheck2.log');
    file_put_contents($log, '[2026-11-14 08:00:00] production.ERROR: Nie udało się wysłać e-maila: ContactReply {"to":"x@example.test","error":"Failed to authenticate on SMTP server with username \\"klub@joker.pl\\" using 2 possible authenticators. Authenticator LOGIN returned Expected response code \\"235\\" but got code \\"535\\"."} '."\n");
    touch($log, time() + 60);

    expect(MailCheck::recentErrors()[0]['message'])->toContain('ContactReply: Failed to authenticate')->toContain('MAIL_PASSWORD');

    unlink($log);
});

test('ostatnie błędy poczty są czytane z logu', function () {
    $log = storage_path('logs/laravel-test-mailcheck.log');
    file_put_contents($log, "[2026-11-04 10:00:00] production.ERROR: Nie udało się wysłać e-maila: AccountActivated {\"to\":\"x\"}\n[2026-11-04 10:01:00] production.ERROR: Coś innego\n");
    touch($log, time() + 60);

    expect(MailCheck::recentErrors())->toHaveCount(1)
        ->and(MailCheck::recentErrors()[0]['message'])->toBe('Nie udało się wysłać e-maila: AccountActivated');

    unlink($log);
});

test('trener nie widzi testu poczty', function () {
    $this->actingAs(User::factory()->trainer()->create())->get(route('admin.settings.index'))->assertForbidden();
});
