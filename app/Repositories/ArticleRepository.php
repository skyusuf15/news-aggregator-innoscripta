<?php

namespace App\Repositories;

use App\Models\Article;
use App\Models\Source;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ArticleRepository
{
    public function __construct(
        private Article $article,
        private Source $source,
        private Category $category
    ) {}

    public function save(array $data): Article
    {
        $source = $this->source->firstOrCreate([
            'name' => $data['source_name'],
            'slug' => Str::slug($data['source_name']),
        ]);

        $category = null;
        if (!empty($data['category_name'])) {
            $category = $this->category->firstOrCreate([
                'name' => strtolower($data['category_name']),
                'slug' => Str::slug($data['category_name']),
            ]);
        }

        return $this->article->updateOrCreate(
            ['url' => $data['url']],
            [
                'title' => $data['title'],
                'description' => $data['description'],
                'content' => $data['content'],
                'image_url' => $data['image_url'],
                'published_at' => $data['published_at'],
                'author' => $data['author'],
                'source_id' => $source->id,
                'category_id' => $category?->id,
            ]
        );
    }

    public function saveMany(iterable $articles): void
    {
        foreach ($articles as $article) {
            $this->save($article);
        }
    }

    public function initArticle(): Builder
    {
        return $this->article->with(relations: ['source', 'category']);
    }

    public function paginate(Builder $query, $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $query->paginate($perPage);
    }

}
