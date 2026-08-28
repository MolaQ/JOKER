<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Season;

class PlayerController extends Controller
{
    public function show(Player $player)
    {
        $player->load(['team', 'likes']);

        $currentSeasonId = Season::current()->value('id');
        $seasonTeams = $currentSeasonId
            ? $player->teamsForSeason($currentSeasonId)->get()
            : collect([$player->team]);

        return view('players.show', compact('player', 'seasonTeams'));
    }
}
