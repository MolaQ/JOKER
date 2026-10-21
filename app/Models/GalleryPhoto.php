<?php

namespace App\Models;

use App\Support\Media;
use Database\Factories\GalleryPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryPhoto extends Model
{
    /** @use HasFactory<GalleryPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'gallery_id',
        'path',
        'caption',
        'display_order',
    ];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function url(): ?string
    {
        return Media::url($this->path);
    }
}
