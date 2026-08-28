<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public function render()
    {
        $q = trim($this->query);

        if ($q === '') {
            return view('livewire.global-search', [
                'teams' => collect(),
                'players' => collect(),
                'articles' => collect(),
                'games' => collect(),
            ]);
        }

        return view('livewire.global-search', [
            'teams' => Team::where('name', 'like', "%{$q}%")->limit(5)->get(),
            'players' => Player::where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->limit(5)
                ->get(),
            'articles' => Article::published()->where('title', 'like', "%{$q}%")->limit(5)->get(),
            'games' => Game::with('team')->where('opponent', 'like', "%{$q}%")->limit(5)->get(),
        ]);
    }
}
