<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProfileImageService;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', [
            'categories' => Category::withCount('products')->orderBy('name')->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = app(ProfileImageService::class)->replace($request->file('image'), null, 'category');
        }

        $data['slug'] = Str::slug($data['name']);
        $base = $data['slug'];
        $i = 1;
        while (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $base.'-'.$i++;
        }

        Category::create($data);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = app(ProfileImageService::class)->replace($request->file('image'), $category->image, 'category');
        }

        $category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        try {
            $category->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Cannot delete: category has products.');
        }

        return back()->with('success', 'Category removed.');
    }
}
