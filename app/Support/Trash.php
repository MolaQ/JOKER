<?php

namespace App\Support;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\Page;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TrashItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Kosz: zanim rekord zniknie z bazy, zapisujemy go razem ze wszystkim, co klucze obce usuwają kaskadowo
 * (np. konto z rolami i wiadomościami, turniej z kategoriami i meczami) oraz z odnośnikami, które baza wyzeruje.
 * Przywrócenie wstawia te wiersze z powrotem z tymi samymi numerami, więc linki i powiązania wracają.
 */
class Trash
{
    /**
     * Co trafia do kosza: klucz, model, nazwa i uprawnienie potrzebne do przywrócenia.
     *
     * @var array<string, array{0: class-string<Model>, 1: string, 2: string}>
     */
    public const TYPES = [
        'user' => [User::class, 'Konto', 'users.manage'],
        'player' => [Player::class, 'Zawodnik', 'teams.manage'],
        'team' => [Team::class, 'Drużyna', 'teams.manage'],
        'article' => [Article::class, 'Aktualność', 'articles.manage'],
        'game' => [Game::class, 'Mecz', 'games.manage'],
        'tournament' => [Tournament::class, 'Turniej', 'tournaments.manage'],
        'gallery' => [Gallery::class, 'Galeria', 'media.manage'],
        'page' => [Page::class, 'Podstrona', 'site.manage'],
    ];

    /** Tabele, których usuniętych wierszy nie zapisujemy (dzienniki, statystyki, sam kosz); wyzerowane odnośniki w nich wracają. */
    private const SKIP_TABLES = ['trash_items', 'audit_logs', 'page_views', 'sessions', 'jobs', 'failed_jobs', 'cache', 'cache_locks'];

    /** W trakcie przenoszenia do kosza pliki (np. zdjęcia galerii) zostają na dysku do ostatecznego usunięcia. */
    public static bool $keepFiles = false;

    /** @var array<string, list<array{table: string, columns: list<string>, foreign_columns: list<string>, on_delete: string}>>|null */
    private static ?array $references = null;

