<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\BlogSettings;
use App\Models\Doktor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    // Public endpoints
    public function index(Request $request)
    {
        $query = BlogPost::with(['doktor:id,ime,prezime,specijalnost,slug,slika_profila', 'categories:id,naziv,slug'])
            ->published()
            ->orderBy('published_at', 'desc');

        if ($request->category) {
            $query->whereHas('categories', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->author) {
            $query->whereHas('doktor', fn($q) => $q->where('slug', $request->author));
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('naslov', 'ilike', "%{$search}%")
                ->orWhere('sadrzaj', 'ilike', "%{$search}%"));
        }

        return response()->json($query->paginate($request->per_page ?? 12));
    }

    public function show($slug)
    {
        $post = BlogPost::with(['doktor:id,ime,prezime,specijalnost,slug,slika_profila,opis', 'categories'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        $post->increment('views');

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->whereHas('categories', fn($q) => $q->whereIn('id', $post->categories->pluck('id')))
            ->limit(3)
            ->get();

        return response()->json([
            'post' => $post,
            'related' => $related
        ]);
    }

    public function homepage()
    {
        $settings = BlogSettings::get();

        if ($settings->homepage_display === 'featured' && $settings->featured_post_ids) {
            $posts = BlogPost::with(['doktor:id,ime,prezime,slug,slika_profila'])
                ->published()
                ->whereIn('id', $settings->featured_post_ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $settings->featured_post_ids) . ')')
                ->get();
        } else {
            $posts = BlogPost::with(['doktor:id,ime,prezime,slug,slika_profila'])
                ->published()
                ->orderBy('published_at', 'desc')
                ->limit($settings->homepage_count)
                ->get();
        }

        return response()->json($posts);
    }

    public function categories()
    {
        return response()->json(
            BlogCategory::withCount(['posts' => fn($q) => $q->published()])
                ->orderBy('sort_order', 'asc')
                ->orderBy('naziv', 'asc')
                ->get()
        );
    }

    public function authors()
    {
        $authors = Doktor::whereHas('blogPosts', function($q) {
            $q->published();
        })
        ->withCount(['blogPosts' => fn($q) => $q->published()])
        ->select('id', 'ime', 'prezime', 'slug')
        ->orderBy('ime')
        ->get()
        ->map(function($doktor) {
            return [
                'id' => $doktor->id,
                'ime' => $doktor->ime,
                'prezime' => $doktor->prezime,
                'slug' => $doktor->slug,
                'posts_count' => $doktor->blog_posts_count
            ];
        });

        return response()->json($authors);
    }

    public function doctorPosts($doctorSlug)
    {
        $doktor = Doktor::where('slug', $doctorSlug)->firstOrFail();

        $posts = BlogPost::with('categories')
            ->where('doktor_id', $doktor->id)
            ->published()
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json($posts);
    }

    // Doctor endpoints
    public function myPosts(Request $request)
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json([]);
        }

        $settings = BlogSettings::get();
        if (!$settings->doctors_can_write) {
            return response()->json(['error' => 'Pisanje članaka nije omogućeno za doktore'], 403);
        }

        return response()->json(
            BlogPost::with('categories:id,naziv,slug')
                ->where('doktor_id', $doktor->id)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function storeDoctor(Request $request)
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json(['error' => 'Doktor profil nije pronađen'], 404);
        }

        $settings = BlogSettings::get();
        if (!$settings->doctors_can_write) {
            return response()->json(['error' => 'Pisanje članaka nije omogućeno'], 403);
        }

        $validated = $request->validate([
            'naslov' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
            'sadrzaj' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:blog_categories,id',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'reading_time' => 'nullable|integer|min:1|max:120',
        ]);

        if (isset($validated['reading_time'])) {
            $validated['reading_time_manual'] = $validated['reading_time'];
            unset($validated['reading_time']);
        }

        $status = $validated['status'] ?? 'published';

        $post = BlogPost::create([
            ...$validated,
            'doktor_id' => $doktor->id,
            'autor_id' => $user->id,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        if (!empty($validated['category_ids'])) {
            $post->categories()->sync($validated['category_ids']);
        }

        return response()->json($post->load('categories'), 201);
    }

    public function updateDoctor(Request $request, $id)
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->first();
        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen'], 404);
        }

        $post = BlogPost::where('id', $id)->where('doktor_id', $doktor->id)->first();
        if (!$post) {
            return response()->json(['message' => 'Članak nije pronađen'], 404);
        }

        $validated = $request->validate([
            'naslov' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('blog_posts', 'slug')->ignore($post->id),
            ],
            'sadrzaj' => 'sometimes|string',
            'excerpt' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:blog_categories,id',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'reading_time' => 'nullable|integer|min:1|max:120',
        ]);

        if (isset($validated['reading_time'])) {
            $validated['reading_time_manual'] = $validated['reading_time'];
            unset($validated['reading_time']);
        }

        if (isset($validated['status']) && $validated['status'] === 'published' && !$post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        if (isset($validated['category_ids'])) {
            $post->categories()->sync($validated['category_ids']);
        }

        return response()->json($post->load('categories'));
    }

    public function destroyDoctor($id)
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->first();
        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen'], 404);
        }

        $post = BlogPost::where('id', $id)->where('doktor_id', $doktor->id)->first();
        if (!$post) {
            return response()->json(['message' => 'Članak nije pronađen'], 404);
        }
        $post->delete();

        return response()->json(['message' => 'Članak obrisan']);
    }

    // Admin endpoints
    public function adminIndex(Request $request)
    {
        return response()->json(
            BlogPost::with(['doktor:id,ime,prezime', 'autor:id,name', 'categories'])
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        );
    }

    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'naslov' => 'required|string|max:255',
            'sadrzaj' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'featured' => 'boolean',
            'category_ids' => 'nullable|array',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'reading_time' => 'nullable|integer|min:1|max:120',
        ]);

        // Map reading_time to reading_time_manual
        if (isset($validated['reading_time'])) {
            $validated['reading_time_manual'] = $validated['reading_time'];
            unset($validated['reading_time']);
        }

        $post = BlogPost::create([
            ...$validated,
            'autor_id' => auth()->id(),
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        if (!empty($validated['category_ids'])) {
            $post->categories()->sync($validated['category_ids']);
        }

        return response()->json($post->load('categories'), 201);
    }

    public function adminUpdate(Request $request, $id)
    {
        $post = BlogPost::findOrFail($id);

        $validated = $request->validate([
            'naslov' => 'sometimes|string|max:255',
            'sadrzaj' => 'sometimes|string',
            'excerpt' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'featured' => 'boolean',
            'category_ids' => 'nullable|array',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'reading_time' => 'nullable|integer|min:1|max:120',
        ]);

        // Map reading_time to reading_time_manual
        if (isset($validated['reading_time'])) {
            $validated['reading_time_manual'] = $validated['reading_time'];
            unset($validated['reading_time']);
        }

        if (isset($validated['status']) && $validated['status'] === 'published' && !$post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        if (isset($validated['category_ids'])) {
            $post->categories()->sync($validated['category_ids']);
        }

        return response()->json($post->load('categories'));
    }

    public function adminDestroy($id)
    {
        BlogPost::findOrFail($id)->delete();
        return response()->json(['message' => 'Članak obrisan']);
    }

    // Categories admin
    public function adminStoreCategory(Request $request)
    {
        $validated = $request->validate([
            'naziv' => 'required|string|max:100',
            'opis' => 'nullable|string',
        ]);

        return response()->json(BlogCategory::create($validated), 201);
    }

    public function adminUpdateCategory(Request $request, $id)
    {
        $cat = BlogCategory::findOrFail($id);
        $cat->update($request->validate([
            'naziv' => 'sometimes|string|max:100',
            'opis' => 'nullable|string',
            'sort_order' => 'sometimes|integer|min:0',
        ]));
        return response()->json($cat);
    }

    public function adminUpdateCategoriesOrder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:blog_categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->categories as $categoryData) {
            BlogCategory::where('id', $categoryData['id'])
                ->update(['sort_order' => $categoryData['sort_order']]);
        }

        return response()->json(['message' => 'Redoslijed kategorija ažuriran']);
    }

    public function adminDestroyCategory($id)
    {
        BlogCategory::findOrFail($id)->delete();
        return response()->json(['message' => 'Kategorija obrisana']);
    }

    // Settings
    public function getSettings()
    {
        return response()->json(BlogSettings::get());
    }

    public function updateSettings(Request $request)
    {
        $settings = BlogSettings::get();

        $validated = $request->validate([
            'doctors_can_write' => 'boolean',
            'homepage_display' => 'in:featured,latest',
            'homepage_count' => 'integer|min:1|max:12',
            'featured_post_ids' => 'nullable|array',
        ]);

        $settings->update($validated);
        return response()->json($settings);
    }

    public function canDoctorsWrite()
    {
        return response()->json(['can_write' => BlogSettings::get()->doctors_can_write]);
    }
}
