<?php

namespace App\Livewire\Admin;

use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DocumentManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $category = 'other';

    public bool $is_public = true;

    public $file = null;

    public ?string $existingFile = null;

    public string $search = '';

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:regulations,forms,reports,other',
            'is_public' => 'boolean',
        ];

        $rules['file'] = $this->editingId ? 'nullable|file|max:10240' : 'required|file|max:10240';

        return $rules;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->flashType = null;
        $this->flashMessage = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $document = Document::findOrFail($id);

        $this->editingId = $document->id;
        $this->title = $document->title;
        $this->description = (string) $document->description;
        $this->category = $document->category;
        $this->is_public = $document->is_public;
        $this->existingFile = $document->file_path;
        $this->file = null;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->file) {
            $validated['file_path'] = $this->file->store('documents', 'public');
            $validated['file_type'] = $this->file->getClientOriginalExtension();
            $validated['file_size'] = $this->file->getSize();
        }

        if ($this->editingId) {
            $document = Document::findOrFail($this->editingId);
            $document->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Dokument został zaktualizowany.';
        } else {
            $validated['uploaded_by'] = Auth::id();
            Document::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Dokument został dodany.';
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        Document::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Dokument został usunięty.';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->category = 'other';
        $this->is_public = true;
        $this->file = null;
        $this->existingFile = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $documents = Document::with('uploader')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.document-manager', [
            'documents' => $documents,
        ]);
    }
}
