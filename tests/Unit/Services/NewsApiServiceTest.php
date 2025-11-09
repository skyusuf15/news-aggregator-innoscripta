<?php

namespace Tests\Unit\Services;

use App\Services\News\NewsApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NewsApiServiceTest extends TestCase
{
    protected NewsApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.newsapi.key' => 'test-api-key']);
        $this->service = new NewsApiService();
    }

    public function test_fetch_articles_returns_normalized_array(): void
    {
        Http::fake([
            'newsapi.org/v2/top-headlines*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Test Article',
                        'description' => 'Test Description',
                        'content' => 'Test Content',
                        'url' => 'https://example.com/article',
                        'urlToImage' => 'https://example.com/image.jpg',
                        'publishedAt' => '2025-01-01T00:00:00Z',
                        'author' => 'John Doe',
                        'source' => ['name' => 'Test Source'],
                        'category' => 'technology',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Test Article', $result[0]['title']);
        $this->assertEquals('Test Description', $result[0]['description']);
        $this->assertEquals('Test Content', $result[0]['content']);
        $this->assertEquals('https://example.com/article', $result[0]['url']);
        $this->assertEquals('https://example.com/image.jpg', $result[0]['image_url']);
        $this->assertEquals('2025-01-01T00:00:00Z', $result[0]['published_at']);
        $this->assertEquals('John Doe', $result[0]['author']);
        $this->assertEquals('Test Source', $result[0]['source_name']);
        $this->assertEquals('technology', $result[0]['category_name']);
    }

    public function test_fetch_articles_handles_missing_fields(): void
    {
        Http::fake([
            'newsapi.org/v2/top-headlines*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Test Article',
                        'description' => null,
                        'content' => null,
                        'url' => 'https://example.com/article',
                        'urlToImage' => null,
                        'publishedAt' => '2025-01-01T00:00:00Z',
                        'author' => null,
                        'source' => [],
                        'category' => null,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('NewsAPI', $result[0]['source_name']);
        $this->assertNull($result[0]['category_name']);
    }

    public function test_fetch_articles_handles_api_failure(): void
    {
        Http::fake([
            'newsapi.org/v2/top-headlines*' => Http::response([], 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('NewsAPI error', \Mockery::type('array'));

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_articles_handles_exception(): void
    {
        Http::fake([
            'newsapi.org/v2/top-headlines*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('NewsAPI error', \Mockery::type('array'));

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_articles_handles_empty_response(): void
    {
        Http::fake([
            'newsapi.org/v2/top-headlines*' => Http::response([
                'articles' => [],
            ], 200),
        ]);

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}

