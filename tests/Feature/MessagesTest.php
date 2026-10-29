<?php

use App\Console\Commands\SendMessageEmails;
use App\Livewire\Admin\ReportedMessages;
use App\Livewire\Messages;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Notifications\MessagesWaiting;
use App\Permission;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->anna = User::factory()->parent()->create(['name' => 'Anna Nowak']);
    $this->piotr = User::factory()->parent()->create(['name' => 'Piotr Kibic']);
});

test('użytkownik pisze do innej osoby, a ta widzi nieprzeczytaną wiadomość i dzwonek z liczbą', function () {
    $this->actingAs($this->anna);

    Livewire::test(Messages::class)
        ->call('startComposing')
        ->set('recipientSearch', 'Piotr')
        ->assertSee('Piotr Kibic')
        ->call('addRecipient', $this->piotr->id)
        ->set('newBody', 'Cześć, kiedy trening?')
        ->call('startConversation')
        ->assertHasNoErrors()
        ->assertSee('Cześć, kiedy trening?');

    expect($this->piotr->fresh()->unreadMessagesCount())->toBe(1)
        ->and($this->anna->fresh()->unreadMessagesCount())->toBe(0);

    $this->actingAs($this->piotr)->get(route('messages.index'))->assertOk()->assertSee('Wiadomości: 1 nieprzeczytane', false);

    $conversation = Conversation::firstOrFail();
    Livewire::test(Messages::class)->call('open', $conversation->id)->assertSee('Cześć, kiedy trening?')
        ->set('body', 'Jutro o 17')->call('send');

    expect($this->piotr->fresh()->unreadMessagesCount())->toBe(0)
        ->and($this->anna->fresh()->unreadMessagesCount())->toBe(1)
        ->and(Conversation::count())->toBe(1);

    // Druga wiadomość do tej samej osoby trafia do tej samej rozmowy.
    $this->actingAs($this->anna);
    Livewire::test(Messages::class)->call('startComposing')->call('addRecipient', $this->piotr->id)->set('newBody', 'Dzięki')->call('startConversation');
    expect(Conversation::count())->toBe(1);
});

test('osoba spoza rozmowy jej nie otworzy', function () {
    $conversation = Conversation::between($this->anna, $this->piotr);
    $conversation->messages()->create(['user_id' => $this->anna->id, 'body' => 'Tajne']);

    $this->actingAs(User::factory()->create());
    Livewire::test(Messages::class)->call('open', $conversation->id)->assertSet('conversationId', null)->assertDontSee('Tajne');
});

test('trener pisze do rodziców drużyny, a nowy rodzic dopisuje się do grupy przy następnej wiadomości', function () {
    $trainer = User::factory()->trainer()->create(['name' => 'Trener Kowalski']);
    $team = Team::factory()->create(['name' => 'Kadeci', 'trainer_id' => $trainer->id]);
    $player = Player::factory()->create(['team_id' => $team->id, 'is_active' => true]);
    $player->guardians()->attach($this->anna->id);

    $this->actingAs($trainer);
    Livewire::test(Messages::class)
        ->call('startComposing')
        ->assertSee('Kadeci – rodzice i sztab')
        ->set('teamGroup', $team->id.':rodzice')
        ->set('newBody', 'Jutro trening o 16')
        ->call('startConversation')
        ->assertHasNoErrors();

    $group = Conversation::where('team_id', $team->id)->firstOrFail();
    expect($group->participants->pluck('id')->sort()->values()->all())->toBe(collect([$trainer->id, $this->anna->id])->sort()->values()->all())
        ->and($this->anna->fresh()->unreadMessagesCount())->toBe(1);

    $newParent = User::factory()->parent()->create();
    Player::factory()->create(['team_id' => $team->id, 'is_active' => true])->guardians()->attach($newParent->id);

    Livewire::test(Messages::class)->call('open', $group->id)->set('body', 'Zmiana: 17')->call('send');

    expect($group->fresh()->participants->pluck('id'))->toContain($newParent->id)
        ->and($newParent->fresh()->unreadMessagesCount())->toBe(1);

    // Rodzic nie może pisać do całej drużyny.
    $this->actingAs($this->anna);
    Livewire::test(Messages::class)->call('startComposing')->set('teamGroup', $team->id.':wszyscy')->set('newBody', 'Hej')->call('startConversation')->assertHasErrors('teamGroup');
});

