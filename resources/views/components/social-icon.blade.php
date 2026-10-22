@props(['network'])

@switch($network)
    @case('facebook')
        <svg {{ $attributes }} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        @break
    @case('instagram')
        <svg {{ $attributes }} fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
        @break
    @case('youtube')
        <svg {{ $attributes }} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/></svg>
        @break
    @case('tiktok')
        <svg {{ $attributes }} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.1v12.4a2.6 2.6 0 1 1-2.6-2.6c.3 0 .5 0 .8.1V9.7a5.8 5.8 0 0 0-.8-.1 5.7 5.7 0 1 0 5.7 5.7V9a7.4 7.4 0 0 0 4.3 1.4V7.3a4.3 4.3 0 0 1-3.2-1.5z"/></svg>
        @break
    @case('x')
        <svg {{ $attributes }} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 1.2h3.7l-8 9.2 9.4 12.4h-7.4l-5.8-7.6-6.6 7.6H.5l8.6-9.8L0 1.2h7.6l5.2 6.9 6.1-6.9zm-1.3 19.4h2L6.5 3.2H4.3l13.3 17.4z"/></svg>
        @break
    @case('threads')
        <svg {{ $attributes }} fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.6 11.2c-.4-2.9-2.1-4.4-4.6-4.4-2.8 0-4.5 2.1-4.5 5.2s1.7 5.2 4.6 5.2c2.3 0 3.9-1.3 3.9-3.2 0-2.3-2.4-3.1-4.7-2.6-1.5.3-2.3 1.2-2.2 2.2.1 1.2 1.3 1.9 2.7 1.8 2.8-.2 3.8-2.8 3.3-6.6M19.6 7.4C18.1 4.5 15.5 3 12 3 6.8 3 4 6.6 4 12s2.8 9 8 9c3.5 0 6-1.5 7.3-4.3"/></svg>
        @break
    @case('linkedin')
        <svg {{ $attributes }} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13ZM7.12 20.45H3.56V9h3.56v11.45Z"/></svg>
        @break
    @case('twitch')
        <svg {{ $attributes }} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.64 5.93h1.43v4.28h-1.43m3.93-4.28H17v4.28h-1.43M7 2 3.43 5.57v12.86h4.28V22l3.58-3.57h2.85L20.57 12V2m-1.43 9.29-2.85 2.85h-2.86l-2.5 2.5v-2.5H7.71V3.43h11.43Z"/></svg>
        @break
    @case('strava')
        <svg {{ $attributes }} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="m15.39 17.94-2.09-4.12h-3.07L15.39 24l5.15-10.18h-3.07M10.46 0 3.46 13.82h4.17l2.83-5.59 2.84 5.59h4.17L10.46 0Z"/></svg>
        @break
    @case('spotify')
        <svg {{ $attributes }} fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M7 9.5c3.5-1 7.5-.6 10.5 1M7.5 12.8c2.9-.8 6.1-.4 8.5 1M8 15.9c2.3-.6 4.6-.3 6.5.8"/></svg>
        @break
    @default
        <svg {{ $attributes }} fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c2.5-2.5 3.75-5.5 3.75-9S14.5 5.5 12 3m0 18c-2.5-2.5-3.75-5.5-3.75-9S9.5 5.5 12 3M3.5 9h17M3.5 15h17"/></svg>
@endswitch
