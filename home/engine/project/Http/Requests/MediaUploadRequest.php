<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,mp4,webm', 'max:102400'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A media file is required',
            'file.mimes' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, MP4, WebM',
            'file.max' => 'File size exceeds maximum allowed size (100MB for videos, 10MB for images)',
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
