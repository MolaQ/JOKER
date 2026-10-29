<?php

namespace App\Models;

use App\AccountStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * Rozmowa: dwie osoby, kilka wybranych osób albo grupa drużyny (np. „Kadeci II – rodzice”).
 * Do grupy drużyny przy każdej wiadomości dopisują się osoby, które w międzyczasie doszły do drużyny.
 */
class Conversation extends Model
{
    /** Grupy drużyny: kogo obejmują (sztab drużyny jest w każdej). */
    public const AUDIENCES = [
        'wszyscy' => 'zawodnicy, rodzice i sztab',
        'rodzice' => 'rodzice i sztab',
        'zawodnicy' => 'zawodnicy i sztab',
        'sztab' => 'sztab',
    ];

    protected $fillable = ['title', 'team_id', 'audience', 'created_by', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('last_read_message_id')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeFor(Builder $query, User $user): void
    {
        $query->whereHas('participants', fn (Builder $q) => $q->where('users.id', $user->id));
    }

    public function isTeamGroup(): bool
    {
        return $this->team_id !== null;
    }

    public function isGroup(): bool
    {
        return $this->isTeamGroup() || filled($this->title) || $this->participants->count() > 2;
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('users.id', $user->id)->exists();
    }

    /**
     * Nazwa rozmowy z punktu widzenia osoby, która ją czyta: grupa drużyny, nadany tytuł albo pozostali uczestnicy.
     */
    public function titleFor(User $viewer): string
    {
        if ($this->isTeamGroup()) {
            return ($this->team?->name ?? 'Drużyna').' – '.(self::AUDIENCES[$this->audience] ?? $this->audience);
        }

        if (filled($this->title)) {
            return $this->title;
        }

        $others = $this->participants->where('id', '!=', $viewer->id)->pluck('name');

        return $others->isEmpty() ? 'Notatki dla siebie' : $others->take(4)->join(', ').($others->count() > 4 ? ' i '.($others->count() - 4).' inne osoby' : '');
    }

    /**
     * Rozmowa tylko tych dwóch osób (bez tytułu i drużyny); tworzy ją, gdy jeszcze nie ma.
     */
    public static function between(User $one, User $two): self
    {
        $existing = self::query()
            ->whereNull('team_id')
            ->whereNull('title')
            ->whereHas('participants', fn (Builder $q) => $q->where('users.id', $one->id))
            ->whereHas('participants', fn (Builder $q) => $q->where('users.id', $two->id))
            ->has('participants', '=', 2)
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = self::create(['created_by' => $one->id]);
        $conversation->participants()->attach([$one->id, $two->id]);

        return $conversation;
    }

    /**
     * Grupa drużyny dla wybranych odbiorców; jedna na drużynę i odbiorców.
     */
    public static function forTeam(Team $team, string $audience, User $creator): self
    {
        $conversation = self::firstOrCreate(['team_id' => $team->id, 'audience' => $audience], ['created_by' => $creator->id]);
        $conversation->syncTeamMembers();
        $conversation->participants()->syncWithoutDetaching([$creator->id]);

        return $conversation;
    }

    /**
     * Konta, które należą do grupy drużyny: sztab, koordynatorzy, a do tego zawodnicy i/lub ich rodzice.
     *
     * @return Collection<int, int>
     */
    public function teamMemberIds(): Collection
    {
        $team = $this->team;

        if (! $team) {
            return collect();
        }

        $players = Player::inTeam($team->id)->where('is_active', true)->with('guardians:id')->get(['id', 'user_id']);
        $ids = collect([$team->trainer_id])
            ->merge($team->assistantTrainers()->pluck('users.id'))
            ->merge($team->coordinators()->pluck('users.id'));

        if (in_array($this->audience, ['wszyscy', 'zawodnicy'], true)) {
            $ids = $ids->merge($players->pluck('user_id'));
        }

        if (in_array($this->audience, ['wszyscy', 'rodzice'], true)) {
            $ids = $ids->merge($players->flatMap(fn (Player $player) => $player->guardians->pluck('id')));
        }

        return User::whereIn('id', $ids->filter()->unique())->withAccountStatus(AccountStatus::Active)->pluck('id');
    }

    /**
     * Dopisuje do grupy drużyny nowe osoby; dołączają „od teraz”, bez starych wiadomości jako nieprzeczytanych.
     */
    public function syncTeamMembers(): void
    {
        if (! $this->isTeamGroup()) {
            return;
        }

        $missing = $this->teamMemberIds()->diff($this->participants()->pluck('users.id'));

        if ($missing->isNotEmpty()) {
            $lastId = (int) $this->messages()->max('id');
            $this->participants()->attach($missing->mapWithKeys(fn (int $id) => [$id => ['last_read_message_id' => $lastId]])->all());
        }
    }

    public function markReadBy(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, ['last_read_message_id' => (int) $this->messages()->max('id')]);
    }
}
