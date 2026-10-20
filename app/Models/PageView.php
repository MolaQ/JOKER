<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jedno wyświetlenie strony (kind=page) albo przełączenie zakładki na stronie (kind=tab).
 */
class PageView extends Model
{
    use Prunable;

    public const KIND_PAGE = 'page';

    public const KIND_TAB = 'tab';

    /** Statystyki trzymamy przez ok. 13 miesięcy, żeby porównać sezony. */
    public const RETENTION_DAYS = 400;

    public const UPDATED_AT = null;

    protected $fillable = [
        'page_view_id',
        'kind',
        'path',
        'route_name',
        'title',
        'label',
        'visitor_hash',
        'referrer_host',
        'device',
        'locale',
        'duration_seconds',
        'max_scroll',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(self::class, 'page_view_id');
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    /**
     * Podpis wyświetlenia przekazywany do przeglądarki, żeby nikt nie dopisał danych do cudzych wizyt.
     */
    public function token(): string
    {
        return $this->id.'.'.substr(hash_hmac('sha256', (string) $this->id, (string) config('app.key')), 0, 16);
    }

    public static function fromToken(?string $token): ?self
    {
        [$id, $signature] = array_pad(explode('.', (string) $token, 2), 2, '');

        if (! ctype_digit($id) || ! hash_equals(substr(hash_hmac('sha256', $id, (string) config('app.key')), 0, 16), $signature)) {
            return null;
        }

        return static::where('kind', self::KIND_PAGE)->find((int) $id);
    }
}
