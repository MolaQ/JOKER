<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LikeButton extends Component
{
    public string $type;

    public int $id;

    public int $likesCount = 0;

    public bool $liked = false;

    public function mount(string $type, int $id): void
    {
        $this->type = $type;
        $this->id = $id;
        $this->refreshState();
    }

    public function toggle(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $model = $this->resolveModel();

        if (! $model) {
            return;
        }

        $model->toggleLikeFor($user);
        $this->refreshState();
    }

    private function refreshState(): void
    {
        $model = $this->resolveModel();

        if (! $model) {
            $this->likesCount = 0;
            $this->liked = false;

            return;
        }

        $this->likesCount = $model->likes()->count();
        $this->liked = $model->isLikedBy($this->authUser());
    }

    private function authUser(): ?Authenticatable
    {
        return Auth::user();
    }

    private function resolveModel(): ?Model
    {
        return match ($this->type) {
            'article' => Article::find($this->id),
            'game' => Game::find($this->id),
            'player' => Player::find($this->id),
            'team' => Team::find($this->id),
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.like-button');
    }
}
