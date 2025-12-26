<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'bio' => $user->bio,
                'profile_image_url' => $user->profile_image_url,
                'header_image_url' => $user->header_image_url,
                'followers_count' => $user->followers_count,
                'following_count' => $user->following_count,
                'posts_count' => $user->posts_count,
                'is_verified' => $user->is_verified,
                'is_private' => $user->is_private,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'profile_image_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'header_image_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_private' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'bio' => $user->bio,
                'profile_image_url' => $user->profile_image_url,
                'header_image_url' => $user->header_image_url,
                'followers_count' => $user->followers_count,
                'following_count' => $user->following_count,
                'posts_count' => $user->posts_count,
                'is_verified' => $user->is_verified,
                'is_private' => $user->is_private,
            ],
        ]);
    }

    public function posts($id)
    {
        $user = User::findOrFail($id);
        $posts = $user->posts()->with('user')->recent()->paginate(20);

        return response()->json([
            'posts' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function followers($id)
    {
        $user = User::findOrFail($id);
        $followers = $user->followers()->paginate(20);

        return response()->json([
            'followers' => $followers->items(),
            'pagination' => [
                'current_page' => $followers->currentPage(),
                'per_page' => $followers->perPage(),
                'total' => $followers->total(),
                'last_page' => $followers->lastPage(),
            ],
        ]);
    }

    public function following($id)
    {
        $user = User::findOrFail($id);
        $following = $user->following()->paginate(20);

        return response()->json([
            'following' => $following->items(),
            'pagination' => [
                'current_page' => $following->currentPage(),
                'per_page' => $following->perPage(),
                'total' => $following->total(),
                'last_page' => $following->lastPage(),
            ],
        ]);
    }
}
