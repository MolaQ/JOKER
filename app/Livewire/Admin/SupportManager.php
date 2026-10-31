<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\ShopProduct;
use App\Models\SupportPackage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Strona „Wspieraj klub”: pakiety sponsorskie i gadżety klubowe. Numer KRS i konto są w ustawieniach strony.
 */
class SupportManager extends Component
{
    use ShowsToasts, WithFileUploads;

    /** Edytowany element: 'package' albo 'product'. */
    public ?string $editing = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $price = '';

    public string $benefits = '';

    public bool $is_highlighted = false;

    public string $description = '';

    public string $sizes = '';

    public bool $is_available = true;

    public int $display_order = 0;

    public $photo = null;

    public ?string $existingPhoto = null;

    public function createPackage(): void
    {
        $this->resetForm();
        $this->editing = 'package';
    }

    public function createProduct(): void
    {
        $this->resetForm();
        $this->editing = 'product';
    }

    public function editPackage(int $id): void
    {
        $package = SupportPackage::findOrFail($id);
        $this->resetForm();
        $this->editing = 'package';
        $this->editingId = $package->id;
        $this->name = $package->name;
        $this->price = (string) $package->price;
        $this->benefits = (string) $package->benefits;
        $this->is_highlighted = $package->is_highlighted;
        $this->display_order = $package->display_order;
    }

    public function editProduct(int $id): void
    {
        $product = ShopProduct::findOrFail($id);
        $this->resetForm();
        $this->editing = 'product';
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->price = $product->price === null ? '' : str_replace('.', ',', (string) $product->price);
        $this->description = (string) $product->description;
        $this->sizes = (string) $product->sizes;
        $this->is_available = $product->is_available;
        $this->display_order = $product->display_order;
        $this->existingPhoto = $product->photo_path;
    }

    public function save(): void
    {
        if ($this->editing === 'package') {
            $data = $this->validate([
                'name' => 'required|string|max:255',
                'price' => 'nullable|string|max:255',
                'benefits' => 'nullable|string|max:3000',
                'is_highlighted' => 'boolean',
                'display_order' => 'integer|min:0',
            ]);
            SupportPackage::updateOrCreate(['id' => $this->editingId], array_map(fn ($value) => $value === '' ? null : $value, $data));
        } else {
            $this->price = str_replace([',', ' ', 'zł'], ['.', '', ''], $this->price);
            $data = $this->validate([
                'name' => 'required|string|max:255',
                'price' => 'nullable|numeric|min:0|max:99999',
                'description' => 'nullable|string|max:1000',
                'sizes' => 'nullable|string|max:255',
                'is_available' => 'boolean',
                'display_order' => 'integer|min:0',
                'photo' => 'nullable|image|max:4096',
            ]);
            unset($data['photo']);
            $data = array_map(fn ($value) => $value === '' ? null : $value, $data);

            if ($this->photo) {
                $data['photo_path'] = $this->photo->store('shop', 'public');
            }

            ShopProduct::updateOrCreate(['id' => $this->editingId], $data);
        }

        $this->toast('Zapisano.');
        $this->resetForm();
    }

    public function deletePackage(int $id): void
    {
        SupportPackage::findOrFail($id)->delete();
        $this->toast('Usunięto pakiet.');
    }

    public function deleteProduct(int $id): void
    {
        ShopProduct::findOrFail($id)->delete();
        $this->toast('Usunięto gadżet.');
    }

    public function resetForm(): void
    {
        $this->reset(['editing', 'editingId', 'name', 'price', 'benefits', 'is_highlighted', 'description', 'sizes', 'is_available', 'display_order', 'photo', 'existingPhoto']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.support-manager', [
            'packages' => SupportPackage::orderBy('display_order')->orderBy('id')->get(),
            'products' => ShopProduct::orderBy('display_order')->orderBy('id')->get(),
        ]);
    }
}
