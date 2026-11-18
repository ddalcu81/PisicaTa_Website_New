<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PublicationController
{
    public function home()
    {
        $latest = Article::published()->with('category')->newest()->limit(6)->get();

        return view('public.home', [
            'featured' => $latest->shift(),
            'latest' => $latest,
            'categories' => $this->categories(),
        ]);
    }

    public function index()
    {
        return view('public.list', [
            'heading' => 'Toate articolele',
            'intro' => 'Explorează ghidurile despre îngrijire, sănătate, comportament și rase de pisici.',
            'articles' => Article::published()->with('category')->newest()->paginate(15)->withQueryString(),
            'categories' => $this->categories(),
        ]);
    }

    public function search(Request $request)
    {
        $input = $request->query('q');
        $query = is_string($input) ? mb_substr(trim($input), 0, 100) : '';
        $articles = Article::published()->with('category');
        if ($query === '') {
            $articles->whereRaw('1 = 0');
        } else {
            foreach (array_slice(preg_split('/\s+/u', $query, flags: PREG_SPLIT_NO_EMPTY), 0, 8) as $term) {
                $pattern = '%'.strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                $articles->where(function ($builder) use ($pattern) {
                    $builder->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("summary LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("body_html LIKE ? ESCAPE '!'", [$pattern]);
                });
            }
        }

        return view('public.list', [
            'heading' => $query === '' ? 'Caută în articole' : 'Rezultate pentru „'.$query.'”',
            'query' => $query,
            'articles' => $articles->newest()->paginate(15)->appends(['q' => $query]),
            'categories' => $this->categories(),
        ]);
    }

    public function show(string $slug)
    {
        $slug = rawurldecode($slug);
        $category = Category::where('slug', $slug)->with(['parent', 'children'])->first();
        if ($category) {
            $articles = Article::published()->with('category')->whereIn('category_id', $category->children->pluck('id')->push($category->id));
            if ($category->slug === 'rase') {
                $articles->orderBy('title');
            } else {
                $articles->newest();
            }

            return view('public.list', [
                'heading' => $category->name,
                'intro' => $category->description,
                'articles' => $articles->paginate(15),
                'category' => $category,
                'categories' => $this->categories(),
            ]);
        }

        $article = Article::published()->where('slug', $slug)->with('category.parent')->first();
        if (! $article) {
            $alias = DB::table('legacy_urls')->where('path', $slug)->value('article_id');
            $target = $alias ? Article::published()->find($alias) : null;
            abort_unless($target, 404);

            return redirect($target->publicUrl(), 301);
        }

        return view('public.article', [
            'article' => $article,
            'related' => Article::published()->with('category')->where('category_id', $article->category_id)
                ->whereKeyNot($article->id)->newest()->limit(3)->get(),
            'categories' => $this->categories(),
        ]);
    }

    public function legacy(Request $request)
    {
        $articleId = $request->query('artid');
        $categoryId = $request->query('catart');

        $target = match (true) {
            is_string($articleId) && ctype_digit($articleId) && $categoryId === null => Article::published()->where('legacy_id', $articleId)->first(),
            is_string($categoryId) && ctype_digit($categoryId) && $articleId === null => Category::where('legacy_id', $categoryId)->first(),
            $articleId === null && $categoryId === null => null,
            default => abort(404),
        };

        if ($articleId === null && $categoryId === null) {
            return redirect()->route('publication.index', status: 301);
        }
        abort_unless($target, 404);

        return redirect($target->publicUrl(), 301);
    }

    public function sitemap()
    {
        return response()->view('public.sitemap', [
            'categories' => Category::orderBy('id')->get(),
            'articles' => Article::published()->newest()->get(['id', 'slug', 'published_at', 'updated_at']),
        ], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $rules = config('pisicata.indexable')
            ? "User-agent: *\nDisallow: /admin/\nDisallow: /login\nSitemap: ".rtrim(config('app.url'), '/')."/sitemap.xml\n"
            : "User-agent: *\nDisallow: /\n";

        return response($rules, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function ads()
    {
        $client = config('pisicata.adsense.client');
        abort_unless(config('pisicata.indexable') && config('pisicata.adsense.enabled') && preg_match('/^ca-pub-\d{16}$/D', (string) $client), 404);

        return response('google.com, '.substr($client, 3).", DIRECT, f08c47fec0942fa0\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function image(string $file)
    {
        $disk = Storage::disk('local');
        $path = 'article-covers/'.$file;
        abort_unless(Article::published()->where('image_path', $path)->exists() && $disk->exists($path), 404);

        return response()->file($disk->path($path), ['Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function categories(): Collection
    {
        return Category::with('children')->whereNull('parent_id')->orderBy('position')->orderBy('name')->get();
    }
}
