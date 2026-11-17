<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Support\ArticleHtml;
use App\Support\EditorialSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class AdminArticleController
{
    public function index(Request $request)
    {
        $query = trim($request->string('q')->limit(100, '')->toString());
        $status = $request->string('status')->toString();

        $articles = Article::query()->with('category:id,name')
            ->when($query !== '', fn ($builder) => $builder->where('title', 'like', '%'.$query.'%'))
            ->when(in_array($status, ['draft', 'published'], true), fn ($builder) => $builder->where('status', $status))
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'category' => $article->category->name,
                'status' => $article->status,
                'published_on' => $article->published_at?->toDateString(),
                'edit_url' => route('admin.articles.edit', $article, absolute: false),
                'public_url' => $article->status === 'published' && $article->published_at?->lte(today()) ? $article->publicUrl() : null,
            ]);

        return Inertia::render('Articles', ['articles' => $articles, 'filters' => ['q' => $query, 'status' => $status]]);
    }

    public function create()
    {
        return $this->editor(new Article(['status' => 'draft']));
    }

    public function edit(Article $article)
    {
        return $this->editor($article);
    }

    public function store(Request $request, ArticleHtml $html)
    {
        return $this->save($request, new Article, $html);
    }

    public function update(Request $request, Article $article, ArticleHtml $html)
    {
        return $this->save($request, $article, $html);
    }

    private function editor(Article $article)
    {
        return Inertia::render('ArticleEditor', [
            'article' => [
                ...$article->only('id', 'title', 'slug', 'summary', 'body_html', 'category_id', 'status', 'image_alt'),
                'published_at' => $article->published_at?->toDateString(),
                'has_image' => (bool) $article->image_path,
                'public_url' => $article->exists && $article->status === 'published' && $article->published_at?->lte(today()) ? $article->publicUrl() : null,
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'action' => $article->exists ? route('admin.articles.update', $article, absolute: false) : route('admin.articles.store', absolute: false),
        ]);
    }

    private function save(Request $request, Article $article, ArticleHtml $html)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => $article->exists ? ['exclude'] : ['required', 'max:160', new EditorialSlug],
            'summary' => ['nullable', 'string', 'max:600'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'body_html' => ['nullable', 'required_if:status,published', 'string', 'max:200000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'required_if:status,published', 'date_format:Y-m-d'],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4800,max_height=4800'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $data['body_html'] = $html->sanitize($data['body_html'] ?? '');
        if ($data['status'] === 'published' && trim(html_entity_decode(strip_tags($data['body_html']))) === '') {
            throw ValidationException::withMessages(['body_html' => 'Adaugă text înainte de publicare.']);
        }

        unset($data['image'], $data['remove_image']);
        $oldImage = $article->image_path;
        $newImage = $request->file('image')?->store('article-covers', 'local');
        if ($request->hasFile('image') && ! $newImage) {
            throw ValidationException::withMessages(['image' => 'Imaginea nu a putut fi salvată.']);
        }

        if ($newImage || $request->boolean('remove_image')) {
            $data['image_path'] = $newImage ?: null;
        }

        try {
            $article->fill($data)->saveOrFail();
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('local')->delete($newImage);
            }
            throw $exception;
        }
        if ($oldImage && $oldImage !== $article->image_path) {
            Storage::disk('local')->delete($oldImage);
        }

        return redirect()->route('admin.articles.edit', $article)->with('message', 'Articol salvat.');
    }
}
