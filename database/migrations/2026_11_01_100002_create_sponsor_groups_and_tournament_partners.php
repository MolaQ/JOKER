<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Blok „Wspierają nas” nad stopką: kategorie logotypów ustawiane w panelu (nazwa, kolejność, wielkość).
        Schema::hasTable('sponsor_groups') || Schema::create('sponsor_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('size', 10)->default('md');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::table('sponsors', function (Blueprint $table) {
            if (! Schema::hasColumn('sponsors', 'sponsor_group_id')) {
                $table->foreignId('sponsor_group_id')->nullable()->after('id')->constrained('sponsor_groups')->nullOnDelete();
                $table->string('tile_color', 9)->nullable()->after('logo_path');
            }
        });

        // Partnerzy turnieju (strona turnieju i ekran TV) oraz tekst „O turnieju”.
        Schema::hasTable('tournament_sponsor') || Schema::create('tournament_sponsor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_main')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['tournament_id', 'sponsor_id']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            if (! Schema::hasColumn('tournaments', 'about')) {
                $table->longText('about')->nullable()->after('description');
            }
        });

        $this->seedClubSponsors();
    }

    /**
     * Kategorie i logotypy z projektu stopki sponsorskiej klubu (tylko na stronie, która już działa).
     */
    private function seedClubSponsors(): void
    {
        if (DB::table('sponsor_groups')->exists() || ! DB::table('pages')->where('slug', 'o-klubie')->exists()) {
            return;
        }

        $groups = [
            ['Instytucje i samorządy', 'xl', [
                ['Ministerstwo Sportu i Turystyki', 'msit', 'https://www.gov.pl/web/sport'],
                ['Samorząd Województwa Wielkopolskiego', 'wielkopolska', 'https://www.umww.pl/'],
                ['Powiat Pilski', 'powiat', 'https://www.powiat.pila.pl/'],
                ['Miasto Piła', 'pila', 'https://www.pila.pl/'],
            ]],
            ['Instytucje i organizacje współpracujące', 'lg', [
                ['LO Mistrzostwa Sportowego w Pile', 'loms', 'https://lo-ms.pila.pl/'],
                ['Fundacja LOTTO im. Haliny Konopackiej', 'lotto', 'https://www.fundacjalotto.pl/'],
                ['Fundacja Siatkarska Polska', 'fsp', null],
                ['LZS – operator Rządowego Programu KLUB', 'lzs', null],
                ['SOS Młodzieżowa Siatkówka', 'sos', null],
                ['WZPS Poznań', 'wzps', null],
                ['Polska Siatkówka', 'polska-siatkowka', null, false],
            ]],
            ['Programy i projekty', 'md', [
                ['KLUB PRO', 'klub-pro', '/page/klub-pro-2026', true, '#1e2a8a'],
                ['Certyfikacja Akademii Siatkarskich', 'cas', null],
                ['Rządowy Program KLUB – Witaj w Klubie', 'witaj', null],
            ]],
            ['Sponsorzy, partnerzy i przyjaciele Klubu', 'md', [
                ['PlanetHerbs', 'planetherbs', null],
                ['Piko Sport', 'piko', null],
                ['Przyjaciele Pilskiego Jokera', 'sppj', null],
            ]],
        ];

        $existing = DB::table('sponsors')->get(['id', 'name']);
        $key = fn (string $name) => Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '')->value();

        foreach ($groups as $groupOrder => [$groupName, $size, $logos]) {
            $groupId = DB::table('sponsor_groups')->insertGetId([
                'name' => $groupName, 'size' => $size, 'display_order' => $groupOrder + 1, 'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($logos as $order => $logo) {
                [$name, $file, $url] = $logo;
                $match = $existing->first(fn ($sponsor) => $key($sponsor->name) !== '' && (str_contains($key($sponsor->name), $key($name)) || str_contains($key($name), $key($sponsor->name))));
                $values = [
                    'sponsor_group_id' => $groupId,
                    'logo_path' => '/img/sponsorzy/'.$file.'.png',
                    'tile_color' => $logo[4] ?? null,
                    'display_order' => $order + 1,
                    'updated_at' => now(),
                ];

                if ($match) {
                    DB::table('sponsors')->where('id', $match->id)->update($values);
                } else {
                    DB::table('sponsors')->insert($values + [
                        'name' => $name, 'website_url' => $url, 'tier' => 'bronze', 'is_active' => $logo[3] ?? true, 'created_at' => now(),
                    ]);
                }
            }
        }

        // Dotychczasowi sponsorzy spoza projektu trafiają do ostatniej kategorii, kolejność za nowymi.
        DB::table('sponsors')->whereNull('sponsor_group_id')->update(['sponsor_group_id' => $groupId, 'display_order' => DB::raw('display_order + 100')]);
    }

    public function down(): void
    {
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn('about'));
        Schema::dropIfExists('tournament_sponsor');
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_group_id');
            $table->dropColumn('tile_color');
        });
        Schema::dropIfExists('sponsor_groups');
    }
};
