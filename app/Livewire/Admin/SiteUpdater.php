<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Support\SiteUpdate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use RuntimeException;

/**
 * Panel „Aktualizacja strony”: wgranie paczki joker-kod.zip, jej sprawdzenie i zatwierdzenie instalacji hasłem.
 * Samą instalację robi cron po znalezieniu pliku flagi; panel pokazuje jej przebieg.
 */
class SiteUpdater extends Component
{
    use ShowsToasts;

    public string $password = '';

    /** Zgoda na wgranie paczki starszej niż zainstalowana. */
    public bool $allowOlder = false;

    public function install(): void
    {
        $package = SiteUpdate::package();

        if ($package && $package['starsza'] && ! $this->allowOlder) {
            $this->addError('allowOlder', 'Ta paczka jest starsza niż zainstalowana. Zaznacz zgodę, jeśli naprawdę chcesz ją wgrać.');

            return;
        }

        $this->confirmed(fn () => SiteUpdate::requestInstall(Auth::user()), 'Instalacja zatwierdzona. Ruszy przy najbliższym uruchomieniu crona.');
    }

    public function rollback(): void
    {
        $this->confirmed(fn () => SiteUpdate::requestRollback(Auth::user()), 'Przywrócenie poprzedniej wersji zlecone. Ruszy przy najbliższym uruchomieniu crona.');
    }

    public function cancel(): void
    {
        Gate::authorize('deploy');

        try {
            SiteUpdate::cancel();
            $this->toast('Paczka usunięta.');
        } catch (RuntimeException $error) {
            $this->toast($error->getMessage(), 'error');
        }
    }

    /**
     * Operacje zmieniające kod strony wymagają ponownego podania hasła.
     */
    private function confirmed(callable $action, string $done): void
    {
        Gate::authorize('deploy');

        if (! Hash::check($this->password, (string) Auth::user()->password)) {
            $this->addError('password', 'Hasło się nie zgadza.');

            return;
        }

        try {
            $action();
            $this->toast($done);
        } catch (RuntimeException $error) {
            $this->toast($error->getMessage(), 'error');
        }

        $this->reset('password', 'allowOlder');
    }

    public function render(): View
    {
        Gate::authorize('deploy');

        $minutes = 5 - (now()->minute % 5);

        return view('livewire.admin.site-updater', [
            'installed' => SiteUpdate::installedVersion(),
            'package' => SiteUpdate::package(),
            'flag' => SiteUpdate::flag(),
            'locked' => SiteUpdate::locked(),
            'status' => SiteUpdate::status(),
            'log' => SiteUpdate::logTail(),
            'canRollback' => is_file(SiteUpdate::rollbackPath()),
            'cronIn' => $minutes,
            'chunkBytes' => SiteUpdate::CHUNK_BYTES,
        ]);
    }
}
