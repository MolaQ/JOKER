<?php

namespace App\Models;

use App\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Szablon miniatury transmisji (/miniatury): wygląd zapisany dla rozgrywek albo turnieju.
 * Szablon usuwa jego autor albo administrator; szablony startowe (bez autora) tylko administrator.
 */
class ThumbnailTemplate extends Model
{
    /** Czcionki do wyboru: klucz => [nazwa, CSS font-family]. */
    public const FONTS = [
        'oswald' => ['Oswald (klubowa, wąska)', "'Oswald Variable', Oswald, 'Arial Narrow', sans-serif"],
        'inter' => ['Inter (prosta)', "'Inter Variable', Inter, Arial, sans-serif"],
        'serif' => ['Szeryfowa (jak w gazecie)', "'Times New Roman', 'Liberation Serif', Georgia, serif"],
        'impact' => ['Impact (gruba)', "Impact, 'Arial Black', 'Oswald Variable', sans-serif"],
    ];

    /** Układy: nowoczesny (zdjęcie w kolorach drużyn) albo z belkami u góry i u dołu. */
    public const LAYOUTS = [
        'modern' => 'Nowoczesny: zdjęcie w kolorach drużyn',
        'bars' => 'Belki: nazwa rozgrywek u góry, data i miejsce u dołu',
    ];

    public const POSITIONS = ['tl' => 'Lewy górny róg', 'tr' => 'Prawy górny róg', 'bl' => 'Lewy dolny róg', 'br' => 'Prawy dolny róg'];

    public const DEFAULTS = [
        'layout' => 'modern',
        'font' => 'oswald',
        'label' => '',
        'preview_title' => 'Zapowiedź meczu',
        'summary_title' => 'Wynik meczu',
        'live_text' => 'Transmisja na żywo',
        'accent' => '#60a5fa',
        'bar_from' => '#0b1f4d',
        'bar_to' => '#1d4ed8',
        'panel' => '#080a10',
        'background' => null,
        'tint' => true,
        'show_names' => true,
        'show_sponsors' => false,
        'tournament_logo' => false,
        'images' => [],
    ];

    protected $fillable = ['user_id', 'name', 'competition_id', 'tournament_id', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Ustawienia z wartościami domyślnymi dla brakujących kluczy.
     *
     * @return array<string, mixed>
     */
    public function resolvedSettings(): array
    {
        return array_replace(self::DEFAULTS, array_intersect_key($this->settings ?? [], self::DEFAULTS));
    }

    public function canBeChangedBy(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || ($this->user_id !== null && $this->user_id === $user->id);
    }

    public static function fontFamily(string $font): string
    {
        return (self::FONTS[$font] ?? self::FONTS['oswald'])[1];
    }
}
