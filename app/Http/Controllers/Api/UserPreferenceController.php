<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserPreferredAuthor;
use App\Http\Controllers\Controller;

class UserPreferenceController extends Controller
{
    /**
     * Get user preferences.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return response()->json(['message' => 'User not found. Please authenticate or provide user_id.'], 401);
        }

        return response()->json([
            'sources' => $user->preferredSources->pluck('id'),
            'categories' => $user->preferredCategories->pluck('id'),
            'authors' => $user->preferredAuthors->pluck('author_name'),
        ]);
    }

    /**
     * Get the user from request (authenticated or by user_id).
     */
    protected function getUser(Request $request): ?User
    {
        // Try authenticated user first
        if ($request->user()) {
            return $request->user();
        }

        // Fallback to user_id parameter
        if ($userId = $request->input('user_id')) {
            return User::find($userId);
        }

        return null;
    }

    /**
     * Update user preferred sources.
     */
    public function updateSources(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return response()->json(['message' => 'User not found. Please authenticate or provide user_id.'], 401);
        }

        $request->validate([
            'source_ids' => 'required|array',
            'source_ids.*' => 'exists:sources,id',
        ]);

        $user->preferredSources()->sync($request->input('source_ids'));

        return response()->json([
            'message' => 'Preferred sources updated successfully',
            'sources' => $user->preferredSources->pluck('id'),
        ]);
    }

    /**
     * Update user preferred categories.
     */
    public function updateCategories(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return response()->json(['message' => 'User not found. Please authenticate or provide user_id.'], 401);
        }

        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $user->preferredCategories()->sync($request->input('category_ids'));

        return response()->json([
            'message' => 'Preferred categories updated successfully',
            'categories' => $user->preferredCategories->pluck('id'),
        ]);
    }

    /**
     * Update user preferred authors.
     */
    public function updateAuthors(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return response()->json(['message' => 'User not found. Please authenticate or provide user_id.'], 401);
        }

        $request->validate([
            'author_names' => 'required|array',
            'author_names.*' => 'string|max:255',
        ]);

        // Remove existing preferences
        $user->preferredAuthors()->delete();

        // Add new preferences
        foreach ($request->input('author_names') as $authorName) {
            UserPreferredAuthor::create([
                'user_id' => $user->id,
                'author_name' => $authorName,
            ]);
        }

        return response()->json([
            'message' => 'Preferred authors updated successfully',
            'authors' => $user->preferredAuthors->pluck('author_name'),
        ]);
    }

    /**
     * Clear all user preferences.
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return response()->json(['message' => 'User not found. Please authenticate or provide user_id.'], 401);
        }

        $user->preferredSources()->detach();
        $user->preferredCategories()->detach();
        $user->preferredAuthors()->delete();

        return response()->json([
            'message' => 'All preferences cleared successfully',
        ]);
    }
}
