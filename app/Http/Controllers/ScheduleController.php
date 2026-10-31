<?php

namespace App\Http\Controllers;

use App\Models\Season;

class ScheduleController extends Controller
{
    public function index()
    {
        return view('schedule.index', ['currentSeason' => Season::current()->first()]);
    }

    public function standings()
    {
        return view('schedule.standings', ['currentSeason' => Season::current()->first()]);
    }
}
