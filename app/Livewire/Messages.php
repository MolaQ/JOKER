<?php

namespace App\Livewire;

use App\AccountStatus;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use App\Permission;
use App\Services\PushNotifier;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Skrzynka „Wiadomości”: lista rozmów, otwarta rozmowa i pisanie nowej wiadomości (do osób albo do grupy drużyny).
 * Nowe wiadomości dochodzą same co kilkanaście sekund; odbiorcy dostają powiadomienie push i zbiorczy e-mail.
 */
class Messages extends Component
{
    use ShowsToasts;

    /** Najwięcej wiadomości na minutę od jednej osoby (ochrona przed spamem). */
    private const PER_MINUTE = 20;

    #[Url(as: 'conversation', except: null)]
    public ?int $conversationId = null;

    public string $body = '';

    public bool $composing = false;

    /** @var list<int> */
    #[Locked]
    public array $recipientIds = [];

    public string $recipientSearch = '';

    public string $groupTitle = '';

    public string $newBody = '';

    public string $teamGroup = '';

    public bool $addingPeople = false;

    public string $memberSearch = '';

    public function mount(): void
    {
        abort_unless(app(SiteSettings::class)->get('messages_enabled'), 404);

        if ($this->conversationId && ! $this->conversation()) {
            $this->conversationId = null;
        }

        // Koperta przy nazwisku: ?to=12 albo kilka osób naraz (?to=12,15, np. rodzice zawodnika).
        $ids = $this->recipients()->whereKey(array_map('intval', explode(',', (string) request('to'))))->pluck('id');

        if ($ids->isNotEmpty()) {
            $this->startComposing();
            $this->recipientIds = $ids->all();
        }
    }

    private function user(): User
    {
        return Auth::user();
    }

    private function conversation(): ?Conversation
    {
        return $this->conversationId ? Conversation::for($this->user())->with(['participants', 'team'])->find($this->conversationId) : null;
    }

    /**
     * Osoby, do których można pisać: aktywne konta (bez samego siebie).
     */
    private function recipients(): Builder
    {
        return User::query()->withAccountStatus(AccountStatus::Active)->whereKeyNot($this->user()->id)->messageableBy($this->user());
    }

    /**
     * Drużyny, do których grup może pisać ta osoba: trener i sztab swoich, koordynator swoich, zarząd wszystkich.
     *
     * @return Collection<int, Team>
     */
    #[Computed]
    public function groupTeams(): Collection
    {
        $user = $this->user();

        if (! $user->canMessage() || ! app(SiteSettings::class)->get('messages_team_groups')) {
            return collect();
        }

        if ($user->hasPermission(Permission::Teams) || $user->hasPermission(Permission::AllTeams)) {
            return Team::managedBy($user)->orderBy('display_order')->get(['id', 'name']);
        }

        return $user->allCoachedTeams()->merge($user->coordinatedTeams)->unique('id')->sortBy('display_order')->values();
    }

    public function open(int $conversationId): void
    {
        $this->conversationId = $conversationId;
        $this->composing = false;
        $this->body = '';
        $this->reset('addingPeople', 'memberSearch');

        if (! $this->conversation()) {
            $this->conversationId = null;
        }
    }

    public function back(): void
    {
        $this->conversationId = null;
        $this->composing = false;
        $this->reset('addingPeople', 'memberSearch');
    }

    public function startComposing(): void
    {
        $this->composing = true;
        $this->conversationId = null;
        $this->reset('recipientIds', 'recipientSearch', 'groupTitle', 'newBody', 'teamGroup');
    }

    public function addRecipient(int $userId): void
    {
        if ($this->recipients()->whereKey($userId)->exists() && ! in_array($userId, $this->recipientIds, true)) {
            $this->recipientIds[] = $userId;
        }

        $this->recipientSearch = '';
    }

    public function removeRecipient(int $userId): void
    {
        $this->recipientIds = array_values(array_diff($this->recipientIds, [$userId]));
    }

