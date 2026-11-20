<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EditorialRevision
{
    public static function fingerprint(Article $article): string
    {
        return hash('sha256', json_encode(array_map(fn ($key) => $article->getRawOriginal($key),
            ['title', 'summary', 'body_html', 'slug']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function run(array $manifest, ?string $backup = null): int
    {
        if (($manifest['schema'] ?? null) !== 1 || ! is_array($manifest['articles'] ?? null)) {
            throw new RuntimeException('Invalid revision manifest.');
        }

        return DB::transaction(function () use ($manifest, $backup): int {
            $pending = [];
            $seen = [];
            foreach ($manifest['articles'] as $row) {
                $id = $row['legacy_id'] ?? null;
                if (! is_int($id) || isset($seen[$id])) {
                    throw new RuntimeException('Invalid or repeated legacy ID.');
                }
                $seen[$id] = true;
                $article = Article::where('legacy_id', $id)->lockForUpdate()->firstOrFail();
                $changes = $row['changes'] ?? [];
                if (! $changes || array_diff(array_keys($changes), ['title', 'summary', 'body_html', 'updated_at'])) {
                    throw new RuntimeException("Unsupported changes for article {$id}.");
                }
                foreach ($changes as $field => $value) {
                    if (! is_string($value) && ! ($field === 'summary' && $value === null)) {
                        throw new RuntimeException("Invalid value for article {$id}.");
                    }
                }
                if (empty($manifest['rollback']) && isset($changes['body_html']) && app(ArticleHtml::class)->sanitize($changes['body_html']) !== $changes['body_html']) {
                    throw new RuntimeException("Article {$id} HTML must already be sanitized.");
                }
                if (collect($changes)->every(fn ($value, $field) => $article->getRawOriginal($field) === $value)) {
                    continue;
                }
                if (! hash_equals($row['expected_sha256'] ?? '', self::fingerprint($article))) {
                    throw new RuntimeException("Article {$id} has changed since review; no revisions applied.");
                }
                $before = [];
                foreach (array_unique([...array_keys($changes), 'updated_at']) as $field) {
                    $before[$field] = $article->getRawOriginal($field);
                }
                $article->forceFill($changes);
                if (! array_key_exists('updated_at', $changes)) {
                    $article->setUpdatedAt(now());
                }
                // Fingerprint the exact replacement before any database writes.
                $after = clone $article;
                $after->syncOriginal();
                $pending[] = [$article, ['legacy_id' => $id,
                    'expected_sha256' => self::fingerprint($after), 'changes' => $before]];
            }

            if ($backup !== null && $pending) {
                $path = realpath(dirname($backup));
                if (! $path || str_starts_with($path.'/', realpath(public_path()).'/')) {
                    throw new RuntimeException('The backup must be outside the public directory.');
                }
                $handle = @fopen($backup, 'x');
                if (! $handle) {
                    throw new RuntimeException('Cannot create a new backup; existing files are never overwritten.');
                }
                try {
                    chmod($backup, 0600);
                    $json = json_encode(['schema' => 1, 'rollback' => true, 'articles' => array_column($pending, 1)],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                    if (fwrite($handle, $json) !== strlen($json) || ! fflush($handle)) {
                        throw new RuntimeException('Could not write the complete revision backup.');
                    }
                } finally {
                    fclose($handle);
                }
                foreach ($pending as [$article]) {
                    $article->saveOrFail();
                }
            }

            return count($pending);
        });
    }
}
