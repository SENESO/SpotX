<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MentionController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\RepostController;
use App\Http\Controllers\Api\ReplyController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserPostController;
use App\Http\Controllers\Api\SavedPostController;
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
    Route::get('/users/{id}/saved-posts', [SavedPostController::class, 'index']);
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
        
        Route::post('/{id}/save', [SavedPostController::class, 'save']);
        Route::delete('/{id}/save', [SavedPostController::class, 'unsave']);
        Route::get('/{id}/saved', [SavedPostController::class, 'check']);
        
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
    
    Route::prefix('/notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/mark-multiple-read', [NotificationController::class, 'markMultipleAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
        Route::delete('/delete-multiple', [NotificationController::class, 'destroyMultiple']);
    });
    
    Route::prefix('/notification-settings')->group(function () {
        Route::get('/', [NotificationSettingController::class, 'index']);
        Route::patch('/', [NotificationSettingController::class, 'update']);
        Route::patch('/push', [NotificationSettingController::class, 'updatePushSettings']);
        Route::patch('/quiet-hours', [NotificationSettingController::class, 'updateQuietHours']);
        Route::patch('/email', [NotificationSettingController::class, 'updateEmailSettings']);
    });
    
    Route::prefix('/conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);
        Route::post('/', [ConversationController::class, 'store']);
        Route::get('/search', [ConversationController::class, 'search']);
        Route::get('/{id}', [ConversationController::class, 'show']);
        Route::post('/{id}/read', [ConversationController::class, 'markAsRead']);
        Route::post('/{id}/leave', [ConversationController::class, 'leave']);
        Route::delete('/{id}', [ConversationController::class, 'destroy']);
        
        Route::prefix('/{conversationId}/messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'store']);
            Route::get('/{messageId}', [MessageController::class, 'show']);
            Route::post('/{messageId}/read', [MessageController::class, 'markAsRead']);
            Route::delete('/{messageId}', [MessageController::class, 'destroy']);
        });
    });
});
