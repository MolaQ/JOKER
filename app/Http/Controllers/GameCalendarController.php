<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Support\SiteSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class GameCalendarController extends Controller
{
    /**
     * Plik .ics - jedno kliknięcie dodaje mecz do kalendarza w telefonie.
     */
    public function show(Game $game): Response
    {
        $game->load(['team', 'opponentTeam', 'competition']);

        $start = $game->game_date->copy()->utc();
        $title = app(SiteSettings::class)['club_short_name'].' '.$game->team?->name.' vs '.$game->opponentShortName();
        $escape = fn (?string $text) => str_replace([',', ';', "\n"], ['\,', '\;', '\n'], (string) $text);

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.Str::ascii(app(SiteSettings::class)['club_name']).'//Terminarz//PL',
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