    public static function typeOf(Model $model): ?string
    {
        foreach (self::TYPES as $key => [$class]) {
            if ($model instanceof $class) {
                return $key;
            }
        }

        return null;
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type][1] ?? $type;
    }

    public static function permission(string $type): ?string
    {
        return self::TYPES[$type][2] ?? null;
    }

    /**
     * Przenosi rekord do kosza i usuwa go tak jak dotąd (zdarzenia modelu, dziennik zmian, klucze obce).
     */
    public static function delete(Model $model, ?string $label = null): TrashItem
    {
        $type = self::typeOf($model) ?? throw new RuntimeException('Tego rodzaju rekordu nie ma w koszu: '.$model::class);

        return DB::transaction(function () use ($model, $type, $label) {
            $item = TrashItem::create([
                'type' => $type,
                'item_id' => $model->getKey(),
                'label' => mb_substr($label ?? self::name($model), 0, 200),
                'payload' => self::capture($model->getTable(), $model->getKeyName(), $model->getKey()),
                'deleted_by' => Auth::id(),
            ]);

            self::$keepFiles = true;

            try {
                $model->delete();
            } finally {
                self::$keepFiles = false;
            }

            return $item;
        });
    }

    /**
     * Wstawia zapisane wiersze z powrotem i usuwa wpis z kosza.
     *
     * @throws RuntimeException gdy rekord nie może wrócić (np. adres albo e-mail jest już zajęty)
     */
    public static function restore(TrashItem $item): void
    {
        $payload = $item->payload;

        try {
            DB::transaction(function () use ($payload, $item) {
                foreach (self::insertOrder($payload['rows'] ?? []) as $table => $rows) {
                    foreach (array_chunk($rows, 200) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                }

                foreach ($payload['pointers'] ?? [] as [$table, $column, $value, $ids]) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                        foreach (array_chunk($ids, 500) as $chunk) {
                            DB::table($table)->whereIn('id', $chunk)->whereNull($column)->update([$column => $value]);
                        }
                    }
                }

                $item->delete();
            });
        } catch (QueryException $exception) {
            $duplicate = in_array($exception->errorInfo[0] ?? null, ['23000', '23505'], true) && str_contains(strtolower($exception->getMessage()), 'unique');

            throw new RuntimeException($duplicate
                ? 'Nie da się przywrócić: taki sam adres, e-mail albo numer ma już inny rekord. Zmień go w tamtym rekordzie i spróbuj ponownie.'
                : 'Nie da się przywrócić: '.str($exception->getMessage())->before(' (Connection')->limit(200), previous: $exception);
        }

        Sitemap::forget();
        SearchIndex::forget();

        $class = self::TYPES[$item->type][0] ?? null;
        AuditLog::record('trash.restored', 'Przywrócono z kosza: '.mb_strtolower(self::label($item->type)).' „'.$item->label.'”', $class ? $class::find($item->item_id) : null);
    }

    /**
     * Zapisuje wiersz i wszystkie wiersze, które baza usunie razem z nim (kaskadowo), oraz odnośniki, które wyzeruje.
     *
     * @return array{rows: array<string, list<array<string, mixed>>>, pointers: list<array{0: string, 1: string, 2: mixed, 3: list<int>}>}
     */
    public static function capture(string $table, string $key, mixed $id): array
    {
        $rows = [];
        $pointers = [];
        $seen = [];
        $queue = [[$table, DB::table($table)->where($key, $id)->get()->map(fn ($row) => (array) $row)->all()]];

        while ($queue) {
            [$current, $found] = array_shift($queue);

            foreach ($found as $row) {
                $mark = $current.':'.(array_key_exists('id', $row) ? $row['id'] : md5(json_encode($row)));

                if (isset($seen[$mark])) {
                    continue;
                }

                $seen[$mark] = true;
                $rows[$current][] = $row;

                foreach (self::references()[$current] ?? [] as $reference) {
                    if (count($reference['columns']) !== 1) {
                        continue;
                    }

                    $value = $row[$reference['foreign_columns'][0]] ?? null;

                    if ($value === null) {
                        continue;
                    }

                    $children = DB::table($reference['table'])->where($reference['columns'][0], $value);

                    if ($reference['on_delete'] === 'cascade' && ! in_array($reference['table'], self::SKIP_TABLES, true)) {
                        $queue[] = [$reference['table'], $children->get()->map(fn ($child) => (array) $child)->all()];
                    } elseif ($reference['on_delete'] === 'set null' && Schema::hasColumn($reference['table'], 'id')) {
                        $ids = $children->pluck('id')->map(fn ($id) => (int) $id)->all();

                        if ($ids) {
                            $pointers[] = [$reference['table'], $reference['columns'][0], $value, $ids];
                        }
                    }
                }
            }
        }

        return ['rows' => $rows, 'pointers' => $pointers];
    }

    /**
     * Pliki do usunięcia z dysku, gdy wpis znika z kosza na stałe (zdjęcia galerii i ich miniatury).
     *
     * @return list<string>
     */
    public static function files(array $payload): array
    {
        return collect($payload['rows']['gallery_photos'] ?? [])->pluck('path')->filter()
            ->flatMap(fn (string $path) => [$path, ImageOptimizer::thumbPath($path)])
            ->values()->all();
    }

    /**
     * Kolejność wstawiania: najpierw tabele, na które inne wskazują (rodzice), w tabeli rosnąco po numerze.
     *
     * @param  array<string, list<array<string, mixed>>>  $rows
     * @return array<string, list<array<string, mixed>>>
     */
    private static function insertOrder(array $rows): array
    {
        $parents = [];

        foreach (self::references() as $parent => $references) {
            foreach ($references as $reference) {
                if ($reference['table'] !== $parent && isset($rows[$parent], $rows[$reference['table']])) {
                    $parents[$reference['table']][$parent] = true;
                }
            }
        }

        $ordered = [];
        $visiting = [];
        $visit = function (string $table) use (&$visit, &$ordered, &$visiting, $parents, $rows) {
            if (isset($ordered[$table]) || isset($visiting[$table])) {
                return;
            }

            $visiting[$table] = true;

            foreach (array_keys($parents[$table] ?? []) as $parent) {
                $visit($parent);
            }

            $ordered[$table] = collect($rows[$table])->sortBy(fn (array $row) => $row['id'] ?? 0)->values()->all();
        };

        foreach (array_keys($rows) as $table) {
            $visit($table);
        }

        return $ordered;
    }

    /**
     * Mapa odwrotna kluczy obcych: tabela → kto na nią wskazuje i co baza robi przy usunięciu.
     *
     * @return array<string, list<array{table: string, columns: list<string>, foreign_columns: list<string>, on_delete: string}>>
     */
    private static function references(): array
    {
        if (self::$references !== null) {
            return self::$references;
        }

        $map = [];

        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            foreach (Schema::getForeignKeys($table) as $key) {
                $map[$key['foreign_table']][] = [
                    'table' => $table,
                    'columns' => $key['columns'],
                    'foreign_columns' => $key['foreign_columns'],
                    'on_delete' => strtolower((string) $key['on_delete']),
                ];
            }
        }

        return self::$references = $map;
    }

    private static function name(Model $model): string
    {
        $name = match (true) {
            $model instanceof Player => $model->full_name,
            $model instanceof Game => trim(($model->team?->name ?? '').' – '.$model->opponentDisplayName().' '.$model->game_date?->format('d.m.Y')),
            $model instanceof User => $model->name.' ('.$model->email.')',
            default => $model->getAttribute('title') ?? $model->getAttribute('name'),
        };

        return trim(strip_tags((string) $name)) ?: '#'.$model->getKey();
    }
}
