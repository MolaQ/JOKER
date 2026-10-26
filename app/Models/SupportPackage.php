<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pakiet sponsorski na stronie „Wspieraj klub”: nazwa, cena (tekst, np. „od 2 000 zł / sezon”) i korzyści.
 */
class SupportPackage extends Model
{
    protected $fillable = [
        'name',
        'price',
        'benefits',
        'is_highlighted',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_highlighted' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public function benefitList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $this->benefits))));
    }
}