    /**
     * Dodawanie osób do rozmowy. Rozmowa dwóch osób zostaje prywatna: zamiast niej zaczyna się nowa grupa z tą samą osobą.
     */
    public function toggleAddingPeople(): void
    {
        $conversation = $this->conversation();

        if (! $conversation || $conversation->isTeamGroup() || ! $this->user()->canMessage()) {
            return;
        }

        if (! $conversation->isGroup()) {
            $others = $conversation->participants->where('id', '!=', $this->user()->id)->pluck('id');
            $this->startComposing();
            $this->recipientIds = $this->recipients()->whereKey($others)->pluck('id')->all();

            return;
        }

        $this->addingPeople = ! $this->addingPeople;
        $this->memberSearch = '';
    }

    public function addParticipant(int $userId): void
    {
        $conversation = $this->conversation();

        if (! $conversation || ! $conversation->isGroup() || $conversation->isTeamGroup() || ! $this->user()->canMessage()) {
            return;
        }

        $person = $this->recipients()->whereKey($userId)->first();

        if ($person && ! $conversation->participants->contains('id', $person->id)) {
            // Nowa osoba widzi wcześniejsze wiadomości, ale nie są dla niej „nieprzeczytane”.
            $conversation->participants()->attach($person->id, ['last_read_message_id' => (int) $conversation->messages()->max('id')]);
            $this->toast($person->name.' dołącza do rozmowy.');
        }

        $this->memberSearch = '';
    }

    public function send(): void
    {
        $conversation = $this->conversation();

        if (! $conversation || ! $this->mayWrite()) {
            return;
        }

        $this->validate(['body' => 'required|string|max:'.Message::MAX_LENGTH], [], ['body' => 'wiadomość']);
        $this->post($conversation, $this->body);
        $this->body = '';
    }

    public function startConversation(): void
    {
        if (! $this->mayWrite()) {
            return;
        }

        $this->validate([
            'newBody' => 'required|string|max:'.Message::MAX_LENGTH,
            'groupTitle' => 'nullable|string|max:120',
            'teamGroup' => 'nullable|string|max:40',
        ], [], ['newBody' => 'wiadomość', 'groupTitle' => 'nazwa rozmowy']);

        $user = $this->user();

        if ($this->teamGroup !== '') {
            [$teamId, $audience] = array_pad(explode(':', $this->teamGroup, 2), 2, '');
            $team = $this->groupTeams->firstWhere('id', (int) $teamId);

            if (! $team || ! array_key_exists($audience, Conversation::AUDIENCES)) {
                $this->addError('teamGroup', 'Nie możesz pisać do tej grupy.');

                return;
            }

            $conversation = Conversation::forTeam(Team::find($team->id), $audience, $user);
        } else {
            $ids = $this->recipients()->whereKey($this->recipientIds)->pluck('id');

            if ($ids->isEmpty()) {
                $this->addError('recipientIds', 'Wybierz, do kogo piszesz.');

                return;
            }

            if ($ids->count() === 1 && trim($this->groupTitle) === '') {
                $conversation = Conversation::between($user, User::find($ids->first()));
            } else {
                $conversation = Conversation::create(['title' => trim($this->groupTitle) ?: null, 'created_by' => $user->id]);
                $conversation->participants()->attach($ids->push($user->id)->unique()->all());
            }
        }

        $this->post($conversation, $this->newBody);
        $this->composing = false;
        $this->conversationId = $conversation->id;
        $this->reset('recipientIds', 'recipientSearch', 'groupTitle', 'newBody', 'teamGroup');
    }

    private function mayWrite(): bool
    {
        if (! $this->user()->canMessage()) {
            $this->toast('Klub wyłączył Ci możliwość pisania wiadomości.', 'error');

            return false;
        }

        if (RateLimiter::tooManyAttempts('messages:'.$this->user()->id, self::PER_MINUTE)) {
            $this->toast('Za dużo wiadomości naraz. Spróbuj za chwilę.', 'error');

            return false;
        }

        return true;
    }

