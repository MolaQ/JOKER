<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jedna wiadomość w rozmowie. Moderator może ją ukryć, a uczestnik rozmowy zgłosić.
 */
class Message extends Model
{
    public const MAX_LENGTH = 4000;

    protected $fillable = ['conversation_id', 'user_id', 'body', 'hidden_at', 'hidden_by', 'reported_at', 'reported_by'];

    protected function casts(): array
    {
        return [
            'hidden_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /**
     * Zgłoszone i jeszcze nierozpatrzone (nieukryte).
     */
    public function scopeReported(Builder $query): void
    {
        $query->whereNotNull('reported_at')->whereNull('hidden_at');
    }

    /**
     * Nieprzeczytane przez użytkownika: w jego rozmowach, od innych osób, nowsze niż ostatnie otwarcie rozmowy.
     */
    public function scopeUnreadBy(Builder $query, User $user): void
    {
        $query->join('conversation_user as reader', function ($join) use ($user) {
            $join->on('reader.conversation_id', '=', 'messages.conversation_id')->where('reader.user_id', $user->id);
        })
            ->where(fn (Builder $q) => $q->whereNull('messages.user_id')->orWhere('messages.user_id', '!=', $user->id))
            ->whereNull('messages.hidden_at')
            ->whereColumn('messages.id', '>', 'reader.last_read_message_id')
            ->select('messages.*');
    }
}
