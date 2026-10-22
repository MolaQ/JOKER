<?php

namespace Database\Seeders;

use App\Models\Alumnus;
use App\Models\Season;
use App\Models\Setting;
use App\Models\SupportPackage;
use App\Support\SiteSettings;
use Illuminate\Database\Seeder;

/**
 * Start strony z czystą bazą (bez przykładowych aktualności): sezony, poziomy rozgrywek, podstrony,
 * 7 drużyn klubu z trenerami, rozgrywki bieżącego sezonu z adresami stron związku oraz konta administratora, szefa i zawodnika z adresami i hasłami z .env
 * (SEED_*). Pozostałych zawodników i sztab wczytasz w panelu: Import z plików.
 * Można uruchomić ponownie: nie dubluje danych i nie zmienia haseł.
 */
class StartSeeder extends Seeder
{
    public function run(): void
    {
        if (Season::doesntExist()) {
            $club = new ClubSeeder;
            $club->seedSeasons();
            $club->seedCompetitionLevels();
        }

        $this->ensureCurrentSeason();
        $this->seedContact();
        $this->seedSupport();

        $this->call([PagesSeeder::class, ClubAccountsSeeder::class]);

        // Rozgrywki bieżącego sezonu z adresami stron związku: terminarz, wyniki i tabele
        // pobierze `php artisan joker:sync-schedules` (i potem cron co 3 godziny).
        $club = new ClubSeeder;
        if ($this->command) {
            $club->setCommand($this->command);
        }
        $club->seedCompetitions();
    }

    /**
     * Dane kontaktowe klubu i szkoły (LO Mistrzostwa Sportowego) w stopce, na stronie Kontakt i dla Google.
     * Zapisuje tylko pola puste albo ze starym domyślnym adresem „Piła”, więc zmian z panelu nie nadpisuje.
     */
    private function seedContact(): void
    {
        $site = app(SiteSettings::class);
        $contact = [
            'contact_email' => 'smsjokerpila@gmail.com',
            'contact_phone' => '+48 531 064 100',
            'contact_address' => 'ul. Wincentego Pola 11, 64-920 Piła',
            'contact_school_name' => 'Liceum Ogólnokształcące Mistrzostwa Sportowego w Pile',
            'contact_school_phone' => '+48 67 349 00 93',
            'contact_school_url' => 'https://lo-ms.pila.pl/',
        ];
        $stored = Setting::whereIn('key', array_keys($contact))->pluck('value', 'key');

        $site->set(collect($contact)
            ->filter(fn (string $value, string $key) => blank($stored->get($key)) || $stored->get($key) === 'Piła')
            ->all());
    }

    /**
     * Najbardziej znany wychowanek i szkic pakietów sponsorskich (korzyści ze strony „Współpraca”).
     * Pakiety powstają tylko, gdy nie ma żadnego; ceny i zakres ustawisz w panelu: Wspieraj klub.
     */
    private function seedSupport(): void
    {
        Alumnus::firstOrCreate(['name' => 'Mateusz Malinowski'], ['level' => 'PlusLiga', 'is_featured' => true]);

        if (SupportPackage::exists()) {
            return;
        }

        $packages = [
            ['Partner główny', true, "Logo na strojach zawodników\nLogo na banerach w hali podczas meczów i turniejów\nWyróżnienie na stronie i w mediach społecznościowych\nWspólne akcje marketingowe i wydarzenia\nRaport z działań promocyjnych po sezonie"],
            ['Partner', false, "Logo na banerach w hali\nLogo na stronie klubu\nPodziękowania w mediach społecznościowych"],
            ['Przyjaciel klubu', false, "Wpis na stronie klubu\nPodziękowanie w mediach społecznościowych"],
        ];

        foreach ($packages as $order => [$name, $highlighted, $benefits]) {
            SupportPackage::create(['name' => $name, 'price' => 'Cena do ustalenia', 'benefits' => $benefits, 'is_highlighted' => $highlighted, 'display_order' => $order]);
        }
    }

    /**
     * Sezon trwający dziś (od 1 września) musi istnieć i być bieżący, bo do niego trafiają rozgrywki.
     * Gdy bieżący sezon jest już ustawiony w panelu, zostaje bez zmian.
     */
    private function ensureCurrentSeason(): void
    {
        if (Season::current()->exists()) {
            return;
        }

        $startYear = now()->month >= 8 ? now()->year : now()->year - 1;
        $season = Season::firstOrCreate(['name' => $startYear.'/'.($startYear + 1)], [
            'start_date' => "{$startYear}-09-01",
            'end_date' => ($startYear + 1).'-06-30',
        ]);
        $season->update(['is_current' => true]);

        $this->command?->info("Ustawiono bieżący sezon: {$season->name}.");
    }
}
