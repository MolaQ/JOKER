<div class="space-y-8">
    @if($editing)
        <form wire:submit="save" class="space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $editing === 'package' ? ($editingId ? 'Edytuj pakiet sponsorski' : 'Nowy pakiet sponsorski') : ($editingId ? 'Edytuj gadżet' : 'Nowy gadżet') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="support-name" class="block text-sm font-medium text-gray-700">Nazwa</label>
                    <input id="support-name" type="text" wire:model="name" placeholder="{{ $editing === 'package' ? 'np. Partner Złoty' : 'np. Koszulka kibica' }}" class="mt-1 block w-full">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="support-price" class="block text-sm font-medium text-gray-700">{{ $editing === 'package' ? 'Cena (tekst)' : 'Cena (zł)' }}</label>
                    <input id="support-price" type="text" wire:model="price" placeholder="{{ $editing === 'package' ? 'np. od 3 000 zł / sezon' : 'np. 79,00' }}" class="mt-1 block w-full">
                    @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                @if($editing === 'package')
                    <div class="sm:col-span-2">
                        <label for="support-benefits" class="block text-sm font-medium text-gray-700">Co dostaje sponsor (każda korzyść w osobnej linii)</label>
                        <textarea id="support-benefits" wire:model="benefits" rows="5" class="mt-1 block w-full"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_highlighted"> Wyróżniony („Polecany”)</label>
                @else
                    <div class="sm:col-span-2">
                        <label for="support-description" class="block text-sm font-medium text-gray-700">Opis</label>
                        <textarea id="support-description" wire:model="description" rows="2" class="mt-1 block w-full"></textarea>
                    </div>
                    <div>
                        <label for="support-sizes" class="block text-sm font-medium text-gray-700">Rozmiary</label>
                        <input id="support-sizes" type="text" wire:model="sizes" placeholder="np. S, M, L, XL" class="mt-1 block w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Zdjęcie</label>
                        @if($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="" class="mt-1 h-20 w-20 rounded object-cover">
                        @elseif($existingPhoto)
                            <img src="{{ \App\Support\Media::url($existingPhoto) }}" alt="" class="mt-1 h-20 w-20 rounded object-cover">
                        @endif
                        <x-photo-cropper model="photo" class="mt-2" :preview="false" aspect="4/3" :width="1200" />
                        @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_available"> Dostępny (można zamówić)</label>
                @endif
                <label class="block text-sm text-gray-700">Kolejność <input type="number" wire:model="display_order" min="0" class="ml-2 w-20"></label>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" wire:click="resetForm" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Anuluj</button>
                <button type="submit" class="rounded-md bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Zapisz</button>
            </div>
        </form>
    @endif

    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900"><x-admin-icon name="briefcase" class="text-zinc-400" /> Pakiety sponsorskie</h2>
            <button type="button" wire:click="createPackage" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-sky-700">+ Dodaj pakiet</button>
        </div>
        <ul class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow ring-1 ring-zinc-200">
            @forelse($packages as $package)
                <li class="flex items-center gap-4 p-4" wire:key="package-{{ $package->id }}">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">{{ $package->name }} @if($package->is_highlighted)<span class="text-amber-500">★</span>@endif</p>
                        <p class="text-sm text-gray-500">{{ $package->price ?: 'cena do ustalenia' }} · {{ count($package->benefitList()) }} korzyści</p>
                    </div>
                    <button type="button" wire:click="editPackage({{ $package->id }})" class="text-sm font-semibold text-sky-700 hover:text-sky-900">Edytuj</button>
                    <button type="button" wire:click="deletePackage({{ $package->id }})" wire:confirm="Usunąć pakiet {{ $package->name }}?" class="text-sm font-semibold text-red-600 hover:text-red-800">Usuń</button>
                </li>
            @empty
                <li class="p-6 text-sm text-gray-500">Brak pakietów. Bez nich sekcja jest ukryta na stronie.</li>
            @endforelse
        </ul>
    </section>

    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900"><x-admin-icon name="bag" class="text-zinc-400" /> Gadżety klubowe</h2>
            <button type="button" wire:click="createProduct" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-sky-700">+ Dodaj gadżet</button>
        </div>
        <ul class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow ring-1 ring-zinc-200">
            @forelse($products as $product)
                <li class="flex items-center gap-4 p-4" wire:key="product-{{ $product->id }}">
                    @if($product->photoUrl())
                        <img src="{{ $product->photoUrl() }}" alt="" class="h-12 w-12 rounded object-cover">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded bg-zinc-100 text-zinc-400"><x-admin-icon name="bag" /></span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">{{ $product->name }} @unless($product->is_available)<span class="text-xs font-normal text-gray-400">(niedostępny)</span>@endunless</p>
                        <p class="text-sm text-gray-500">{{ $product->priceLabel() ?? 'bez ceny' }}@if($product->sizes) · {{ $product->sizes }}@endif</p>
                    </div>
                    <button type="button" wire:click="editProduct({{ $product->id }})" class="text-sm font-semibold text-sky-700 hover:text-sky-900">Edytuj</button>
                    <button type="button" wire:click="deleteProduct({{ $product->id }})" wire:confirm="Usunąć {{ $product->name }}?" class="text-sm font-semibold text-red-600 hover:text-red-800">Usuń</button>
                </li>
            @empty
                <li class="p-6 text-sm text-gray-500">Brak gadżetów. Bez nich sekcja jest ukryta na stronie.</li>
            @endforelse
        </ul>
    </section>
</div>
