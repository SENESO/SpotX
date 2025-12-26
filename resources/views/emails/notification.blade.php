@component('mail::message')
# Hello {{ $user->full_name }},

{{ $previewText }}

@if($relatedPost ?? null)
@component('mail::button', ['url' => $actionUrl])
View Post
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
