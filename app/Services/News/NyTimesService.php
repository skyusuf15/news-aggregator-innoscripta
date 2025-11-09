<?php

namespace App\Services\News;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Contracts\NewsProviderInterface;
use Log;

class NyTimesService implements NewsProviderInterface
{
    protected string $baseUrl = 'https://api.nytimes.com/svc/topstories/v2';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.nytimes.key');
    }

    public function fetchArticles(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/home.json", [
                'api-key' => $this->apiKey,
            ]);

            if ($response->failed()) {
                throw new Exception('Failed to fetch articles from New York Times: ' . $response->body(), $response->status());
            }

            return collect($response->json('results'))->map(fn($item) => [
                'title' => $item['title'],
                'description' => $item['abstract'],
                'content' => $item['abstract'],
                'url' => $item['url'],
                'image_url' => $item['multimedia'][0]['url'] ?? null,
                'published_at' => $item['published_date'],
                'author' => $item['byline'] ?? null,
                'source_name' => 'New York Times',
                'category_name' => $item['section'] ?? null,
            ])->toArray();
        } catch (\Throwable $th) {
            Log::error('NyTimesService:fetchArticles', [
                'error' => $th->getMessage(),
            ]);
            return [];
        }
    }
}
