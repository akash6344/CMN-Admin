<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $blogs = Blog::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('pages.blogs.index', [
            'blogs' => $blogs,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('pages.blogs.form', [
            'blog' => new Blog(['status' => 'Active']),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedBlog($request);
        $validated['image_path'] = $this->storeImage($request);

        Blog::query()->create($validated);

        return redirect()->route('blogs.index')->with('success', 'Blog created successfully.');
    }

    public function edit(Blog $blog): View
    {
        return view('pages.blogs.form', [
            'blog' => $blog,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $validated = $this->validatedBlog($request, requireImage: false);

        if ($request->hasFile('image')) {
            if ($blog->image_path) {
                Storage::disk('public')->delete($blog->image_path);
            }
            $validated['image_path'] = $this->storeImage($request);
        }

        $blog->update($validated);

        return redirect()->route('blogs.index')->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        if ($blog->image_path) {
            Storage::disk('public')->delete($blog->image_path);
        }

        $blog->delete();

        return redirect()->route('blogs.index')->with('success', 'Blog deleted successfully.');
    }

    public function uploadEditorImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]);

        $path = $validated['image']->store('blogs/content', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedBlog(Request $request, bool $requireImage = false): array
    {
        $imageRules = $requireImage
            ? ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']
            : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'image' => $imageRules,
        ]);

        unset($validated['image']);

        return $validated;
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('blogs', 'public');
    }
}
