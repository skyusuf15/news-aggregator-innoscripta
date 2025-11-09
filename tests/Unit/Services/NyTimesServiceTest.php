<?php

namespace Tests\Unit\Services;

use App\Services\News\NyTimesService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NyTimesServiceTest extends TestCase
{
    protected NyTimesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.nytimes.key' => 'test-api-key']);
        $this->service = new NyTimesService();
    }

    public function test_fetch_articles_returns_normalized_array(): void
    {
        Http::fake([
            'api.nytimes.com/svc/topstories/v2/home.json*' => Http::response([
                'results' => [
                    [
                        'title' => 'NYT Article',
                        'abstract' => 'NYT Abstract',
                        'url' => 'https://nytimes.com/article',
                        'published_date' => '2025-01-01',
                        'byline' => 'By Jane Smith',
                        'section' => 'Technology',
                        'multimedia' => [
                            ['url' => 'https://nytimes.com/image.jpg'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('NYT Article', $result[0]['title']);
        $this->assertEquals('NYT Abstract', $result[0]['description']);
        $this->assertEquals('NYT Abstract', $result[0]['content']);
        $this->assertEquals('https://nytimes.com/article', $result[0]['url']);
        $this->assertEquals('https://nytimes.com/image.jpg', $result[0]['image_url']);
        $this->assertEquals('2025-01-01', $result[0]['published_at']);
        $this->assertEquals('By Jane Smith', $result[0]['author']);
        $this->assertEquals('New York Times', $result[0]['source_name']);
        $this->assertEquals('Technology', $result[0]['category_name']);
    }

    public function test_fetch_articles_handles_missing_fields(): void
    {
        Http::fake([
            'api.nytimes.com/svc/topstories/v2/home.json*' => Http::response([
                'results' => [
                    [
                        'title' => 'NYT Article',
                        'abstract' => 'NYT Abstract',
                        'url' => 'https://nytimes.com/article',
                        'published_date' => '2025-01-01',
                        'byline' => null,
                        'section' => null,
                        'multimedia' => [],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]['image_url']);
        $this->assertNull($result[0]['author']);
        $this->assertNull($result[0]['category_name']);
    }

    public function test_fetch_articles_handles_api_failure(): void
    {
        Http::fake([
            'api.nytimes.com/svc/topstories/v2/home.json*' => Http::response([], 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('NyTimesService:fetchArticles', \Mockery::type('array'));

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_articles_handles_exception(): void
    {
        Http::fake([
            'api.nytimes.com/svc/topstories/v2/home.json*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('NyTimesService:fetchArticles', \Mockery::type('array'));

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}

