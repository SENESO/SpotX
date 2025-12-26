<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaUploadRequest;
use App\Http\Resources\MediaResource;
use App\Models\PostMedia;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function upload(MediaUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');
        
        $media = $this->mediaService->upload($file);

        return response()->json([
            'message' => 'Media uploaded successfully',
            'media' => new MediaResource($media),
        ], 201);
    }

    public function uploadForPost(Request $request): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,mp4,webm', 'max:102400'],
        ]);

        $mediaItems = $this->mediaService->uploadMultiple($request->file('files'));

        return response()->json([
            'message' => 'Media uploaded successfully',
            'media' => MediaResource::collection($mediaItems),
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $media = PostMedia::findOrFail($id);
        
        $user = $request->user();
        
        if ($media->post && $media->post->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->mediaService->delete($media);

        return response()->json(null, 204);
    }
}
