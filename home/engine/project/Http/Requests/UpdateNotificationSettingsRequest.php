<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notify_likes' => ['sometimes', 'boolean'],
            'notify_reposts' => ['sometimes', 'boolean'],
            'notify_quotes' => ['sometimes', 'boolean'],
            'notify_replies' => ['sometimes', 'boolean'],
            'notify_mentions' => ['sometimes', 'boolean'],
            'notify_follows' => ['sometimes', 'boolean'],
            'notify_follower_posts' => ['sometimes', 'boolean'],
            'quiet_hours_enabled' => ['sometimes', 'boolean'],
            'quiet_hours_start' => ['sometimes', 'date_format:H:i:s'],
            'quiet_hours_end' => ['sometimes', 'date_format:H:i:s'],
            'email_digest_enabled' => ['sometimes', 'boolean'],
            'email_digest_frequency' => ['sometimes', 'string', 'in:never,daily,weekly'],
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
