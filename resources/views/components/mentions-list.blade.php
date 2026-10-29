@props(['articles', 'name'])
{{-- Artykuły, w których ktoś oznaczył osobę przez @Imię Nazwisko --}}
<div class="card p-8">
    <h2 class="heading-md">{{ __('Wzmianki') }}</h2>
    <p class="mt-1 text-sm text-zinc-500">{{ __('Artykuły, w których pojawia się :name.', ['name' => $name]) }}</p>
    <ul class="mt-5 divide-y divide-zinc-100">
        @foreach($articles as $mentionArticle)
            <li>
                <a href="{{ route('articles.show', $mentionArticle) }}" class="group flex items-baseline gap-4 py-3">
                    <span class="w-20 shrink-0 text-sm text-zinc-500 tabular-nums">{{ $mentionArticle->published_at?->format('d.m.Y') }}</span>
                    <span class="min-w-0 flex-1 font-semibold text-zinc-900 transition group-hover:text-sky-club-dark">{{ $mentionArticle->localizedTitle() }}</span>
                    <span class="text-sky-club transition group-hover:translate-x-1" aria-hidden="true">→</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