test('po odebraniu uprawnienia „Pisanie wiadomości” nie da się wysłać wiadomości', function () {
    $this->anna->permissionOverrides()->create(['permission' => Permission::WriteMessages->value, 'allowed' => false]);
    $this->actingAs($this->anna->fresh());

    Livewire::test(Messages::class)
        ->call('startComposing')
        ->call('addRecipient', $this->piotr->id)
        ->set('newBody', 'Spam')
        ->call('startConversation')
        ->assertDispatched('toast', message: 'Klub wyłączył Ci możliwość pisania wiadomości.', type: 'error');

    expect(Message::count())->toBe(0);
});

test('zbyt wiele wiadomości w minutę jest blokowane', function () {
    $this->actingAs($this->anna);
    $conversation = Conversation::between($this->anna, $this->piotr);
    RateLimiter::clear('messages:'.$this->anna->id);
    $component = Livewire::test(Messages::class)->call('open', $conversation->id);

    foreach (range(1, 21) as $i) {
        $component->set('body', 'Wiadomość '.$i)->call('send');
    }

    expect(Message::count())->toBe(20);
});

test('zgłoszona wiadomość trafia do moderacji i można ją ukryć', function () {
    $conversation = Conversation::between($this->anna, $this->piotr);
    $message = $conversation->messages()->create(['user_id' => $this->piotr->id, 'body' => 'Brzydka wiadomość']);

    $this->actingAs($this->anna);
    Livewire::test(Messages::class)->call('open', $conversation->id)->call('report', $message->id);
    expect($message->fresh()->reported_by)->toBe($this->anna->id);

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('admin.reported-messages.index'))->assertOk()->assertSee('Brzydka wiadomość');
    Livewire::test(ReportedMessages::class)->call('hide', $message->id);

    expect($message->fresh()->isHidden())->toBeTrue();
    $this->actingAs($this->anna);
    Livewire::test(Messages::class)->call('open', $conversation->id)->assertDontSee('Brzydka wiadomość')->assertSee('Wiadomość ukryta przez moderatora.');
});

test('e-mail zbiorczy idzie po kwadransie, raz na nowe wiadomości i nie do osób, które go wyłączyły', function () {
    Notification::fake();
    $conversation = Conversation::between($this->anna, $this->piotr);
    $this->travelTo(now()->subMinutes(20));
    $conversation->messages()->create(['user_id' => $this->anna->id, 'body' => 'Pierwsza']);
    $conversation->messages()->create(['user_id' => $this->anna->id, 'body' => 'Druga']);
    $this->travelBack();
    $conversation->messages()->create(['user_id' => $this->piotr->id, 'body' => 'Świeża odpowiedź']);

    $this->artisan(SendMessageEmails::class)->assertSuccessful();
    Notification::assertSentTo($this->piotr, MessagesWaiting::class, fn ($n) => $n->conversations[0]['count'] === 2 && $n->conversations[0]['excerpt'] === 'Druga');
    Notification::assertNotSentTo($this->anna, MessagesWaiting::class);

    $this->artisan(SendMessageEmails::class);
    Notification::assertSentToTimes($this->piotr, MessagesWaiting::class, 1);

    $this->anna->forceFill(['notify_messages' => false])->save();
    $this->travel(20)->minutes();
    $this->artisan(SendMessageEmails::class);
    Notification::assertNotSentTo($this->anna, MessagesWaiting::class);
});

test('do rozmowy grupowej można dodać kolejną osobę, a rozmowa dwóch osób zaczyna nową grupę', function () {
    $ewa = User::factory()->parent()->create(['name' => 'Ewa Nowa']);
    $group = Conversation::create(['title' => 'Koordynatorzy', 'created_by' => $this->anna->id]);
    $group->participants()->attach([$this->anna->id, $this->piotr->id]);
    $group->messages()->create(['user_id' => $this->anna->id, 'body' => 'Witajcie']);

    $this->actingAs($this->anna);
    Livewire::test(Messages::class)
        ->call('open', $group->id)
        ->call('toggleAddingPeople')
        ->set('memberSearch', 'Ewa')
        ->assertSee('Ewa Nowa')
        ->call('addParticipant', $ewa->id);

    expect($group->fresh()->participants->pluck('id'))->toContain($ewa->id)
        ->and($ewa->fresh()->unreadMessagesCount())->toBe(0);

    $private = Conversation::between($this->anna, $this->piotr);
    Livewire::test(Messages::class)
        ->call('open', $private->id)
        ->call('toggleAddingPeople')
        ->assertSet('composing', true)
        ->assertSet('recipientIds', [$this->piotr->id])
        ->call('addParticipant', $ewa->id);

    expect($private->fresh()->participants)->toHaveCount(2);
});
