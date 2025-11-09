<?php

namespace Tests\Unit\Repositories;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Repositories\ArticleRepository;
use Mockery;
use Tests\TestCase;

class ArticleRepositoryTest extends TestCase
{
    protected ArticleRepository $repository;
    protected $mockArticle;
    protected $mockSource;
    protected $mockCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockArticle = Mockery::mock(Article::class);
        $this->mockSource = Mockery::mock(Source::class);
        $this->mockCategory = Mockery::mock(Category::class);
        $this->repository = new ArticleRepository($this->mockArticle, $this->mockSource, $this->mockCategory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_save_creates_source_and_article(): void
    {
        $data = [
            'title' => 'Test Article',
            'description' => 'Test Description',
            'content' => 'Test Content',
            'url' => 'https://example.com/article',
            'image_url' => 'https://example.com/image.jpg',
            'published_at' => '2025-01-01 00:00:00',
            'author' => 'John Doe',
            'source_name' => 'Test Source',
        ];

        $mockSource = Mockery::mock();
        $mockSource->id = 1;
        $mockArticle = Mockery::mock(Article::class);

        $this->mockSource->shouldReceive('firstOrCreate')
            ->once()
            ->andReturn($mockSource);

        $this->mockArticle->shouldReceive('updateOrCreate')
            ->once()
            ->andReturn($mockArticle);

        $result = $this->repository->save($data);
        $this->assertSame($mockArticle, $result);
    }

    public function test_save_with_category(): void
    {
        $data = [
            'title' => 'Test Article',
            'description' => 'Test Description',
            'content' => 'Test Content',
            'url' => 'https://example.com/article',
            'image_url' => null,
            'published_at' => '2025-01-01 00:00:00',
            'author' => null,
            'source_name' => 'Test Source',
            'category_name' => 'Technology',
        ];

        $mockSource = Mockery::mock();
        $mockSource->id = 1;
        $mockCategory = Mockery::mock();
        $mockCategory->id = 2;
        $mockArticle = Mockery::mock(Article::class);

        $this->mockSource->shouldReceive('firstOrCreate')->andReturn($mockSource);
        $this->mockCategory->shouldReceive('firstOrCreate')
            ->once()
            ->andReturn($mockCategory);
        $this->mockArticle->shouldReceive('updateOrCreate')->andReturn($mockArticle);

        $result = $this->repository->save($data);
        $this->assertSame($mockArticle, $result);
    }

    public function test_save_many_calls_save_for_each_article(): void
    {
        $articles = [
            [
                'title' => 'Article 1',
                'description' => 'Desc 1',
                'content' => 'Content 1',
                'url' => 'url1',
                'image_url' => null,
                'published_at' => '2025-01-01',
                'author' => null,
                'source_name' => 'Source 1'
            ],
            [
                'title' => 'Article 2',
                'description' => 'Desc 2',
                'content' => 'Content 2',
                'url' => 'url2',
                'image_url' => null,
                'published_at' => '2025-01-01',
                'author' => null,
                'source_name' => 'Source 2'
            ],
        ];

        $mockSource = Mockery::mock();
        $mockSource->id = 1;
        $mockArticle = Mockery::mock(Article::class);

        $this->mockSource->shouldReceive('firstOrCreate')->twice()->andReturn($mockSource);
        $this->mockArticle->shouldReceive('updateOrCreate')->twice()->andReturn($mockArticle);

        $this->repository->saveMany($articles);
        $this->assertTrue(true);
    }
}