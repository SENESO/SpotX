<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    protected string $disk = 'public';
    
    protected array $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected array $allowedVideoTypes = ['mp4', 'webm'];
    
    protected int $maxImageSize = 10 * 1024 * 1024; // 10MB
    protected int $maxVideoSize = 100 * 1024 * 1024; // 100MB

    public function upload(UploadedFile $file, ?int $postId = null): PostMedia
    {
        $this->validateFile($file);
        
        $type = $this->determineType($file);
        $extension = strtolower($file->getClientOriginalExtension());
        
        $filename = $this->generateFilename($extension);
        $path = $this->getPath($type);
        
        $fullPath = $file->storeAs($path, $filename, $this->disk);
        
        $media = PostMedia::create([
            'post_id' => $postId,
            'media_url' => Storage::url($fullPath),
            'media_type' => $type,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        if ($type === 'image') {
            $this->generateThumbnail($media, $fullPath);
        } elseif ($type === 'video') {
            $this->generateVideoThumbnail($media, $fullPath);
        }

        return $media->fresh();
    }

    public function uploadMultiple(array $files, ?int $postId = null): array
    {
        $mediaItems = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $mediaItems[] = $this->upload($file, $postId);
            }
        }
        
        return $mediaItems;
    }

    public function delete(PostMedia $media): void
    {
        $this->deleteFile($media->media_url);
        
        if ($media->thumbnail_url) {
            $this->deleteFile($media->thumbnail_url);
        }
        
        $media->delete();
    }

    protected function validateFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $size = $file->getSize();
        
        $isImage = in_array($extension, $this->allowedImageTypes);
        $isVideo = in_array($extension, $this->allowedVideoTypes);
        
        if (!$isImage && !$isVideo) {
            throw new \InvalidArgumentException(
                "Invalid file type. Allowed types: " . implode(', ', array_merge($this->allowedImageTypes, $this->allowedVideoTypes))
            );
        }
        
        if ($isImage && $size > $this->maxImageSize) {
            throw new \InvalidArgumentException(
                "Image size exceeds maximum allowed size of 10MB"
            );
        }
        
        if ($isVideo && $size > $this->maxVideoSize) {
            throw new \InvalidArgumentException(
                "Video size exceeds maximum allowed size of 100MB"
            );
        }
    }

    protected function determineType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $this->allowedImageTypes)) {
            return 'image';
        }
        
        return 'video';
    }

    protected function generateFilename(string $extension): string
    {
        return sprintf(
            '%s_%s.%s',
            now()->format('YmdHis'),
            Str::random(8),
            $extension
        );
    }

    protected function getPath(string $type): string
    {
        $date = now()->format('Y/m/d');
        return "media/{$type}s/{$date}";
    }

    protected function generateThumbnail(PostMedia $media, string $fullPath): void
    {
        try {
            $image = \Image::make(storage_path('app/' . $fullPath));
            
            $thumbnailPath = str_replace(
                '/media/',
                '/media/thumbnails/',
                dirname($fullPath)
            );
            
            $thumbnailFilename = 'thumb_' . basename($fullPath);
            
            $image->fit(400, 400, function ($constraint) {
                $constraint->aspectRatio();
            })->save(
                storage_path('app/' . $thumbnailPath . '/' . $thumbnailFilename),
                80
            );
            
            $media->update([
                'thumbnail_url' => Storage::url($thumbnailPath . '/' . $thumbnailFilename),
            ]);
        } catch (\Exception $e) {
            \Log::error('Thumbnail generation failed: ' . $e->getMessage());
        }
    }

    protected function generateVideoThumbnail(PostMedia $media, string $fullPath): void
    {
        try {
            $thumbnailPath = str_replace(
                '/media/',
                '/media/thumbnails/',
                dirname($fullPath)
            );
            
            $thumbnailFilename = 'thumb_' . pathinfo(basename($fullPath), PATHINFO_FILENAME) . '.jpg';
            
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
                'ffprobe.binaries' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
            ]);
            
            $video = $ffmpeg->open(storage_path('app/' . $fullPath));
            
            $frame = $video->frame(\FFMpeg\Coordinates\TimeCode::fromSeconds(1));
            $frame->save(
                storage_path('app/' . $thumbnailPath . '/' . $thumbnailFilename)
            );
            
            $media->update([
                'thumbnail_url' => Storage::url($thumbnailPath . '/' . $thumbnailFilename),
            ]);
        } catch (\Exception $e) {
            \Log::error('Video thumbnail generation failed: ' . $e->getMessage());
        }
    }

    protected function deleteFile(string $url): void
    {
        $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
        Storage::disk($this->disk)->delete($path);
    }

    public function getMediaUrls(array $mediaItems): array
    {
        return array_map(function ($item) {
            return [
                'url' => $item->media_url,
                'thumbnail' => $item->thumbnail_url,
                'type' => $item->media_type,
                'width' => $item->width,
                'height' => $item->height,
                'duration' => $item->duration,
            ];
        }, $mediaItems);
    }
}
