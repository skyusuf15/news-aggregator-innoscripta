<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ArticleService
{
    public function applyUserPreferences(Builder $query, Request $request): Builder
    {
        $user = $request->user() ?: ($request->input('user_id') ? \App\Models\User::find($request->input('user_id')) : null);

        if (!$user || !$request->boolean('use_preferences')) {
            return $query;
        }

        if ($sourceIds = $user->preferredSources->pluck('id')->filter()->toArray()) {
            $query->whereIn('source_id', $sourceIds);
        }

        if ($categoryIds = $user->preferredCategories->pluck('id')->filter()->toArray()) {
            $query->whereIn('category_id', $categoryIds);
        }

        if ($authors = $user->preferredAuthors->pluck('author_name')->filter()->toArray()) {
            $query->where(fn($q) => collect($authors)->each(fn($author) => $q->orWhere('author', 'like', "%{$author}%")));
        }

        return $query;
    }

    public function applyFilters(Builder $query, Request $request): Builder
    {
        if ($search = $request->input('search')) {
            $query->where(fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%"));
        }

        if ($date = $request->input('date')) {
            $query->whereDate('published_at', $date);
        }

        if ($request->has('author')) {
            $authors = (array) $request->input('author');
            $query->where(fn($q) => collect($authors)->each(fn($author) => $q->orWhere('author', 'like', "%{$author}%")));
        }

        if ($request->has('source')) {
            $sources = (array) $request->input('source');
            $query->whereHas('source', fn($q) => $q->where(
                fn($sub) =>
                collect($sources)->each(fn($source) => $sub->orWhere('slug', $source)->orWhere('name', 'like', "%{$source}%"))
            ));
        }

        if ($request->has('category')) {
            $categories = (array) $request->input('category');
            $query->whereHas('category', fn($q) => $q->where(
                fn($sub) =>
                collect($categories)->each(fn($category) => $sub->orWhere('slug', $category)->orWhere('name', 'like', "%{$category}%"))
            ));
        }

        return $query;
    }

    public function applySorting(Builder $query, Request $request): Builder
    {
        $query->orderBy('published_at', $request->has('sort_by') ? $request->input('sort_order', 'desc') : 'desc');
        return $query;
    }
}