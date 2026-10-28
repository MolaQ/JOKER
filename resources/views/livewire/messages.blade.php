<div wire:poll.15s class="grid gap-4 lg:grid-cols-[22rem_1fr]">
    {{-- Lista rozmów (na telefonie tylko wtedy, gdy żadna nie jest otwarta) --}}
    <aside @class(['card flex min-h-0 flex-col overflow-hidden', 'hidden lg:flex' => $conversation || $composing])>
        <div class="flex items-center justify-between gap-2 border-b border-zinc-100 p-4">
            <h2 class="font-display text-xl font-semibold text-zinc-900 uppercase">{{ __('Rozmowy') }}</h2>
            @if($canWrite)
                <button type="button" wire:click="startComposing" class="btn-primary px-4! py-2! text-sm">{{ __('Nowa wiadomość') }}</button>
            @endif
        </div>
        <ul class="max-h-[70vh] divide-y divide-zinc-100 overflow-y-auto">
            @forelse($conversations as $item)
                @php $last = $item->latestMessage; @endphp
                <li wire:key="conversation-{{ $item->id }}">
                    <button type="button" wire:click="open({{ $item->id }})" @class([
                        'flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-zinc-50',
                        'bg-sky-club/10' => $conversation?->id === $item->id,
                    ])>
                        <x-avatar :name="$item->titleFor(auth()->user())" :label="$item->isTeamGroup() ? '👥' : null" class="h-10 w-10 shrink-0 rounded-full text-sm" />
                        <span class="min-w-0 flex-1">
                            <span class="flex items-baseline justify-between gap-2">
                                <span @class(['truncate text-sm', 'font-bold text-zinc-900' => $item->unread_count > 0, 'font-semibold text-zinc-800' => ! $item->unread_count])>{{ $item->titleFor(auth()->user()) }}</span>
                                @if($item->last_message_at)
                                    <span class="shrink-0 text-xs text-zinc-400">{{ $item->last_message_at->isToday() ? $item->last_message_at->format('H:i') : $item->last_message_at->format('d.m') }}</span>
                                @endif
                            </span>
                            <span class="mt-0.5 flex items-center gap-2">
                                <span class="truncate text-xs text-zinc-500">
                                    @if($last)
                                        @if($last->user_id === auth()->id()){{ __('Ty') }}: @elseif($item->isGroup() && $last->author){{ $last->author->name }}: @endif{{ $last->isHidden() ? __('wiadomość ukryta') : \Illuminate\Support\Str::limit($last->body, 70) }}
                                    @endif
                                </span>
                                @if($item->unread_count > 0)
                                    <span class="ml-auto shrink-0 rounded-full bg-red-600 px-1.5 text-xs font-bold text-white tabular-nums">{{ $item->unread_count }}</span>
                                @endif
                            </span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="p-6 text-sm text-zinc-500">{{ __('Nie masz jeszcze żadnych rozmów.') }}</li>
            @endforelse
        </ul>
        <div class="mt-auto border-t border-zinc-100 p-4 text-xs text-zinc-500">
            <button type="button" wire:click="toggleEmails" class="inline-flex items-center gap-2 font-medium text-zinc-600 hover:text-zinc-900">
                <span @class(['inline-flex h-5 w-9 items-center rounded-full p-0.5 transition', 'bg-sky-club' => auth()->user()->notify_messages, 'bg-zinc-300' => ! auth()->user()->notify_messages])><span @class(['h-4 w-4 rounded-full bg-white shadow transition', 'translate-x-4' => auth()->user()->notify_messages])></span></span>
                {{ __('E-mail o nieprzeczytanych wiadomościach') }}
            </button>
        </div>
    </aside>

    <section @class(['card flex min-h-[60vh] flex-col overflow-hidden', 'hidden lg:flex' => ! $conversation && ! $composing])>
        @if($composing)
            <div class="flex items-center gap-3 border-b border-zinc-100 p-4">
                <button type="button" wire:click="back" class="text-sm font-semibold text-sky-club-dark lg:hidden">← {{ __('Rozmowy') }}</button>
                <h2 class="font-display text-xl font-semibold text-zinc-900 uppercase">{{ __('Nowa wiadomość') }}</h2>
            </div>
            <form wire:submit="startConversation" class="space-y-4 p-4">
                @if($this->groupTeams->isNotEmpty())
                    <div>
                        <label for="team-group" class="block text-sm font-semibold text-zinc-700">{{ __('Do całej drużyny') }}</label>
                        <select id="team-group" wire:model.live="teamGroup" class="mt-1 w-full rounded-lg border-zinc-300 text-sm">
                            <option value="">{{ __('— nie, wybieram osoby —') }}</option>
                            @foreach($this->groupTeams as $team)
                                <optgroup label="{{ $team->name }}">
                                    @foreach($audiences as $key => $label)
                                        <option value="{{ $team->id }}:{{ $key }}">{{ $team->name }} – {{ __($label) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('teamGroup')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-zinc-500">{{ __('Grupa obejmuje konta zawodników i rodziców z drużyny oraz jej sztab. Osoby, które dojdą później, dopiszą się same.') }}</p>
                    </div>
                @endif

                @if($teamGroup === '')
                    <div>
                        <label for="recipient-search" class="block text-sm font-semibold text-zinc-700">{{ __('Do kogo') }}</label>
                        @if($chosen->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($chosen as $person)
                                    <span wire:key="chosen-{{ $person->id }}" class="inline-flex items-center gap-1 rounded-full bg-sky-club/15 py-1 pr-1 pl-3 text-sm font-medium text-sky-club-dark">
                                        {{ $person->name }}
                                        <button type="button" wire:click="removeRecipient({{ $person->id }})" class="flex h-6 w-6 items-center justify-center rounded-full hover:bg-sky-club/20" aria-label="{{ __('Usuń') }} {{ $person->name }}">×</button>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <input id="recipient-search" type="search" wire:model.live.debounce.300ms="recipientSearch" autocomplete="off" placeholder="{{ __('Wpisz imię lub nazwisko…') }}" class="mt-2 w-full rounded-lg border-zinc-300 text-sm">
                        @if($matches->isNotEmpty())
                            <ul class="mt-1 divide-y divide-zinc-100 rounded-lg ring-1 ring-zinc-200">
                                @foreach($matches as $person)
                                    <li wire:key="match-{{ $person->id }}">
                                        <button type="button" wire:click="addRecipient({{ $person->id }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50">
                                            <span class="font-medium text-zinc-900">{{ $person->name }}</span>
                                            <span class="text-xs text-zinc-500">{{ $person->roles()->map(fn ($role) => $role->label())->join(', ') ?: __('Kibic') }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @elseif(mb_strlen(trim($recipientSearch)) >= 2)
                            <p class="mt-1 text-sm text-zinc-500">{{ __('Nikogo takiego nie znaleźliśmy.') }}</p>
                        @endif
                        @error('recipientIds')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if(count($recipientIds) > 1)
                        <div>
                            <label for="group-title" class="block text-sm font-semibold text-zinc-700">{{ __('Nazwa rozmowy (opcjonalnie)') }}</label>
                            <input id="group-title" type="text" wire:model="groupTitle" maxlength="120" placeholder="{{ __('np. Wyjazd na turniej') }}" class="mt-1 w-full rounded-lg border-zinc-300 text-sm">
                        </div>
                    @endif
                @endif

                <div>
                    <label for="new-body" class="block text-sm font-semibold text-zinc-700">{{ __('Wiadomość') }}</label>
                    <textarea id="new-body" wire:model="newBody" rows="5" maxlength="{{ \App\Models\Message::MAX_LENGTH }}" class="mt-1 w-full rounded-lg border-zinc-300 text-sm"></textarea>
                    @error('newBody')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="back" class="btn-ghost text-zinc-600!">{{ __('Anuluj') }}</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">{{ __('Wyślij') }}</button>
                </div>
            </form>
        @elseif($conversation)
            <div class="flex items-center gap-3 border-b border-zinc-100 p-4">
                <button type="button" wire:click="back" class="text-sm font-semibold text-sky-club-dark lg:hidden">←</button>
                <div class="min-w-0">
                    <h2 class="truncate font-display text-xl font-semibold text-zinc-900 uppercase">{{ $conversation->titleFor(auth()->user()) }}</h2>
                    @if($conversation->isGroup())
                        <p class="truncate text-xs text-zinc-500" title="{{ $conversation->participants->pluck('name')->join(', ') }}">{{ trans_choice('{1} :count osoba|[2,4] :count osoby|[5,*] :count osób', $conversation->participants->count(), ['count' => $conversation->participants->count()]) }}: {{ $conversation->participants->pluck('name')->take(8)->join(', ') }}{{ $conversation->participants->count() > 8 ? '…' : '' }}</p>
                    @endif
                </div>
            </div>

            <div class="flex flex-1 flex-col gap-3 overflow-y-auto bg-zinc-50/60 p-4" style="max-height: 60vh" x-data x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
                @forelse($thread as $message)
                    @php $mine = $message->user_id === auth()->id(); @endphp
                    <div wire:key="message-{{ $message->id }}" @class(['group flex max-w-[85%] flex-col', 'self-end items-end' => $mine, 'self-start items-start' => ! $mine])>
                        @if(! $mine && $conversation->isGroup())
                            <span class="mb-0.5 px-1 text-xs font-semibold text-zinc-600">{{ $message->author?->name ?? __('Konto usunięte') }}</span>
                        @endif
                        @if($message->isHidden())
                            <p class="rounded-2xl bg-zinc-100 px-4 py-2 text-sm text-zinc-400 italic">{{ __('Wiadomość ukryta przez moderatora.') }}</p>
                        @else
                            <p @class(['rounded-2xl px-4 py-2 text-sm break-words whitespace-pre-line', 'bg-sky-club text-white' => $mine, 'bg-white text-zinc-800 ring-1 ring-zinc-200' => ! $mine])>{{ $message->body }}</p>
                        @endif
                        <span class="mt-0.5 flex items-center gap-2 px-1 text-[11px] text-zinc-400">
                            {{ $message->created_at->isToday() ? $message->created_at->format('H:i') : $message->created_at->format('d.m.Y H:i') }}
                            @if(! $mine && ! $message->isHidden())
                                @if($message->reported_at)
                                    <span>· {{ __('zgłoszona') }}</span>
                                @else
                                    <button type="button" wire:click="report({{ $message->id }})" wire:confirm="{{ __('Zgłosić tę wiadomość moderatorom klubu?') }}" class="opacity-0 transition group-hover:opacity-100 focus:opacity-100 hover:text-red-600">· {{ __('Zgłoś') }}</button>
                                @endif
                            @endif
                            @if($canModerate && ! $message->isHidden())
                                <button type="button" wire:click="hide({{ $message->id }})" wire:confirm="{{ __('Ukryć tę wiadomość u wszystkich?') }}" class="opacity-0 transition group-hover:opacity-100 focus:opacity-100 hover:text-red-600">· {{ __('Ukryj') }}</button>
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="m-auto text-sm text-zinc-500">{{ __('Brak wiadomości.') }}</p>
                @endforelse
            </div>

            @if($canWrite)
                <form wire:submit="send" class="flex items-end gap-2 border-t border-zinc-100 p-3">
                    <label for="reply-body" class="sr-only">{{ __('Wiadomość') }}</label>
                    <textarea id="reply-body" wire:model="body" rows="2" maxlength="{{ \App\Models\Message::MAX_LENGTH }}" placeholder="{{ __('Napisz wiadomość…') }}" class="min-h-11 flex-1 resize-y rounded-xl border-zinc-300 text-sm" x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit() }"></textarea>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">{{ __('Wyślij') }}</button>
                </form>
                @error('body')<p class="px-4 pb-3 text-sm text-red-600">{{ $message }}</p>@enderror
            @else
                <p class="border-t border-zinc-100 p-4 text-sm text-zinc-500">{{ __('Klub wyłączył Ci możliwość pisania wiadomości.') }}</p>
            @endif
        @else
            <div class="m-auto max-w-sm p-8 text-center text-sm text-zinc-500">
                <p class="text-4xl" aria-hidden="true">💬</p>
                <p class="mt-3">{{ __('Wybierz rozmowę z listy albo napisz nową wiadomość.') }}</p>
            </div>
        @endif
    </section>
</div>
