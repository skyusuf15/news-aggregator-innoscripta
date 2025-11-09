<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Repositories\ArticleRepository;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function __construct(private ArticleService $articleService, private ArticleRepository $articleRepository)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        // can add validation here

        $query = $this->articleRepository->initArticle();

        $query = $this->articleService->applyUserPreferences(query: $query, request: $request);
        $query = $this->articleService->applyFilters(query: $query, request: $request);
        $query = $this->articleService->applySorting(query: $query, request: $request);

        return ArticleResource::collection(
            $this->articleRepository->paginate(
                $query,
                $request->input(key: 'limit', default: 10)
            )
        );
    }

    /**
     * Fetch a single article by ID
     */
    public function show($id): ArticleResource
    {
        $query = $this->articleRepository->initArticle();
        $article = $query->findOrFail(id: $id);
        return ArticleResource::make($article);
    }
}
