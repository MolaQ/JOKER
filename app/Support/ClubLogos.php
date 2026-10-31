<?php

namespace App\Support;

use App\Models\Game;
use App\Models\RivalClub;
use App\Models\RivalTeam;
use App\Models\Team;
use App\Models\TournamentParticipant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Logotypy wszystkich klubów w systemie (nasz klub, nasze drużyny z własnym logo, kluby i drużyny rywali)
 * do pobrania na stronie /logotypy, np. do grafik transmisji.
 */
class ClubLogos
{
    /**
     * @return Collection<int, array{key: string, name: string, group: string, url: string, file: string, extension: string, downloads: int, meetings: int}>
     */
    public static function all(): Collection
    {
        $downloads = DB::table('logo_downloads')->pluck('downloads', 'logo_key');
        $games = Game::whereNotNull('opponent_team_id')->selectRaw('opponent_team_id, count(*) as total')->groupBy('opponent_team_id')->pluck('total', 'opponent_team_id');
        $tournaments = TournamentParticipant::where('competitor_type', 'rival_team')->selectRaw('competitor_id, count(*) as total')->groupBy('competitor_id')->pluck('total', 'competitor_id');
        $meetings = fn (int $teamId) => (int) ($games[$teamId] ?? 0) + (int) ($tournaments[$teamId] ?? 0);

        $club = app(SiteSettings::class);
        $items = collect();

        $items->push(self::item('klub', $club['club_name'] ?? config('app.name'), __('Nasz klub'), $club->get('logo') ?: null, public_path('img/logo-512.png')));

        foreach (Team::whereNotNull('logo_path')->orderBy('display_order')->get(['id', 'name', 'logo_path']) as $team) {
            $items->push(self::item('druzyna-'.$team->id, $team->name, __('Nasz klub'), $team->logo_path));
        }

        foreach (RivalClub::whereNotNull('logo_path')->with('teams:id,rival_club_id')->orderBy('name')->get(['id', 'name', 'logo_path']) as $rival) {
            $items->push(self::item('klub-'.$rival->id, $rival->name, __('Kluby'), $rival->logo_path, meetings: $rival->teams->sum(fn (RivalTeam $team) => $meetings($team->id))));
        }

        foreach (RivalTeam::whereNotNull('logo_path')->orderBy('full_name')->get(['id', 'full_name', 'short_name', 'logo_path']) as $rival) {
            $items->push(self::item('rywal-'.$rival->id, $rival->full_name, __('Kluby'), $rival->logo_path, meetings: $meetings($rival->id)));
        }

        // To samo logo (np. drużyna z logo klubu) pokazujemy raz.
        return $items->filter()->unique('file')
            ->map(fn (array $logo) => $logo + ['downloads' => (int) ($downloads[$logo['key']] ?? 0)])
            ->values();
    }

    /**
     * Liczy pobranie logo (do sortowania „najpopularniejsze”).
     */
    public static function countDownload(string $key): void
    {
        $updated = DB::table('logo_downloads')->where('logo_key', $key)->increment('downloads', 1, ['updated_at' => now()]);

        if ($updated === 0) {
            DB::table('logo_downloads')->insertOrIgnore(['logo_key' => $key, 'downloads' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Logotypy z pozycjami do sortowania: najpopularniejsze (pobrania, potem spotkania z nami) i po nazwie.
     */
    public static function ranked(): Collection
    {
        $logos = self::all();
        // Polska kolejność liter (Ł po L, Ś po S), gdy serwer ma rozszerzenie intl.
        $compare = class_exists(\Collator::class)
            ? fn (array $a, array $b) => (new \Collator('pl_PL'))->compare($a['name'], $b['name'])
            : fn (array $a, array $b) => strcasecmp($a['name'], $b['name']);
        $popular = $logos->sortBy([['downloads', 'desc'], ['meetings', 'desc'], $compare])->pluck('key')->flip();
        $byName = $logos->sort($compare)->pluck('key')->flip();

        return $logos->map(fn (array $logo) => $logo + ['popular_rank' => $popular[$logo['key']], 'name_rank' => $byName[$logo['key']]]);
    }

    public static function find(string $key): ?array
    {
        return self::all()->firstWhere('key', $key);
    }

    public static function fileName(array $logo): string
    {
        return (Str::slug($logo['name']) ?: $logo['key']).'.'.$logo['extension'];
    }

    /**
     * @return array{key: string, name: string, group: string, url: string, file: string, extension: string}|null
     */
    private static function item(string $key, string $name, string $group, ?string $path, ?string $fallback = null, int $meetings = 0): ?array
    {
        $local = $path && ! Str::startsWith($path, ['http://', 'https://']) ? Storage::disk('public')->path(ltrim($path, '/')) : null;
        $file = $local && is_file($local) ? $local : $fallback;

        if (! $file || ! is_file($file)) {
            return null;
        }

        return [
            'key' => $key,
            'name' => trim($name),
            'group' => $group,
            'url' => $file === $fallback ? asset(Str::after($fallback, public_path().DIRECTORY_SEPARATOR)) : Media::url($path),
            'file' => $file,
            'extension' => strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'png',
            'meetings' => $meetings,
        ];
    }
}
