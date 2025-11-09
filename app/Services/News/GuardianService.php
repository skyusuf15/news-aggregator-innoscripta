<?php

namespace App\Services\News;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Contracts\NewsProviderInterface;
use Log;

class GuardianService implements NewsProviderInterface
{
    protected string $baseUrl = 'https://content.guardianapis.com';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.guardian.key');
    }

    public function fetchArticles(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/search", [
                'api-key' => $this->apiKey,
                'show-fields' => 'headline,body,thumbnail',
                'page-size' => 10,
            ]);

            if ($response->failed()) {
                throw new Exception('Failed to fetch articles from Guardian API: ' . $response->body(), $response->status());
            }

            return collect($response->json('response.results'))->map(fn($item) => [
                'title' => $item['webTitle'],
                'description' => $item['fields']['headline'] ?? null,
                'content' => $item['fields']['body'] ?? null,
                'url' => $item['webUrl'],
                'image_url' => $item['fields']['thumbnail'] ?? null,
                'published_at' => $item['webPublicationDate'],
                'author' => null,
                'source_name' => 'The Guardian',
                'category_name' => $item['sectionName'] ?? $item['sectionId'] ?? null,
            ])->toArray();

        } catch (\Throwable $th) {
            Log::error('Guardian API Error:', ['error' => $th->getMessage()]);
            return [];
        }

    }
}
