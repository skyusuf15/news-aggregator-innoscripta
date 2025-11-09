<?php

namespace Tests\Unit\Services;

use App\Contracts\NewsProviderInterface;
use App\Repositories\ArticleRepository;
use App\Services\News\NewsFetcher;
use Mockery;
use Tests\TestCase;

class NewsFetcherTest extends TestCase
{
    public function test_fetch_and_store_calls_all_providers(): void
    {
        $provider1 = Mockery::mock(NewsProviderInterface::class);
        $provider2 = Mockery::mock(NewsProviderInterface::class);
        $repository = Mockery::mock(ArticleRepository::class);

        $provider1->shouldReceive('fetchArticles')
            ->once()
            ->andReturn([
                ['title' => 'Article 1', 'url' => 'https://example.com/1'],
            ]);

        $provider2->shouldReceive('fetchArticles')
            ->once()
            ->andReturn([
                ['title' => 'Article 2', 'url' => 'https://example.com/2'],
            ]);

        $repository->shouldReceive('saveMany')
            ->twice()
            ->with(Mockery::type('array'));

        $fetcher = new NewsFetcher([$provider1, $provider2], $repository);
        $fetcher->fetchAndStore();
    }

    public function test_fetch_and_store_skips_invalid_providers(): void
    {
        $validProvider = Mockery::mock(NewsProviderInterface::class);
        $invalidProvider = new \stdClass(); // Not implementing interface
        $repository = Mockery::mock(ArticleRepository::class);

        $validProvider->shouldReceive('fetchArticles')
            ->once()
            ->andReturn([
                ['title' => 'Article 1', 'url' => 'https://example.com/1'],
            ]);

        $repository->shouldReceive('saveMany')
            ->once()
            ->with(Mockery::type('array'));

        $fetcher = new NewsFetcher([$validProvider, $invalidProvider], $repository);
        $fetcher->fetchAndStore();
    }

    public function test_fetch_and_store_handles_empty_provider_list(): void
    {
        $repository = Mockery::mock(ArticleRepository::class);

        $repository->shouldReceive('saveMany')
            ->never();

        $fetcher = new NewsFetcher([], $repository);
        $fetcher->fetchAndStore();
    }

    public function test_fetch_and_store_handles_provider_returning_empty_array(): void
    {
        $provider = Mockery::mock(NewsProviderInterface::class);
        $repository = Mockery::mock(ArticleRepository::class);

        $provider->shouldReceive('fetchArticles')
            ->once()
            ->andReturn([]);

        $repository->shouldReceive('saveMany')
            ->once()
            ->with([]);

        $fetcher = new NewsFetcher([$provider], $repository);
        $fetcher->fetchAndStore();
    }
}

