<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\Page;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Serduszko z liczbą polubień. Klikać mogą tylko zalogowani, goście widzą samą liczbę.
 */
class LikeButton extends Component
{
    /** @var array<string, class-string<Model>> */
    public const TYPES = [
        'article' => Article::class,
        'game' => Game::class,
        'player' => Player::class,
        'team' => Team::class,
        'page' => Page::class,
        'tournament' => Tournament::class,
        'tournament_match' => TournamentMatch::class,
        'gallery' => Gallery::class,
        'trainer' => User::class,
        'person' => User::class,
    ];

    #[Locked]
    public string $type;

    #[Locked]
    public int $id;

    public int $likesCount = 0;

    public bool $liked = false;

    /** Ciemne tło (nagłówki ze zdjęciem, galeria na pełnym ekranie). */
    public bool $dark = false;

    public function mount(string $type, int $id, bool $dark = false): void
    {
        $this->type = $type;
        $this->id = $id;
        $this->dark = $dark;
        $this->refreshState();
    }

    public function toggle(): void
    {
        $user = Auth::user();
        $model = $this->resolveModel();

        if (! $user?->is_active || ! $model) {
            return;
        }

        $model->toggleLikeFor($user);
        $this->refreshState();

        // Ta sama treść może mieć kilka serduszek na stronie (np. galeria i jej podgląd).
        $this->dispatch('like-toggled', type: $this->type, id: $this->id);
    }

    #[On('like-toggled')]
    public function syncWith(string $type, int $id): void
    {
        if ($type === $this->type && $id === $this->id) {
            $this->refreshState();
        }
    }

    private function refreshState(): void
    {
        $model = $this->resolveModel();

        $this->likesCount = $model ? $model->likesRelation()->count() : 0;
        $this->liked = $model ? $model->isLikedBy(Auth::user()) : false;
    }

    private function resolveModel(): ?Model
    {
        $class = self::TYPES[$this->type] ?? null;

        return $class ? $class::find($this->id) : null;
    }

    public function render(): View
    {
        return view('livewire.like-button');
    }
}
