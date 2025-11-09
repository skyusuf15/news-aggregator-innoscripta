<?php

namespace App\Services\News;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Contracts\NewsProviderInterface;
use Log;

class NewsApiService implements NewsProviderInterface
{
    protected string $baseUrl = 'https://newsapi.org/v2';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.newsapi.key');
    }

    public function fetchArticles(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/top-headlines", [
                'language' => 'en',
                'pageSize' => 10,
                'apiKey' => $this->apiKey,
            ]);

            if ($response->failed()) {
                throw new Exception('Failed to fetch articles from NewsAPI: ' . $response->body(), $response->status());
            }

            return collect($response->json('articles'))->map(fn($item) => [
                'title' => $item['title'],
                'description' => $item['description'],
                'content' => $item['content'],
                'url' => $item['url'],
                'image_url' => $item['urlToImage'],
                'published_at' => $item['publishedAt'],
                'author' => $item['author'],
                'source_name' => $item['source']['name'] ?? 'NewsAPI',
                'category_name' => $item['category'] ?? null,
            ])->toArray();
        } catch (\Throwable $th) {
            Log::error('NewsAPI error', ['error' => $th->getMessage()]);
            return [];
        }

    }
}
