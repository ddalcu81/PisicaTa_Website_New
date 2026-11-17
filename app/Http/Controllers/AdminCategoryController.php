<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\EditorialSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminCategoryController
{
    public function index()
    {
        return Inertia::render('Categories', [
            'categories' => Category::withCount('articles')->orderBy('position')->orderBy('name')
                ->get(['id', 'name', 'parent_id', 'slug', 'description', 'position']),
        ]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new Category);
    }

    public function update(Request $request, Category $category)
    {
        return $this->save($request, $category);
    }

    private function save(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $category->exists ? ['exclude'] : ['required', 'max:160', new EditorialSlug],
            'description' => ['nullable', 'string', 'max:400'],
            'position' => ['required', 'integer', 'min:0', 'max:999'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        DB::transaction(function () use ($category, $data): void {
            $locked = Category::orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $parent = $locked->get($data['parent_id'] ?? 0);
            if (! empty($data['parent_id']) && (! $parent || $parent->parent_id || $parent->is($category) || $locked->contains('parent_id', $category->id) && $category->exists)) {
                throw ValidationException::withMessages(['parent_id' => 'Alege o categorie principală fără a crea o ierarhie circulară.']);
            }

            $category->fill($data)->saveOrFail();
        });

        return redirect()->route('admin.categories.index')->with('message', 'Categorie salvată.');
    }
}
