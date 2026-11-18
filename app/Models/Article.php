<?php

namespace App\Models;

use App\Support\ArticleCover;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $fillable = [
        'legacy_id', 'category_id', 'title', 'slug', 'summary', 'body_html',
        'image_path', 'image_alt', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<', today()->addDay()->toDateString());
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    public function publicUrl(): string
    {
        return route('publication.show', ['slug' => $this->slug], absolute: false);
    }

    public function cover(): string
    {
        if ($this->image_path) {
            return route('publication.image', ['file' => basename($this->image_path)], absolute: false);
        }

        return $this->sourcedCover()['path'] ?? '/media/editorial/cat-'.(($this->id % 4) + 1).'.webp';
    }

    public function sourcedCover(): ?array
    {
        return $this->image_path ? null : app(ArticleCover::class)->forLegacyId($this->legacy_id);
    }

    public function coverAlt(): string
    {
        return $this->image_alt ?? $this->sourcedCover()['alt'] ?? '';
    }
}
