<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Wyróżnienie albo certyfikat klubu (np. Brązowy Ośrodek PZPS) z opcjonalnym logo.
 * Pokazuje się w stopce każdej strony i trafia do danych o klubie dla Google.
 */
class Award extends Model
{
    protected $fillable = ['name', 'note', 'logo_path', 'url', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'display_order' => 'integer'];
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('display_order')->orderBy('id');
    }

    /**
     * Pełna nazwa do danych dla Google, np. „Brązowy Ośrodek PZPS (Certyfikacja … PZPS)”.
     */
    public function fullName(): string
    {
        return $this->name.(filled($this->note) ? ' ('.$this->note.')' : '');
    }
}
