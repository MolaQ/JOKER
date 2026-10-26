<?php

namespace App\Support;

use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Import drużyn, sztabu i zawodników z plików tekstowych (CSV z Excela: separator ; , albo tabulator,
 * kodowanie UTF-8 albo Windows-1250). Pierwszy wiersz to nagłówki, kolejność kolumn dowolna.
 */
class DataImport
{
    public const TYPES = [
        'teams' => 'Drużyny',
        'staff' => 'Trenerzy i sztab',
        'players' => 'Zawodnicy',
    ];

    /**
     * Kolumny każdego rodzaju: klucz => [nagłówek we wzorze, wymagana, przykład].
     *
     * @var array<string, array<string, array{0: string, 1: bool, 2: string}>>
     */
    public const COLUMNS = [
        'teams' => [
            'name' => ['nazwa', true, 'Kadeci'],
            'category' => ['kategoria', true, 'juniorzy młodsi'],
            'birth_year' => ['rocznik', false, '2010'],
            'description' => ['opis', false, 'Pierwsza drużyna kadetów.'],
            'display_order' => ['kolejnosc', false, '4'],
        ],
        'staff' => [
            'name' => ['imie_nazwisko', true, 'Jan Kowalski'],
            'email' => ['email', false, 'jan.kowalski@example.com'],
            'role' => ['rola', false, 'trener'],
            'teams' => ['druzyny', false, 'Kadeci, Kadeci II'],
            'function' => ['funkcja', false, 'trener główny'],
            'bio' => ['opis', false, 'Trener z licencją II klasy.'],
        ],
        'players' => [
            'first_name' => ['imie', true, 'Adam'],
            'last_name' => ['nazwisko', true, 'Nowak'],
            'team' => ['druzyna', false, 'Kadeci'],
            'jersey_number' => ['numer', false, '7'],
            'position' => ['pozycja', false, 'przyjmujący'],
            'captain' => ['kapitan', false, 'nie'],
            'birth_date' => ['data_urodzenia', false, '2010-05-14'],
            'height' => ['wzrost', false, '188'],
            'weight' => ['waga', false, '75'],
            'spike_reach' => ['zasieg_atak', false, '320'],
            'block_reach' => ['zasieg_blok', false, '305'],
            'email' => ['email', false, ''],
            'guardians' => ['rodzice', false, 'Anna Nowak <anna.nowak@example.com>, piotr.nowak@example.com'],
            'bio' => ['opis', false, 'Gra w siatkówkę od 8. roku życia.'],
            'interests' => ['zainteresowania', false, 'gitara, góry, matematyka'],
        ],
    ];

    private const CATEGORIES = [
        'senior' => ['senior', 'seniorzy', 'seniorki'],
        'junior' => ['junior', 'juniorzy', 'juniorki'],
        'junior_mlodszy' => ['junior_mlodszy', 'juniorzy mlodsi', 'juniorki mlodsze', 'kadeci', 'kadetki'],
        'mlodzik' => ['mlodzik', 'mlodzicy', 'mlodziczki', 'mini'],
    ];

    private const POSITIONS = [
        'setter' => ['setter', 'rozgrywajacy', 'rozgrywajaca', 'r'],
        'outside_hitter' => ['outside_hitter', 'przyjmujacy', 'przyjmujaca', 'p'],
        'opposite' => ['opposite', 'atakujacy', 'atakujaca', 'a'],
        'middle_blocker' => ['middle_blocker', 'srodkowy', 'srodkowa', 's'],
        'libero' => ['libero', 'l'],
    ];

