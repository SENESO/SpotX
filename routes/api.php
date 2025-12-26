<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MentionController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\RepostController;
use App\Http\Controllers\Api\ReplyController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserPostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::get('/users/{id}/posts', [UserPostController::class, 'index']);
    Route::get('/users/{id}/media', [UserPostController::class, 'media']);
    Route::get('/users/{id}/likes', [UserPostController::class, 'likes']);
    Route::get('/users/{id}/followers', [UserController::class, 'followers']);
    Route::get('/users/{id}/following', [UserController::class, 'following']);
    
    Route::prefix('/posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/', [PostController::class, 'store'])->middleware('throttle:50,1');
        Route::get('/{id}', [PostController::class, 'show']);
        Route::patch('/{id}', [PostController::class, 'update']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
        
        Route::post('/{id}/like', [LikeController::class, 'store']);
        Route::delete('/{id}/like', [LikeController::class, 'destroy']);
        Route::get('/{id}/likes', [LikeController::class, 'index']);
        
        Route::post('/{id}/repost', [RepostController::class, 'store']);
        Route::delete('/{id}/repost', [RepostController::class, 'destroy']);
        Route::get('/{id}/reposts', [RepostController::class, 'index']);
        
        Route::post('/{id}/quote', [QuoteController::class, 'store']);
        Route::delete('/{id}/quote', [QuoteController::class, 'destroy']);
        Route::get('/{id}/quotes', [QuoteController::class, 'index']);
        
        Route::get('/{id}/replies', [ReplyController::class, 'index']);
        Route::post('/{id}/replies', [ReplyController::class, 'store']);
    });
    
    Route::prefix('/replies')->group(function () {
        Route::get('/{id}', [ReplyController::class, 'show']);
        Route::patch('/{id}', [ReplyController::class, 'update']);
        Route::delete('/{id}', [ReplyController::class, 'destroy']);
    });
    
    Route::prefix('/feed')->group(function () {
        Route::get('/', [FeedController::class, 'index']);
        Route::get('/chronological', [FeedController::class, 'chronological']);
        Route::get('/personalized', [FeedController::class, 'personalized']);
    });
    
    Route::prefix('/media')->group(function () {
        Route::post('/upload', [MediaController::class, 'upload'])->middleware('throttle:10,1');
        Route::post('/upload-multiple', [MediaController::class, 'uploadForPost'])->middleware('throttle:10,1');
        Route::delete('/{id}', [MediaController::class, 'destroy']);
    });
    
    Route::prefix('/search')->group(function () {
        Route::get('/', [SearchController::class, 'index']);
        Route::get('/posts', [SearchController::class, 'posts']);
        Route::get('/users', [SearchController::class, 'users']);
        Route::get('/hashtags', [SearchController::class, 'hashtags']);
    });
    
    Route::prefix('/mentions')->group(function () {
        Route::get('/suggestions', [MentionController::class, 'suggestions']);
        Route::get('/', [MentionController::class, 'index']);
    });
});
