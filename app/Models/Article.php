<?php

namespace App\Models;

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
        return $this->image_path
            ? route('publication.image', ['file' => basename($this->image_path)], absolute: false)
            : '/media/editorial/cat-'.(($this->id % 4) + 1).'.webp';
    }
}
