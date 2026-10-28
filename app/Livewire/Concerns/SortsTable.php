<?php

namespace App\Livewire\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Sortowanie listy w panelu kliknięciem nagłówka kolumny: pierwsze kliknięcie sortuje,
 * drugie odwraca kolejność, trzecie wraca do kolejności domyślnej. Stan jest w adresie strony.
 */
trait SortsTable
{
    #[Url(as: 'sortuj', except: '')]
    public string $sortField = '';

    #[Url(as: 'kierunek', except: 'asc')]
    public string $sortDirection = 'asc';

    /**
     * Kolumny do sortowania: klucz z nagłówka => kolumna w bazie albo funkcja (zapytanie, kierunek).
     *
     * @return array<string, string|Closure(Builder, string): mixed>
     */
    abstract protected function sortableColumns(): array;

    /**
     * Kolumny, które za pierwszym kliknięciem sortują od największej (np. liczby wyświetleń, daty).
     *
     * @return list<string>
     */
    protected function sortDescendingFirst(): array
    {
        return [];
    }

    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, $this->sortableColumns())) {
            return;
        }

        $first = in_array($field, $this->sortDescendingFirst(), true) ? 'desc' : 'asc';

        if ($this->sortField !== $field) {
            [$this->sortField, $this->sortDirection] = [$field, $first];
        } elseif ($this->sortDirection === $first) {
            $this->sortDirection = $first === 'asc' ? 'desc' : 'asc';
        } else {
            [$this->sortField, $this->sortDirection] = ['', 'asc'];
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Nakłada wybrane sortowanie na zapytanie (zastępując domyślną kolejność); bez wyboru zostawia ją bez zmian.
     */
    protected function applySort(Builder $query): Builder
    {
        $column = $this->sortableColumns()[$this->sortField] ?? null;

        if ($column === null) {
            return $query;
        }

        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $query->reorder();

        if ($column instanceof Closure) {
            $column($query, $direction);
        } else {
            $query->orderBy($column, $direction);
        }

        // Stała kolejność przy równych wartościach, żeby wiersze nie skakały między stronami listy.
        return $query->orderBy($query->getModel()->getQualifiedKeyName());
    }
}
