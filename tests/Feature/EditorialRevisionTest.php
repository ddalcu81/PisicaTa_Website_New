<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Support\ArticleHtml;
use App\Support\EditorialRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use RuntimeException;

class EditorialRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_whitespace_headings_are_removed_without_losing_text(): void
    {
        $html = new ArticleHtml;
        $this->assertSame('<h2>Somnul pisicii</h2><p>Un text.</p><div><hr></div>',
            $html->sanitize('<h2><em>&nbsp;</em></h2><h5>​</h5><h2>Somnul pisicii</h2><p>Un text.</p><div><hr></div>'));
    }

    public function test_revisions_preview_apply_and_restore_exact_original_content(): void
    {
        $article = $this->article(1);
        $article = $article->fresh();
        $before = $article->getRawOriginal();
        $manifest = ['schema' => 1, 'articles' => [$this->change($article)]];
        $revision = new EditorialRevision;
        $backup = tempnam(sys_get_temp_dir(), 'pisicata-revision-');
        unlink($backup);
        $rollbackBackup = $backup.'-rollback';
        try {
            $this->assertSame(1, $revision->run($manifest));
            $this->assertSame($before, $article->fresh()->getRawOriginal());
            $this->assertSame(1, $revision->run($manifest, $backup));
            $this->assertSame('Titlu corectat', $article->fresh()->title);
            $this->assertSame($before['slug'], $article->fresh()->slug);
            $this->assertSame($before['published_at'], $article->fresh()->getRawOriginal('published_at'));
            $this->assertSame(0, $revision->run($manifest));
            $rollback = json_decode(file_get_contents($backup), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(1, $revision->run($rollback, $rollbackBackup));
            $this->assertSame($before, $article->fresh()->getRawOriginal());
        } finally {
            @unlink($backup);
            @unlink($rollbackBackup);
        }
    }

    public function test_intervening_edit_aborts_the_entire_batch(): void
    {
        $one = $this->article(1);
        $two = $this->article(2);
        $manifest = ['schema' => 1, 'articles' => [$this->change($one), $this->change($two)]];
        $two->update(['title' => 'Modificat de editor']);
        $backup = sys_get_temp_dir().'/pisicata-unused-'.bin2hex(random_bytes(8));
        try {
            (new EditorialRevision)->run($manifest, $backup);
            $this->fail('Expected an editorial conflict.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('has changed since review', $error->getMessage());
        }
        $this->assertSame($one->title, $one->fresh()->title);
        $this->assertSame('Modificat de editor', $two->fresh()->title);
        $this->assertFileDoesNotExist($backup);
    }

    private function change(Article $article): array
    {
        return ['legacy_id' => $article->legacy_id, 'expected_sha256' => EditorialRevision::fingerprint($article),
            'changes' => ['title' => 'Titlu corectat', 'body_html' => '<p>Conținut îmbunătățit.</p>']];
    }

    private function article(int $id): Article
    {
        $category = Category::firstOrCreate(['slug' => 'ingrijire'], ['name' => 'Îngrijire']);

        return Article::create(['legacy_id' => $id, 'category_id' => $category->id, 'slug' => 'articol-'.$id,
            'title' => 'Titlu vechi', 'summary' => 'Un rezumat.', 'body_html' => '<h2>&nbsp;</h2><p>Text vechi.</p>',
            'status' => 'published', 'published_at' => '2013-01-01', 'updated_at' => '2013-01-01 00:00:00']);
    }
}
