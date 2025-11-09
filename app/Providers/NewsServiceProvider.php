<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\NewsProviderInterface;
use App\Services\News\{
    NewsApiService,
    GuardianService,
    NyTimesService,
    NewsFetcher,
};
use App\Repositories\ArticleRepository;

class NewsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(abstract: NewsApiService::class);
        $this->app->bind(abstract: GuardianService::class);
        $this->app->bind(abstract: NyTimesService::class);

        $this->app->tag(
            abstracts: [NewsApiService::class, GuardianService::class, NyTimesService::class],
            tags: NewsProviderInterface::class
        );

        $this->app->bind(abstract: NewsFetcher::class, concrete: function ($app): NewsFetcher {
            return new NewsFetcher(
                providers: $app->tagged(NewsProviderInterface::class),
                articles: $app->make(ArticleRepository::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
