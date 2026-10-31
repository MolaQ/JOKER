<?php

namespace App\Models;

use App\Support\SiteSettings;
use App\Support\Trash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Rekord usunięty w panelu, do przywrócenia przez liczbę dni z ustawienia „Kosz: ile dni”.
 */
class TrashItem extends Model
{
    use Prunable;

    protected $fillable = ['type', 'item_id', 'label', 'payload', 'deleted_by'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public static function days(): int
    {
        return max(1, (int) (app(SiteSettings::class)['trash_days'] ?? 30));
    }

    public function expiresAt(): Carbon
    {
        return $this->created_at->copy()->addDays(self::days());
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Ile wierszy (z zależnymi) wróci po przywróceniu.
     */
    public function rowCount(): int
    {
        return collect($this->payload['rows'] ?? [])->sum(fn (array $rows) => count($rows));
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::days()));
    }

    /**
     * Ostateczne usunięcie: dopiero teraz kasujemy pliki (np. zdjęcia galerii).
     */
    protected function pruning(): void
    {
        $this->purgeFiles();
    }

    public function purgeFiles(): void
    {
        $files = Trash::files($this->payload ?? []);

        if ($files) {
            Storage::disk('public')->delete($files);
        }
    }
}
