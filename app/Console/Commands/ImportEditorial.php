<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Support\ArticleHtml;
use App\Support\LegacyEditorialExport;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportEditorial extends Command
{
    protected $signature = 'editorial:import {source} {--export= : Write an editorial-only JSON file without changing the database}';

    protected $description = 'Import public articles and categories from the legacy archive';

    public function handle(LegacyEditorialExport $exporter, ArticleHtml $html): int
    {
        $source = $this->argument('source');
        if (! is_file($source) || ! is_readable($source)) {
            $this->error('Editorial input is unavailable.');

            return self::FAILURE;
        }

        $contents = file_get_contents($source);
        $data = str_ends_with(strtolower($source), '.sql')
            ? $exporter->fromSql($contents)
            : json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (($data['schema'] ?? null) !== 1 || empty($data['categories']) || empty($data['articles'])) {
            $this->error('Invalid editorial export.');

            return self::FAILURE;
        }

        if ($destination = $this->option('export')) {
            if (file_exists($destination)) {
                $this->error('The export destination already exists.');

                return self::FAILURE;
            }

            file_put_contents($destination, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX);
        } else {
            DB::transaction(function () use ($data, $html): void {
                $categoryIds = [];
                foreach ($data['categories'] as $row) {
                    $category = Category::firstOrCreate(['legacy_id' => $row['legacy_id']],
                        Arr::only($row, ['name', 'slug', 'description', 'position']));
                    $categoryIds[$row['legacy_id']] = $category->id;
                    $newCategories[$row['legacy_id']] = $category->wasRecentlyCreated;
                }

                foreach ($data['categories'] as $row) {
                    if ($row['parent_legacy_id'] && $newCategories[$row['legacy_id']]) {
                        Category::whereKey($categoryIds[$row['legacy_id']])->update([
                            'parent_id' => $categoryIds[$row['parent_legacy_id']] ?? throw new RuntimeException('Missing parent category.'),
                        ]);
                    }
                }

                $articles = [];
                foreach ($data['articles'] as $row) {
                    $article = Article::firstOrNew(['legacy_id' => $row['legacy_id']], [
                        ...Arr::only($row, ['title', 'slug', 'summary', 'published_at']),
                        'body_html' => $html->sanitize($row['body_html']),
                        'category_id' => $categoryIds[$row['category_legacy_id']] ?? throw new RuntimeException('Missing article category.'),
                        'status' => 'published',
                    ]);
                    if (! $article->exists) {
                        $article->setCreatedAt($row['published_at']);
                        $article->setUpdatedAt($row['published_at']);
                        $article->saveOrFail();
                    }
                    $articles[$row['slug']] = $article->id;
                }

                foreach ($data['aliases'] as $path => $slug) {
                    if (isset($articles[$slug])) {
                        DB::table('legacy_urls')->insertOrIgnore(['path' => $path, 'article_id' => $articles[$slug]]);
                    }
                }
            });
        }

        $this->info(count($data['articles']).' articles, '.count($data['categories']).' categories, '.count($data['aliases']).' aliases.');

        return self::SUCCESS;
    }
}
