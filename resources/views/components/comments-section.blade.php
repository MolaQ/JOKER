@props(['comments', 'action', 'title' => null])
{{-- Komentarze: widoczne i dodawane tylko po zalogowaniu; goście widzą samą liczbę komentarzy --}}
@php $count = $comments->count(); @endphp
<section id="komentarze" {{ $attributes->merge(['class' => 'scroll-mt-32']) }}>
    <div class="flex flex-wrap items-center gap-3">
        <h2 class="heading-md">{{ $title ?? __('Komentarze') }}</h2>
        <x-comment-count :count="$count" />
    </div>

    @auth
        @if(! auth()->user()->is_active)
            <p class="mt-6 rounded-2xl bg-zinc-100 p-5 text-sm text-zinc-600">{{ __('Komentować możesz po aktywacji konta.') }}</p>
        @elseif(! auth()->user()->canComment())
            <p class="mt-6 rounded-2xl bg-zinc-100 p-5 text-sm text-zinc-600">{{ __('Klub wyłączył Ci możliwość komentowania.') }}</p>
        @else
        <form action="{{ $action }}" method="POST" class="card mt-6 p-6">
            @csrf
            <label for="comment" class="text-sm font-semibold">{{ __('Twój komentarz') }}</label>
            <textarea id="comment" name="content" rows="4" required maxlength="1000" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 outline-none focus:border-sky-club focus:ring-2 focus:ring-sky-club/30">{{ old('content') }}</textarea>
            @error('content') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <div class="mt-4 flex justify-end"><button type="submit" class="btn-primary">{{ __('Wyślij') }}</button></div>
        </form>
        @endif

        <div class="mt-8 space-y-4">
            @foreach($comments->whereNull('parent_id') as $comment)
                <div class="card p-5">
                    <div class="flex items-center gap-3">
                        <x-avatar :name="$comment->user?->name ?? '?'" class="h-9 w-9 rounded-full text-sm" />
                        <p class="text-sm"><span class="font-semibold">{{ $comment->user?->name }}</span> <span class="text-zinc-400">· {{ $comment->created_at->diffForHumans() }}</span></p>
                    </div>
                    <p class="mt-3 whitespace-pre-line text-zinc-700">{{ $comment->content }}</p>

                    @if($comment->relationLoaded('replies'))
                        @foreach($comment->replies->where('is_approved', true) as $reply)
                            <div class="mt-4 ml-6 border-l-2 border-sky-club/40 pl-4">
                                <p class="text-sm"><span class="font-semibold">{{ $reply->user?->name }}</span> <span class="text-zinc-400">· {{ $reply->created_at->diffForHumans() }}</span></p>
                                <p class="mt-1 whitespace-pre-line text-zinc-700">{{ $reply->content }}</p>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl bg-sky-club/10 p-5 text-sm">
            <span aria-hidden="true">🔒</span>
            <a href="{{ route('login') }}" class="font-semibold text-sky-club-dark underline">{{ __('Zaloguj się, aby komentować') }}</a>
        </p>
    @endauth
</section>
