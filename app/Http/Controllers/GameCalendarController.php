<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Response;

class GameCalendarController extends Controller
{
    /**
     * Plik .ics - jedno kliknięcie dodaje mecz do kalendarza w telefonie.
     */
    public function show(Game $game): Response
    {
        $game->load(['team', 'opponentTeam', 'competition']);

        $start = $game->game_date->copy()->utc();
        $title = 'Joker '.($game->team?->name ?? 'Piła').' vs '.$game->opponentShortName();
        $escape = fn (?string $text) => str_replace([',', ';', "\n"], ['\,', '\;', '\n'], (string) $text);

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Joker Pila//Terminarz//PL',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:mecz-'.$game->id.'@'.request()->getHost(),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$start->copy()->addHours(2)->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape('🏐 '.$title),
            'LOCATION:'.$escape($game->venue),
            'DESCRIPTION:'.$escape(trim(($game->competition?->name ?? '').' - '.route('schedule.index'), ' -')),
            'END:VEVENT',
            'END:VCALENDAR',
        ])."\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="mecz-'.$game->id.'.ics"',
        ]);
    }
}
