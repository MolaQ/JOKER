@extends('layouts.public')

@section('title', 'Kontakt')

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <p class="eyebrow">Skontaktuj się z nami</p>
    <h1 class="heading-xl mt-4">Kontakt</h1>
    @if(filled($site['contact_intro']))
        <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ $site['contact_intro'] }}</p>
    @endif
</section>

<!-- Contact Content -->
<section class="container-site py-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Contact Form -->
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 mb-6">Wyślij wiadomość</h2>

            <form method="POST" action="{{ route('contact.send') }}" class="space-y-6">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-zinc-700 font-semibold mb-2">
                        Imię i nazwisko *
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent @error('name') border-red-500 @enderror"
                        placeholder="Jan Kowalski"
                    >
                    @error('name')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-zinc-700 font-semibold mb-2">
                        Email *
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent @error('email') border-red-500 @enderror"
                        placeholder="twoj@email.pl"
                    >
                    @error('email')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-zinc-700 font-semibold mb-2">
                        Temat *
                    </label>
                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        value="{{ old('subject') }}"
                        required
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent @error('subject') border-red-500 @enderror"
                        placeholder="W jakiej sprawie się kontaktujesz?"
                    >
                    @error('subject')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Message -->
                <div>
                    <label for="message" class="block text-zinc-700 font-semibold mb-2">
                        Wiadomość *
                    </label>
                    <textarea
                        id="message"
                        name="message"
                        rows="6"
                        required
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent @error('message') border-red-500 @enderror"
                        placeholder="Twoja wiadomość..."
                    >{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-ink text-white py-3 rounded-lg hover:bg-graphite transition font-semibold"
                >
                    Wyślij wiadomość
                </button>

                <p class="text-sm text-zinc-600">
                    * Pola wymagane
                </p>
            </form>
        </div>

        <!-- Contact Information -->
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 mb-6">Informacje kontaktowe</h2>

            <div class="bg-white rounded-3xl ring-1 ring-zinc-200/70 p-8 space-y-6">
                <!-- Address -->
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-ink text-white rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-zinc-900 mb-1">Adres</h3>
                        <p class="text-zinc-600">{{ $site['contact_address'] }}</p>
                    </div>
                </div>

                <!-- Phone -->
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-ink text-white rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-zinc-900 mb-1">Telefon</h3>
                        <p class="text-zinc-600">
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $site['contact_phone']) }}" class="hover:text-sky-club-dark transition">{{ $site['contact_phone'] }}</a>
                        </p>
                    </div>
                </div>

                <!-- Email -->
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-ink text-white rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-zinc-900 mb-1">Email</h3>
                        <p class="text-zinc-600">
                            <a href="mailto:{{ $site['contact_email'] }}" class="hover:text-sky-club-dark transition">{{ $site['contact_email'] }}</a>
                        </p>
                    </div>
                </div>

                <!-- Hours -->
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-ink text-white rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-zinc-900 mb-1">Godziny otwarcia</h3>
                        <p class="whitespace-pre-line text-zinc-600">{{ $site['contact_hours'] }}</p>
                    </div>
                </div>
            </div>

            @if($site->socials())
                <div class="mt-8">
                    <h3 class="text-xl font-bold text-zinc-900 mb-4">Media społecznościowe</h3>
                    <div class="flex flex-wrap gap-3">
                        @foreach($site->socials() as $network => $socialUrl)
                            <a href="{{ $socialUrl }}" target="_blank" rel="noopener" class="w-12 h-12 bg-ink text-white rounded-lg flex items-center justify-center hover:bg-sky-club transition" aria-label="{{ ucfirst($network) }}">
                                <x-social-icon :network="$network" class="w-6 h-6" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
