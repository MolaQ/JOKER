<?php

namespace App\Livewire\Admin;

use App\Http\Controllers\SitemapController;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Article;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Player;
use App\Models\Team;
use App\Support\Sitemap;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Panel „Pozycjonowanie (SEO)”: budowanie mapy strony, robots.txt i kontrola braków w treściach.
 */
class SeoManager extends Component
{
    use ShowsToasts;

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('site.manage'), 403);
    }

    public function rebuild(): void
    {
        Sitemap::build();
        $this->toast('Mapa strony zbudowana: '.Sitemap::meta()['count'].' adresów.');
    }

    /**
     * Sprawdzenia konfiguracji i treści: [poziom ok|warn|bad, tytuł, opis, link do poprawy].
     *
     * @return list<array{0: string, 1: string, 2: string, 3: ?string}>
     */
    private function checks(SiteSettings $site): array
    {
        $appUrl = (string) config('app.url');
        $published = Article::published();
        $noImage = (clone $published)->where(fn ($q) => $q->whereNull('featured_image')->orWhere('featured_image', ''))->count();
        $noExcerpt = (clone $published)->where(fn ($q) => $q->whereNull('excerpt')->orWhere('excerpt', ''))->count();
        $playersNoPhoto = Player::where('is_active', true)->whereNull('photo_path')->count();
        $teamsNoPhoto = Team::whereNull('photo_path')->count();
        $staticRobots = file_exists(public_path('robots.txt'));

        return [
            str_starts_with($appUrl, 'https://') && ! str_contains($appUrl, 'localhost')
                ? ['ok', 'Adres strony', 'APP_URL = '.$appUrl.'. Pod tym adresem są linki w mapie strony i adresy kanoniczne.', null]
                : ['bad', 'Adres strony', 'W pliku .env APP_URL = '.$appUrl.'. Ustaw docelowy adres z https:// (np. https://jokerpila.pl), inaczej Google dostanie złe linki.', null],
            config('app.debug')
                ? ['bad', 'Tryb debugowania włączony', 'W .env ustaw APP_DEBUG=false na serwerze produkcyjnym: błędy nie pokażą wtedy szczegółów kodu i strony działają szybciej.', null]
                : ['ok', 'Tryb produkcyjny', 'APP_DEBUG jest wyłączony.', null],
            $staticRobots
                ? ['bad', 'Plik public/robots.txt zasłania właściwy', 'Na serwerze jest stary plik public/robots.txt, więc Google nie widzi adresu mapy strony ani blokady panelu. Usuń ten plik, a strona sama poda poprawny robots.txt.', null]
                : ['ok', 'robots.txt', 'Generowany przez stronę: blokuje panel i logowanie, wskazuje mapę strony.', route('robots')],
            $site['seo_google_verification']
                ? ['ok', 'Google Search Console', 'Kod weryfikacji jest na stronie. W Search Console dodaj mapę strony: '.route('sitemap'), 'https://search.google.com/search-console']
                : ['warn', 'Google Search Console', 'Dodaj stronę w Search Console (weryfikacja „Tag HTML”) i wklej kod w Ustawieniach. Zobaczysz, na jakie frazy klub się wyświetla i czy są błędy.', route('admin.settings.index', ['zakladka' => 'seo'])],
            filled($site['seo_awards'])
                ? ['ok', 'Wyróżnienia klubu', 'Są w danych o klubie dla Google.', route('admin.settings.index', ['zakladka' => 'seo'])]
                : ['warn', 'Wyróżnienia klubu', 'Wpisz oficjalne certyfikaty i nagrody (np. certyfikat ośrodka szkolenia); trafią do danych o klubie dla wyszukiwarek.', route('admin.settings.index', ['zakladka' => 'seo'])],
            $noExcerpt
                ? ['warn', 'Artykuły bez zajawki: '.$noExcerpt, 'Zajawka jest opisem w wynikach Google. Bez niej Google sam wytnie fragment tekstu.', route('admin.articles.index')]
                : ['ok', 'Zajawki artykułów', 'Każdy opublikowany artykuł ma zajawkę.', null],
            $noImage
                ? ['warn', 'Artykuły bez zdjęcia: '.$noImage, 'Zdjęcie wyróżniające pokazuje się przy udostępnianiu na Facebooku i w Google Discover.', route('admin.articles.index')]
                : ['ok', 'Zdjęcia artykułów', 'Każdy opublikowany artykuł ma zdjęcie.', null],
            $playersNoPhoto
                ? ['warn', 'Aktywni zawodnicy bez zdjęcia: '.$playersNoPhoto, 'Profile ze zdjęciem częściej pojawiają się w wyszukiwaniu grafiki po nazwisku.', route('admin.players.index')]
                : ['ok', 'Zdjęcia zawodników', 'Wszyscy aktywni zawodnicy mają zdjęcie.', null],
            $teamsNoPhoto
                ? ['warn', 'Drużyny bez własnego zdjęcia: '.$teamsNoPhoto, 'Strona pokazuje wtedy zdjęcie zastępcze; własne zdjęcie drużyny jest lepsze dla Google i kibiców.', route('admin.teams.index')]
                : ['ok', 'Zdjęcia drużyn', 'Każda drużyna ma zdjęcie.', null],
        ];
    }

    public function render(SiteSettings $site): View
    {
        $meta = Sitemap::meta();

        return view('livewire.admin.seo-manager', [
            'meta' => $meta,
            'builtAt' => isset($meta['built_at']) ? Carbon::parse($meta['built_at']) : null,
            'checks' => $this->checks($site),
            'robots' => app(SitemapController::class)->robots()->getContent(),
            'staticRobots' => file_exists(public_path('robots.txt')) ? (string) file_get_contents(public_path('robots.txt')) : null,
            'counts' => [
                'Artykuły' => Article::published()->count(),
                'Podstrony' => Page::published()->count(),
                'Drużyny' => Team::count(),
                'Zawodnicy' => Player::where('is_active', true)->count(),
                'Galerie' => Gallery::published()->count(),
            ],
        ]);
    }
}
