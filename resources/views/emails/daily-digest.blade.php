@component('mail::message')
# Your Daily Digest

Hello {{ $user->full_name }},

You have **{{ $count }}** notifications from today.

@foreach($notifications->take(10) as $notification)
- **{{ $notification->actor->username }}** {{ $notification->preview_text }}
@endforeach

@if($count > 10)
... and {{ $count - 10 }} more
@endif

@component('mail::button', ['url' => config('app.url') . '/notifications'])
View All Notifications
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
