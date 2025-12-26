<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'string', 'max:500'],
            'media_files' => ['sometimes', 'array', 'max:10'],
            'media_files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,mp4,webm', 'max:102400'],
            'media_urls' => ['sometimes', 'array'],
            'visibility' => ['sometimes', 'string', 'in:public,private,followers_only'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.max' => 'Post content cannot exceed 500 characters',
            'media_files.max' => 'Maximum 10 media files allowed',
            'media_files.*.mimes' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, MP4, WebM',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