    /**
     * Rodzaj danych rozpoznany po nagłówkach (np. imie + nazwisko = zawodnicy), albo null.
     */
    public static function detectType(string $content): ?string
    {
        $first = strtok(self::toUtf8($content), "\r\n");

        if ($first === false) {
            return null;
        }

        $delimiter = collect([';', "\t", ','])->sortByDesc(fn (string $candidate) => substr_count($first, $candidate))->first();
        $headers = array_map(fn (?string $header) => self::normalize((string) $header), str_getcsv($first, $delimiter, '"', ''));

        return collect(self::COLUMNS)
            ->filter(fn (array $columns) => collect($columns)->filter(fn (array $column) => $column[1])->every(fn (array $column) => in_array(self::normalize($column[0]), $headers, true)))
            ->keys()
            ->first();
    }

    /**
     * Wzór pliku: nagłówki i przykładowy wiersz, z BOM, żeby Excel otworzył polskie znaki.
     */
    public static function template(string $type): string
    {
        $columns = self::COLUMNS[$type];

        return "\u{FEFF}".implode(';', array_column($columns, 0))."\r\n".implode(';', array_column($columns, 2))."\r\n";
    }

    /**
     * Wiersze pliku z polami po kluczach, opisem i listą błędów (wiersz z błędem nie zostanie zaimportowany).
     *
     * @return list<array{line: int, data: array<string, string>, errors: list<string>, warnings: list<string>, action: string}>
     */
    public static function parse(string $type, string $content): array
    {
        $content = self::toUtf8($content);
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $content), fn (string $line) => trim($line) !== ''));

        if ($lines === []) {
            return [];
        }

        $delimiter = collect([';', "\t", ','])->sortByDesc(fn (string $candidate) => substr_count($lines[0], $candidate))->first();
        $headers = array_map(fn (?string $header) => self::normalize((string) $header), str_getcsv(array_shift($lines), $delimiter, '"', ''));
        $columns = collect(self::COLUMNS[$type])->map(fn (array $column) => array_search(self::normalize($column[0]), $headers, true));
        $missing = $columns->filter(fn ($index, string $key) => $index === false && self::COLUMNS[$type][$key][1])->keys();

        $rows = [];

        foreach ($lines as $number => $line) {
            $cells = str_getcsv($line, $delimiter, '"', '');
            $data = $columns->map(fn ($index) => $index === false ? '' : trim((string) ($cells[$index] ?? '')))->all();
            $errors = $missing->map(fn (string $key) => 'brak kolumny „'.self::COLUMNS[$type][$key][0].'”')->all();

            foreach (self::COLUMNS[$type] as $key => [$header, $required]) {
                if ($required && ! $missing->contains($key) && $data[$key] === '') {
                    $errors[] = 'puste pole „'.$header.'”';
                }
            }

            $rows[] = ['line' => $number + 2, 'data' => $data, 'errors' => [...$errors, ...self::validate($type, $data)], 'warnings' => [], 'action' => self::action($type, $data)];
        }

        return $type === 'players' ? self::markDuplicateNumbers($rows) : $rows;
    }

    /**
     * Zapisuje poprawne wiersze; istniejące rekordy (ta sama nazwa / e-mail / zawodnik w drużynie) są aktualizowane.
     *
     * @param  list<array{line: int, data: array<string, string>, errors: list<string>, action: string}>  $rows
     */
    public static function import(string $type, array $rows, ?array &$failed = null): int
    {
        $valid = array_values(array_filter($rows, fn (array $row) => $row['errors'] === []));
        $failed = [];
        $imported = 0;

        // Każdy wiersz osobno: błąd bazy w jednym wierszu (np. zajęty numer) nie wywraca całego importu.
        foreach ($valid as $row) {
            try {
                DB::transaction(fn () => match ($type) {
                    'teams' => self::importTeam($row['data']),
                    'staff' => self::importStaff($row['data']),
                    'players' => self::importPlayer($row['data']),
                });
                $imported++;
            } catch (QueryException $exception) {
                report($exception);
                $failed[$row['line']] = str_contains($exception->getMessage(), 'jersey_number')
                    ? 'ten numer ma już inny zawodnik tej drużyny'
                    : 'nie udało się zapisać wiersza (błąd bazy danych)';
            }
        }

        return $imported;
    }

    /**
     * Ten sam numer dwa razy w jednej drużynie w pliku: drugi wiersz dostaje błąd.
     *
     * @param  list<array{line: int, data: array<string, string>, errors: list<string>, warnings: list<string>, action: string}>  $rows
     * @return list<array{line: int, data: array<string, string>, errors: list<string>, warnings: list<string>, action: string}>
     */
    private static function markDuplicateNumbers(array $rows): array
    {
        $seen = [];

        foreach ($rows as $index => $row) {
            $data = $row['data'];

            if ($data['jersey_number'] === '' || ! ($team = self::team($data['team']))) {
                continue;
            }

            $key = $team->id.'-'.(int) $data['jersey_number'];
            $name = self::normalize($data['first_name'].' '.$data['last_name']);

            if (isset($seen[$key]) && $seen[$key]['name'] !== $name) {
                $rows[$index]['errors'][] = 'numer '.$data['jersey_number'].' w drużynie '.$team->name.' jest już w wierszu '.$seen[$key]['line'];
            } else {
                $seen[$key] ??= ['name' => $name, 'line' => $row['line']];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $data
     * @return list<string>
     */
    private static function validate(string $type, array $data): array
    {
        $errors = [];
        $number = fn (string $key, int $min, int $max) => $data[$key] !== '' && (! ctype_digit($data[$key]) || (int) $data[$key] < $min || (int) $data[$key] > $max);

        if ($type === 'teams') {
            if ($data['category'] !== '' && self::category($data['category']) === null) {
                $errors[] = 'nieznana kategoria „'.$data['category'].'” (seniorzy, juniorzy, juniorzy młodsi, młodzicy)';
            }
            if ($number('birth_year', 1990, 2030)) {
                $errors[] = 'rocznik musi być liczbą, np. 2010';
            }
        }

        if ($type === 'staff') {
            if ($data['email'] !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'błędny e-mail';
            }
            foreach (self::teamNames($data['teams']) as $name) {
                if (! self::team($name)) {
                    $errors[] = 'nie ma drużyny „'.$name.'” (najpierw zaimportuj drużyny)';
                }
            }
        }

        if ($type === 'players') {
            if ($data['team'] !== '' && ! self::team($data['team'])) {
                $errors[] = 'nie ma drużyny „'.$data['team'].'” (najpierw zaimportuj drużyny)';
            }
            if ($data['position'] !== '' && self::position($data['position']) === null) {
                $errors[] = 'nieznana pozycja „'.$data['position'].'” (rozgrywający, przyjmujący, atakujący, środkowy, libero)';
            }
            if ($number('jersey_number', 0, 99)) {
                $errors[] = 'numer musi być liczbą 0-99';
            } elseif ($data['jersey_number'] !== '' && ($taken = self::numberTakenBy($data))) {
                $errors[] = 'numer '.$data['jersey_number'].' w drużynie '.$data['team'].' ma już '.$taken;
            }
            if ($data['captain'] !== '' && self::flag($data['captain']) === null) {
                $errors[] = 'kapitan: wpisz tak albo nie';
            }
            if ($data['email'] !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'błędny e-mail zawodnika';
            }
            foreach (ClubAccounts::parseContacts($data['guardians']) as $contact) {
                if (! filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'błędny e-mail rodzica „'.$contact['email'].'”';
                }
            }
            if ($data['birth_date'] !== '' && self::date($data['birth_date']) === null) {
                $errors[] = 'data urodzenia w formacie 2010-05-14 albo 14.05.2010';
            }
            foreach (['height' => 'wzrost', 'weight' => 'waga', 'spike_reach' => 'zasięg w ataku', 'block_reach' => 'zasięg w bloku'] as $key => $label) {
                if ($number($key, 1, 400)) {
                    $errors[] = $label.' musi być liczbą (cm / kg)';
                }
            }
        }

        return $errors;
    }

    /**
     * Kto inny w tej drużynie nosi już ten numer (numer w drużynie musi być unikalny), albo null.
     *
     * @param  array<string, string>  $data
     */
    private static function numberTakenBy(array $data): ?string
    {
        if (! ctype_digit($data['jersey_number']) || ! ($team = self::team($data['team']))) {
            return null;
        }

        $name = self::normalize($data['first_name'].' '.$data['last_name']);

        return Player::where('team_id', $team->id)->where('jersey_number', (int) $data['jersey_number'])->get()
            ->reject(fn (Player $player) => self::normalize($player->first_name.' '.$player->last_name) === $name)
            ->map->full_name
            ->first();
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function action(string $type, array $data): string
    {
        $exists = match ($type) {
            'teams' => self::team($data['name']) !== null,
            'staff' => self::staffMember($data) !== null,
            'players' => self::player($data) !== null,
        };

        return $exists ? 'aktualizacja' : 'nowy';
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function importTeam(array $data): void
    {
        $values = array_filter([
            'category' => self::category($data['category']),
            'birth_year' => $data['birth_year'] !== '' ? (int) $data['birth_year'] : null,
            'description' => $data['description'] ?: null,
            'display_order' => $data['display_order'] !== '' ? (int) $data['display_order'] : null,
        ], fn ($value) => $value !== null);

        if ($team = self::team($data['name'])) {
            $team->update($values);

            return;
        }

        Team::create($values + [
            'name' => $data['name'],
            'slug' => Slug::unique(Team::class, $data['name']),
            'display_order' => (int) Team::max('display_order') + 1,
        ]);
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function importStaff(array $data): void
    {
        $role = in_array(self::normalize($data['role']), ['sztab', 'staff', 'czlonek sztabu', 'kierownik', 'fizjoterapeuta'], true) ? UserRole::Staff : UserRole::Trainer;
        $user = self::staffMember($data);
        $values = array_filter(['email' => $data['email'] ?: null, 'additional_info' => $data['bio'] ? '<p>'.e($data['bio']).'</p>' : null], fn ($value) => $value !== null);

        if ($user) {
            $user->update($values);
            $user->assignRole($role);
        } else {
            $user = User::create($values + [
                'name' => $data['name'],
                // Bez e-maila osoba dostaje adres zastępczy; prawdziwy wpiszesz w panelu, gdy będzie potrzebne konto.
                'email' => $data['email'] ?: Str::slug($data['name'], '.').'.'.Str::lower(Str::random(4)).'@brak-emaila.jokerpila.pl',
                'password' => Hash::make(Str::random(32)),
                'role' => $role,
            ]);
        }

        $head = in_array(self::normalize($data['function']), ['trener glowny', 'trenerka glowna', 'pierwszy trener', 'glowny'], true);

        foreach (self::teamNames($data['teams']) as $name) {
            $team = self::team($name);

            if ($head) {
                $team->update(['trainer_id' => $user->id]);
                $team->assistantTrainers()->detach($user->id);
            } else {
                $team->assistantTrainers()->syncWithoutDetaching([$user->id => ['role' => $data['function'] ?: null]]);
            }
        }
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function importPlayer(array $data): void
    {
        $team = self::team($data['team']);
        // Puste pola nie kasują tego, co już jest w bazie: uzupełnisz je później w panelu.
        $values = ['first_name' => $data['first_name'], 'last_name' => $data['last_name']] + array_filter([
            'team_id' => $team?->id,
            'jersey_number' => $data['jersey_number'] !== '' ? (int) $data['jersey_number'] : null,
            'position' => self::position($data['position']),
            'birth_date' => self::date($data['birth_date']),
            'height' => $data['height'] !== '' ? (int) $data['height'] : null,
            'weight' => $data['weight'] !== '' ? (int) $data['weight'] : null,
            'spike_reach' => $data['spike_reach'] !== '' ? (int) $data['spike_reach'] : null,
            'block_reach' => $data['block_reach'] !== '' ? (int) $data['block_reach'] : null,
            'bio' => $data['bio'] !== '' ? '<p>'.e($data['bio']).'</p>' : null,
            'interests' => $data['interests'] ?: null,
        ], fn ($value) => $value !== null);

        $player = self::player($data);
        $previousTeamId = $player?->team_id;

        if ($player) {
            $player->update($values);
        } else {
            $player = Player::create($values + ['is_active' => true]);
        }

        if ($previousTeamId && $previousTeamId !== $player->team_id && ($seasonId = Season::current()->value('id'))) {
            $player->teams()->wherePivot('season_id', $seasonId)->detach($previousTeamId);
        }

        $player->syncHomeRoster($data['captain'] !== '' ? self::flag($data['captain']) : null);

        if ($data['email'] !== '') {
            ClubAccounts::connectPlayer($player, $data['email']);
        }

        foreach (ClubAccounts::parseContacts($data['guardians']) as $contact) {
            ClubAccounts::connectGuardian($player, $contact['email'], $contact['name']);
        }
    }

    private static function team(string $name): ?Team
    {
        $key = self::normalize($name);

        return $key === '' ? null : Team::all()->first(fn (Team $team) => self::normalize($team->name) === $key);
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function player(array $data): ?Player
    {
        $name = self::normalize($data['first_name'].' '.$data['last_name']);
        $matches = Player::all()
            ->filter(fn (Player $player) => self::normalize($player->first_name.' '.$player->last_name) === $name);
        $team = self::team($data['team']);

        // Ta sama osoba w podanej drużynie, a bez drużyny (albo przy przenosinach) - jedyna osoba o tym imieniu i nazwisku.
        return ($team ? $matches->firstWhere('team_id', $team->id) : null) ?? ($matches->count() === 1 ? $matches->first() : null);
    }

    private static function flag(string $value): ?bool
    {
        return match (self::normalize($value)) {
            'tak', 't', '1', 'x', 'kapitan', 'k', 'c' => true,
            'nie', 'n', '0', '-' => false,
            default => null,
        };
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function staffMember(array $data): ?User
    {
        if ($data['email'] !== '' && ($user = User::firstWhere('email', $data['email']))) {
            return $user;
        }

        return User::withRole(UserRole::Trainer, UserRole::Staff, UserRole::Boss, UserRole::Admin)->get()
            ->first(fn (User $user) => self::normalize($user->name) === self::normalize($data['name']));
    }

    /**
     * @return list<string>
     */
    private static function teamNames(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,|]/', $value))));
    }

    private static function category(string $value): ?string
    {
        return collect(self::CATEGORIES)->search(fn (array $aliases) => in_array(self::normalize($value), $aliases, true)) ?: null;
    }

    private static function position(string $value): ?string
    {
        return collect(self::POSITIONS)->search(fn (array $aliases) => in_array(self::normalize($value), $aliases, true)) ?: null;
    }

    private static function date(string $value): ?string
    {
        foreach (['Y-m-d', 'd.m.Y', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($date && $date->format($format) === $value) {
                return $date->toDateString();
            }
        }

        return null;
    }

    /**
     * Małe litery bez polskich znaków i nadmiarowych spacji, żeby „Środkowy” = „srodkowy”, a „Kadeci II” = „kadeci  ii”.
     */
    private static function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', Str::lower(Str::ascii(str_replace("\u{FEFF}", '', $value)))));
    }

    public static function toUtf8(string $content): string
    {
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        // Excel w polskim Windows zapisuje CSV w Windows-1250 (mbstring go nie zna, iconv tak).
        $converted = function_exists('iconv') ? @iconv('WINDOWS-1250', 'UTF-8', $content) : false;

        return $converted !== false ? $converted : mb_convert_encoding($content, 'UTF-8', 'ISO-8859-2');
    }
}
