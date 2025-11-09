<?php

namespace App\Contracts;

interface NewsProviderInterface
{
    /**
     * Fetch and normalize articles from a source.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchArticles(): array;
}