    private function post(Conversation $conversation, string $body): void
    {
        $user = $this->user();
        RateLimiter::hit('messages:'.$user->id, 60);

        $conversation->syncTeamMembers();
        $message = $conversation->messages()->create(['user_id' => $user->id, 'body' => trim($body)]);
        $conversation->forceFill(['last_message_at' => $message->created_at])->save();
        $conversation->markReadBy($user);
        $conversation->load('participants', 'team');

        app(PushNotifier::class)->newMessage(
            $conversation->participants->where('id', '!=', $user->id)->pluck('id'),
            $conversation->isGroup() ? $user->name.' · '.$conversation->titleFor($user) : $user->name,
            Str::limit($message->body, 140),
            route('messages.index', ['conversation' => $conversation->id]),
            'rozmowa-'.$conversation->id,
        );
    }

    public function report(int $messageId): void
    {
        $message = Message::whereKey($messageId)->whereHas('conversation', fn (Builder $q) => $q->for($this->user()))->first();

        if (! $message || $message->user_id === $this->user()->id) {
            return;
        }

        if ($message->reported_at === null) {
            $message->forceFill(['reported_at' => now(), 'reported_by' => $this->user()->id])->save();
        }

        $this->toast('Dziękujemy. Wiadomość trafiła do moderatorów klubu.');
    }

    public function hide(int $messageId): void
    {
        if (! $this->user()->hasPermission(Permission::Comments)) {
            return;
        }

        Message::whereKey($messageId)->whereHas('conversation', fn (Builder $q) => $q->for($this->user()))
            ->update(['hidden_at' => now(), 'hidden_by' => $this->user()->id]);
    }

    public function toggleEmails(): void
    {
        $user = $this->user();
        $user->forceFill(['notify_messages' => ! $user->notify_messages])->save();
        $this->toast($user->notify_messages ? 'E-maile o nowych wiadomościach włączone.' : 'E-maile o nowych wiadomościach wyłączone.');
    }

    public function render(): View
    {
        $user = $this->user();
        $conversation = $this->conversation();

        if ($conversation) {
            $conversation->markReadBy($user);
        }

        $conversations = Conversation::for($user)
            ->with(['participants:id,name', 'team:id,name', 'latestMessage.author:id,name'])
            ->addSelect(['unread_count' => Message::query()
                ->selectRaw('count(*)')
                ->whereColumn('messages.conversation_id', 'conversations.id')
                ->whereNull('messages.hidden_at')
                ->where(fn (Builder $q) => $q->whereNull('messages.user_id')->orWhere('messages.user_id', '!=', $user->id))
                ->whereRaw('messages.id > (select last_read_message_id from conversation_user where conversation_user.conversation_id = conversations.id and conversation_user.user_id = ?)', [$user->id]),
            ])
            ->orderByDesc(DB::raw('coalesce(last_message_at, conversations.created_at)'))
            ->limit(100)
            ->get();

        $search = trim($this->recipientSearch);
        $memberSearch = trim($this->memberSearch);

        return view('livewire.messages', [
            'conversations' => $conversations,
            'conversation' => $conversation,
            'thread' => $conversation ? $conversation->messages()->with('author:id,name,avatar_path')->latest('id')->limit(200)->get()->reverse()->values() : collect(),
            'canWrite' => $user->canMessage(),
            'canModerate' => $user->hasPermission(Permission::Comments),
            'chosen' => $this->composing ? User::whereKey($this->recipientIds)->get(['id', 'name']) : collect(),
            'matches' => $this->composing && mb_strlen($search) >= 2
                ? $this->recipients()->where('name', 'like', '%'.$search.'%')->whereKeyNot($this->recipientIds)->orderBy('name')->limit(8)->get()
                : collect(),
            'memberMatches' => $conversation && $this->addingPeople && mb_strlen($memberSearch) >= 2
                ? $this->recipients()->where('name', 'like', '%'.$memberSearch.'%')->whereKeyNot($conversation->participants->pluck('id'))->orderBy('name')->limit(8)->get()
                : collect(),
            'audiences' => Conversation::AUDIENCES,
        ]);
    }
}
