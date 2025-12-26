@component('mail::message')
# Your Weekly Digest

Hello {{ $user->full_name }},

You have **{{ $count }}** notifications from this week.

@foreach($notifications->groupBy('type')->take(5) as $type => $typeNotifications)
- **{{ ucfirst($type) }}s**: {{ $typeNotifications->count() }}
@endforeach

@component('mail::button', ['url' => config('app.url') . '/notifications'])
View All Notifications
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
