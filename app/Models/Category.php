<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['legacy_id', 'parent_id', 'name', 'slug', 'description', 'position'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('name');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function publicUrl(): string
    {
        return route('publication.show', ['slug' => $this->slug], absolute: false);
    }
}
