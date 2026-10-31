<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;

/**
 * Gadżet klubowy. Zamówienie idzie formularzem kontaktowym (bez płatności online).
 */
class ShopProduct extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'sizes',
        'photo_path',
        'is_available',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function photoUrl(): ?string
    {
        return Media::url($this->photo_path);
    }

    public function priceLabel(): ?string
    {
        return $this->price === null ? null : number_format((float) $this->price, 2, ',', ' ').' zł';
    }

    public function orderUrl(): string
    {
        return route('contact.index', ['subject' => __('Zamówienie').': '.$this->name]);
    }
}
