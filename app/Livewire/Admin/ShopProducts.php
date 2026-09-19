<?php

namespace App\Livewire\Admin;

use App\Models\ShopProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class ShopProducts extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $category = '';

    public int $price = 0;

    public int $stock = 0;

    public ?string $description = null;

    /** Image en attente de téléversement (formulaire ouvert). */
    public $image = null;

    /** Chemin de l'image déjà enregistrée (aperçu + gardée si aucune nouvelle n'est choisie). */
    public ?string $image_path = null;

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:60'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['boolean'],
        ];
    }

    public function new(): void
    {
        $this->reset('editingId', 'name', 'category', 'description', 'image', 'image_path');
        $this->price = 0;
        $this->stock = 0;
        $this->is_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(ShopProduct $product): void
    {
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->category = $product->category;
        $this->price = $product->price;
        $this->stock = $product->stock;
        $this->description = $product->description;
        $this->image = null;
        $this->image_path = $product->image_path;
        $this->is_active = $product->is_active;
        $this->resetValidation();
        $this->showForm = true;
    }

    /** Retire l'image (effectif à l'enregistrement, comme le reste du formulaire). */
    public function removeImage(): void
    {
        $this->image = null;
        $this->image_path = null;
    }

    public function save(): void
    {
        $data = $this->validate();
        unset($data['image']);

        if ($this->image instanceof UploadedFile) {
            $data['image_path'] = $this->image->store('shop-products', 'public');
        } else {
            $data['image_path'] = $this->image_path;
        }

        if ($this->editingId) {
            $product = ShopProduct::findOrFail($this->editingId);
            if ($product->image_path && $product->image_path !== $data['image_path']) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->update($data);
            $message = 'Produit mis à jour.';
        } else {
            $data['position'] = (int) ShopProduct::max('position') + 1;
            ShopProduct::create($data);
            $message = 'Produit ajouté.';
        }

        $this->showForm = false;
        $this->dispatch('notify', message: $message);
    }

    public function toggleActive(ShopProduct $product): void
    {
        $product->update(['is_active' => ! $product->is_active]);
    }

    public function delete(ShopProduct $product): void
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        $this->dispatch('notify', message: 'Produit supprimé.');
    }

    public function render()
    {
        return view('livewire.admin.shop-products', [
            'products' => ShopProduct::orderBy('position')->orderBy('name')->get(),
        ]);
    }
}
