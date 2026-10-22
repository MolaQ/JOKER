<?php

namespace App\Models;

use App\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerChangeRequest extends Model
{
    public const TYPE_DATA = 'data';

    public const TYPE_PHOTO = 'photo';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Pola profilu zawodnika, które zawodnik lub rodzic może zaproponować.
     *
     * @var array<string, string>
     */
    public const EDITABLE_FIELDS = [
        'height' => 'Wzrost (cm)',
        'weight' => 'Waga (kg)',
        'reach' => 'Zasięg (cm)',
        'spike_reach' => 'Zasięg w ataku (cm)',
        'block_reach' => 'Zasięg w bloku (cm)',
        'bio' => 'O mnie',
        'interests' => 'Zainteresowania',
        'social_links' => 'Media społecznościowe',
        'email' => 'E-mail zawodnika (konto na stronie)',
    ];

    /**
     * Pola liczbowe (w formularzu jako pola liczb).
     *
     * @var list<string>
     */
    public const NUMERIC_FIELDS = ['height', 'weight', 'reach', 'spike_reach', 'block_reach'];

    /**
     * Wartość pola do porównania i pokazania (bez propozycji = aktualny stan profilu).
     */
    public static function displayValue(string $field, mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)->filter()->map(fn ($url, $key) => $key.': '.$url)->implode("\n") ?: '—';
        }

        return $value === null || $value === '' ? '—' : (string) $value;
    }

    protected $fillable = [
        'player_id',
        'submitted_by',
        'type',
        'proposed_data',
        'photo_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'proposed_data' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Zgłoszenia, które użytkownik może rozpatrzyć: z prawem zatwierdzania, ze wszystkich drużyn albo tylko z prowadzonych.
     */
    public function scopeReviewableBy($query, User $user)
    {
        if (! $user->hasPermission(Permission::Proposals)) {
            return $query->whereIn('id', []);
        }

        if (($teamIds = $user->managedTeamIds()) === null) {
            return $query;
        }

        return $query->whereHas('player', fn ($player) => $player->whereIn('team_id', $teamIds));
    }

    /**
     * Biografia z edytora (HTML) jako zwykły tekst do formularza propozycji.
     */
    public static function bioAsText(?string $html): string
    {
        $text = preg_replace(['#<br\s*/?>#i', '#</p>\s*#i'], ["\n", "\n\n"], (string) $html);

        return trim(html_entity_decode(strip_tags((string) $text)));
    }

    /**
     * Zaproponowany tekst biografii jako akapity HTML (bezpiecznie zakodowane).
     */
    public static function bioAsHtml(?string $text): ?string
    {
        $paragraphs = array_filter(array_map('trim', preg_split('/\n\s*\n/', trim((string) $text))));

        if ($paragraphs === []) {
            return null;
        }

        return collect($paragraphs)->map(fn (string $paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')->implode('');
    }

    public function isPhoto(): bool
    {
        return $this->type === self::TYPE_PHOTO;
    }
}
