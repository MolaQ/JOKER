<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Przeglądarka (telefon albo komputer), która zgodziła się na powiadomienia o nowych treściach.
 */
class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint_hash',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_id',
        'topics',
        'teams',
        'participants',
        'last_sent_at',
    ];

    /** Tematy do wyboru przez kibica (włączone przez administratora pokazują się w okienku dzwonka). */
    public const TOPICS = [
        'articles' => 'Nowe aktualności',
        'results' => 'Wyniki meczów',
        'galleries' => 'Nowe galerie zdjęć',
        'tournaments' => 'Wyniki meczów w turniejach',
        'upcoming' => 'Przypomnienie przed meczem',
    ];

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
            'topics' => 'array',
            'teams' => 'array',
            'participants' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Czy to urządzenie ma dostać powiadomienie.
     * Odbiorcy: topic (null = wszyscy, np. wiadomość wysłana ręcznie), teams (drużyny klubu, których dotyczy;
     * null = nie dotyczy drużyny, [] = żadnej naszej drużyny) i participants (drużyny turniejowe obserwowane przez kibica).
     *
     * @param  array{topic?: ?string, teams?: list<int>|null, participants?: list<int>}  $audience
     */
    public function wants(array $audience): bool
    {
        $topic = $audience['topic'] ?? null;

        if ($topic === null) {
            return true;
        }

        if ($this->topics !== null && ! in_array($topic, $this->topics, true)) {
            return false;
        }

        if (array_intersect($this->participants ?? [], $audience['participants'] ?? []) !== []) {
            return true;
        }

        $teams = $audience['teams'] ?? null;

        return $teams === null || ($teams !== [] && ($this->teams === null || array_intersect($this->teams, $teams) !== []));
    }
}
