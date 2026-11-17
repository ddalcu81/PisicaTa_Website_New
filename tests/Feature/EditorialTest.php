<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

class EditorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_html_and_metadata_are_available_without_javascript(): void
    {
        $article = $this->article();
        $this->get($article->publicUrl())
            ->assertOk()
            ->assertSee('<h1>Îngrijirea pisicilor</h1>', false)
            ->assertSee('<p>Un ghid despre pisici și periaj.</p>', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('<link rel="canonical" href="http://localhost/%C3%AEngrijirea-pisicilor">', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertDontSee('pagead2.googlesyndication.com');

        $this->get('/articol.php?artid=8')->assertRedirect($article->publicUrl())->assertStatus(301);
        $this->get('/articol.php?catart=3')->assertRedirect('/ingrijire')->assertStatus(301);
        DB::table('legacy_urls')->insert(['path' => 'ghid-vechi', 'article_id' => $article->id]);
        $this->get('/ghid-vechi')->assertRedirect($article->publicUrl())->assertStatus(301);
        $this->get('/necunoscut')->assertNotFound();
        $this->get('/up')->assertOk();
    }

    public function test_search_and_sitemap_only_show_public_articles(): void
    {
        $article = $this->article();
        $this->article(['legacy_id' => null, 'title' => 'Ciornă pentru pisici', 'slug' => 'ciorna', 'status' => 'draft']);
        $this->article(['legacy_id' => null, 'title' => 'Articol viitor despre pisici', 'slug' => 'viitor', 'published_at' => now()->addDay()->toDateString()]);

        $this->get('/cauta?q=pisici')->assertOk()->assertSee($article->title)->assertDontSee('Ciornă pentru pisici')->assertDontSee('Articol viitor despre pisici');
        $this->get('/sitemap.xml')->assertOk()->assertSee($article->publicUrl())->assertDontSee('/ciorna')->assertDontSee('/viitor');
        $this->get('/ciorna')->assertNotFound();
        $this->get('/viitor')->assertNotFound();
        $this->get('/robots.txt')->assertSee('Disallow: /');
    }

    public function test_admin_can_publish_and_attach_an_image(): void
    {
        $category = Category::create(['name' => 'Sfaturi', 'slug' => 'sfaturi']);
        Storage::fake('local');
        $this->get('/admin/articole')->assertRedirect('/login');

        $admin = User::create(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => 'fixture-password', 'is_admin' => true]);
        $this->post('/login', ['email' => $admin->email, 'password' => 'fixture-password'])->assertRedirect('/admin/articole');
        $this->get('/admin/articole')->assertInertia(fn (AssertableInertia $page) => $page->component('Articles'));

        $this->post('/admin/articole', [
            'title' => 'Un articol nou',
            'slug' => 'un-articol-nou',
            'category_id' => $category->id,
            'body_html' => '<script>alert(1)</script><p onclick="alert(2)">Conținut nou.</p><img src="/images/old.jpg"><a href="javascript:alert(3)">Legătură</a>',
            'status' => 'draft',
            'image' => UploadedFile::fake()->image('cat.jpg', 500, 330),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $article = Article::sole();
        $this->assertSame('<p>Conținut nou.</p><a>Legătură</a>', $article->body_html);
        $this->get($article->publicUrl())->assertNotFound();
        $this->get($article->cover())->assertNotFound();

        $this->patch('/admin/articole/'.$article->id, [
            'title' => 'Un articol nou',
            'slug' => 'should-stay-unchanged',
            'category_id' => $category->id,
            'body_html' => '<p>Conținut publicat.</p>',
            'status' => 'published',
            'published_at' => today()->toDateString(),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('un-articol-nou', $article->fresh()->slug);
        $this->get($article->publicUrl())->assertOk()->assertSee('Conținut publicat.');
        $this->get($article->cover())->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->post('/admin/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_staging_and_unconfigured_pages_never_render_ads(): void
    {
        $article = $this->article(['body_html' => '<p>'.str_repeat('Îngrijire și periaj. ', 350).'</p>']);
        config([
            'pisicata.adsense.enabled' => true,
            'pisicata.adsense.client' => 'ca-pub-0000000000000000',
            'pisicata.adsense.rail' => '123456',
            'pisicata.adsense.article' => '123457',
        ]);
        $this->get($article->publicUrl())->assertDontSee('data-ad-slot');
        $this->get('/ads.txt')->assertNotFound();

        config(['pisicata.indexable' => true]);
        $this->get($article->publicUrl())->assertOk()->assertSee('data-ad-slot="123456"', false)->assertSee('data-ad-slot="123457"', false);
        $this->get('/')->assertDontSee('data-ad-slot');
        $this->get('/ads.txt')->assertOk()->assertSee('google.com, pub-0000000000000000');
    }

    private function article(array $data = []): Article
    {
        $category = Category::firstOrCreate(['slug' => 'ingrijire'], ['legacy_id' => 3, 'name' => 'Îngrijire']);
        return Article::create($data + [
            'legacy_id' => 8,
            'category_id' => $category->id,
            'slug' => 'îngrijirea-pisicilor',
            'title' => 'Îngrijirea pisicilor',
            'summary' => 'Sfaturi despre pisici.',
            'body_html' => '<p>Un ghid despre pisici și periaj.</p>',
            'status' => 'published',
            'published_at' => '2020-06-15',
        ]);
    }
}
