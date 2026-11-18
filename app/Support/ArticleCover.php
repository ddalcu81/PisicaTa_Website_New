<?php

namespace App\Support;

class ArticleCover
{
    private ?array $legacyCovers = null;

    public function forLegacyId(?int $legacyId): ?array
    {
        if ($legacyId === null) {
            return null;
        }

        $this->legacyCovers ??= json_decode(
            file_get_contents(resource_path('editorial/article-covers.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $this->legacyCovers[$legacyId] ?? null;
    }
}
