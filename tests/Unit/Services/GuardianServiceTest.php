<?php

namespace Tests\Unit\Services;

use App\Services\News\GuardianService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GuardianServiceTest extends TestCase
{
    protected GuardianService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.guardian.key' => 'test-api-key']);
        $this->service = new GuardianService();
    }

    public function test_fetch_articles_returns_normalized_array(): void
    {
        Http::fake([
            'content.guardianapis.com/search*' => Http::response([
                'response' => [
                    'results' => [
                        [
                            'webTitle' => 'Guardian Article',
                            'webUrl' => 'https://guardian.com/article',
                            'webPublicationDate' => '2025-01-01T00:00:00Z',
                            'sectionName' => 'Technology',
                            'sectionId' => 'technology',
                            'fields' => [
                                'headline' => 'Guardian Headline',
                                'body' => 'Guardian Body Content',
                                'thumbnail' => 'https://guardian.com/thumb.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Guardian Article', $result[0]['title']);
        $this->assertEquals('Guardian Headline', $result[0]['description']);
        $this->assertEquals('Guardian Body Content', $result[0]['content']);
        $this->assertEquals('https://guardian.com/article', $result[0]['url']);
        $this->assertEquals('https://guardian.com/thumb.jpg', $result[0]['image_url']);
        $this->assertEquals('2025-01-01T00:00:00Z', $result[0]['published_at']);
        $this->assertNull($result[0]['author']);
        $this->assertEquals('The Guardian', $result[0]['source_name']);
        $this->assertEquals('Technology', $result[0]['category_name']);
    }

    public function test_fetch_articles_uses_section_id_when_section_name_missing(): void
    {
        Http::fake([
            'content.guardianapis.com/search*' => Http::response([
                'response' => [
                    'results' => [
                        [
                            'webTitle' => 'Guardian Article',
                            'webUrl' => 'https://guardian.com/article',
                            'webPublicationDate' => '2025-01-01T00:00:00Z',
                            'sectionId' => 'technology',
                            'fields' => [
                                'headline' => 'Guardian Headline',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchArticles();

        $this->assertEquals('technology', $result[0]['category_name']);
    }

    public function test_fetch_articles_handles_missing_fields(): void
    {
        Http::fake([
            'content.guardianapis.com/search*' => Http::response([
                'response' => [
                    'results' => [
                        [
                            'webTitle' => 'Guardian Article',
                            'webUrl' => 'https://guardian.com/article',
                            'webPublicationDate' => '2025-01-01T00:00:00Z',
                            'fields' => [],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]['description']);
        $this->assertNull($result[0]['content']);
        $this->assertNull($result[0]['image_url']);
        $this->assertNull($result[0]['category_name']);
    }

    public function test_fetch_articles_handles_api_failure(): void
    {
        Http::fake([
            'content.guardianapis.com/search*' => Http::response([], 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Guardian API Error:', \Mockery::type('array'));

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_articles_handles_exception(): void
    {
        Http::fake([
            'content.guardianapis.com/search*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Guardian API Error:', \Mockery::type('array'));

        $result = $this->service->fetchArticles();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}

