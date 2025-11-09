<?php

namespace App\Services\News;

use App\Repositories\ArticleRepository;
use App\Contracts\NewsProviderInterface;


class NewsFetcher
{
    /**
     * @param iterable<NewsProviderInterface> $providers
     */
    public function __construct(
        protected iterable $providers,
        protected ArticleRepository $articles
    ) {
    }

    public function fetchAndStore(): void
    {
        foreach ($this->providers as $provider) {

            if (! $provider instanceof NewsProviderInterface) {
                continue; // Skip invalid providers
            }

            $articles = $provider->fetchArticles();
            $this->articles->saveMany(articles: $articles);
        }
    }
}
