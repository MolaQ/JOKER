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
        'bebas' => ['Bebas Neue (wąska, same wielkie)', "'Bebas Neue', 'Oswald Variable', sans-serif"],
        'anton' => ['Anton (gruba, wąska)', "Anton, 'Oswald Variable', sans-serif"],
        'barlow' => ['Barlow Condensed (sportowa)', "'Barlow Condensed', 'Oswald Variable', sans-serif"],
        'roboto' => ['Roboto Condensed (prosta, wąska)', "'Roboto Condensed Variable', 'Roboto Condensed', sans-serif"],
        'montserrat' => ['Montserrat (szeroka, nowoczesna)', "'Montserrat Variable', Montserrat, Arial, sans-serif"],
        'serif' => ['Szeryfowa (jak w gazecie)', "'Times New Roman', 'Liberation Serif', Georgia, serif"],
    ];

    /** Czcionka napisu VS: pisana albo taka jak reszta grafiki. */
    public const VS_FONTS = [
        'brush' => ['Pędzel, poszarpana (jak Joker TV)', "'Protest Revolution', 'Permanent Marker', cursive"],
        'knewave' => ['Pędzel, gruba', 'Knewave, cursive'],
        'kaushan' => ['Kaushan Script (pisana, pędzel)', "'Kaushan Script', cursive"],
        'yellowtail' => ['Yellowtail (pisana, elegancka)', 'Yellowtail, cursive'],
        'marker' => ['Permanent Marker (flamaster)', "'Permanent Marker', cursive"],
        'same' => ['Jak reszta grafiki', null],
    ];

    /** Wygląd napisu VS: komiksowy wybuch, sam napis albo własny obrazek (PNG z przezroczystym tłem). */
    public const VS_STYLES = ['burst' => 'Wybuch: litery w kolorach drużyn', 'text' => 'Sam napis', 'image' => 'Własny obrazek'];

    /** Miejsce napisu o transmisji w zapowiedzi. */
    public const LIVE_POSITIONS = ['bl' => 'Lewy dolny róg', 'br' => 'Prawy dolny róg'];

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
        'title_color' => '#ffffff',
        'bar_from' => '#0b1f4d',
        'bar_to' => '#1d4ed8',
        'panel' => '#080a10',
        'background' => null,
        'tint' => true,
        'tint_gradient' => false,
        'vs_style' => 'burst',
        'vs_image' => null,
        'vs_size' => 100,
        'vs_offset' => 0,
        'vs_font' => 'brush',
        'vs_color' => '#ffffff',
        'live_position' => 'bl',
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

    public static function vsFontFamily(string $vsFont, string $font): string
    {
        return (self::VS_FONTS[$vsFont] ?? self::VS_FONTS['brush'])[1] ?? self::fontFamily($font);
    }
}
