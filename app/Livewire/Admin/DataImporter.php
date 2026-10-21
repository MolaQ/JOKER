<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Support\DataImport;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Import drużyn, sztabu i zawodników z pliku CSV albo wklejonego tekstu: najpierw podgląd z błędami, potem zapis.
 */
class DataImporter extends Component
{
    use ShowsToasts, WithFileUploads;

    public string $type = 'teams';

    public ?TemporaryUploadedFile $file = null;

    public string $text = '';

    /** @var list<array{line: int, data: array<string, string>, errors: list<string>, action: string}> */
    public array $rows = [];

    public function updatedType(): void
    {
        $this->reset('file', 'text', 'rows');
    }

    public function updatedFile(): void
    {
        $this->validate(['file' => 'file|max:2048|mimes:csv,txt']);
        $this->text = DataImport::toUtf8((string) file_get_contents($this->file->getRealPath()));
        $this->preview();
    }

    public function preview(): void
    {
        $this->rows = DataImport::parse($this->type, $this->text);

        if ($this->rows === []) {
            $this->toast('Plik jest pusty: potrzebny wiersz nagłówków i co najmniej jeden wiersz danych.', 'error');
        }
    }

    public function import(): void
    {
        $this->rows = DataImport::parse($this->type, $this->text);
        $count = DataImport::import($this->type, $this->rows);
        $skipped = count($this->rows) - $count;

        $this->toast('Zaimportowano: '.$count.($skipped ? ', pominięto z błędami: '.$skipped : '').'.');
        $this->rows = DataImport::parse($this->type, $this->text);
    }

    public function render(): View
    {
        return view('livewire.admin.data-importer', [
            'columns' => DataImport::COLUMNS[$this->type],
            'valid' => collect($this->rows)->where('errors', [])->count(),
        ]);
    }
}
