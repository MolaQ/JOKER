<?php

namespace App\Support;

use App\Livewire\Admin\AccountActivation;
use App\Livewire\Admin\CommentModeration;
use App\Livewire\Admin\ParentAssignment;
use App\Livewire\Admin\PhotoTagReview;
use App\Models\ContentProposal;
use App\Models\PlayerChangeRequest;
use App\Models\ProfileChangeRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Wszystko, co czeka na zatwierdzenie, zebrane w jednym miejscu panelu („Do zatwierdzenia”).
 * Każda sekcja ma własny adres i uprawnienie; użytkownik widzi tylko te, do których ma prawo.
 */
class Approvals
{
    /**
     * @return Collection<string, array{label: string, icon: string, route: string, intro: string, component: string, count: int}>
     */
    public static function sectionsFor(User $user): Collection
    {
        return once(fn () => self::build($user));
    }

    /**
     * @return Collection<string, array{label: string, icon: string, route: string, intro: string, component: string, count: int}>
     */
    private static function build(User $user): Collection
    {
        $sections = [
            'rodzice' => [
                'label' => 'Rodzice do przypisania',
                'icon' => 'users',
                'route' => 'admin.parents.index',
                'intro' => 'Nowe konta rodziców i rodzice bez przypisanego dziecka. Przy każdym koncie podpowiadamy zawodnika, o którym pisał rodzic: jedno kliknięcie aktywuje konto i przypisuje dziecko. Rodzic dostaje e-mail, że konto działa.',
                'component' => 'admin.parent-assignment',
                'visible' => fn () => $user->can('users.activate'),
                'count' => fn () => ParentAssignment::countFor($user),
            ],
            'konta' => [
                'label' => 'Konta do aktywacji',
                'icon' => 'userplus',
                'route' => 'admin.accounts.index',
                'intro' => 'Nowe konto działa dopiero, gdy powiążesz je z profilem: zawodnika z jego profilem w kadrze, trenera z drużyną. Rodzice są w zakładce „Rodzice do przypisania”. Konto kibica aktywuje się samo po potwierdzeniu e-maila.',
                'component' => 'admin.account-activation',
                'visible' => fn () => $user->can('users.activate'),
                'count' => fn () => AccountActivation::pendingFor($user)->count(),
            ],
            'propozycje' => [
                'label' => 'Propozycje treści',
                'icon' => 'light',
                'route' => 'admin.content-proposals.index',
                'intro' => 'Artykuły, zdjęcia i transmisje zaproponowane przez rodziców i zawodników. Zatwierdzony artykuł od razu jest aktualnością, zdjęcia trafiają do galerii meczu, a transmisja do meczu.',
                'component' => 'admin.content-proposal-manager',
                'visible' => fn () => $user->can('proposals.content'),
                'count' => fn () => ContentProposal::pending()->count(),
            ],
            'komentarze' => [
                'label' => 'Komentarze',
                'icon' => 'chat',
                'route' => 'admin.comments.index',
                'intro' => 'Komentarze kibiców pojawiają się na stronie dopiero po akceptacji. Komentarze rodziców, zawodników i osób z klubu publikują się od razu, ale tu też możesz je ukryć albo usunąć.',
                'component' => 'admin.comment-moderation',
                'visible' => fn () => $user->can('comments.moderate'),
                'count' => fn () => CommentModeration::pendingCount(),
            ],
            'oznaczenia' => [
                'label' => 'Oznaczenia na zdjęciach',
                'icon' => 'tag',
                'route' => 'admin.photo-tags.index',
                'intro' => 'Oznaczenia zawodników, trenerów i drużyn zaproponowane przez rodziców i zawodników. Trener zatwierdza oznaczenia zawodników swoich drużyn.',
                'component' => 'admin.photo-tag-review',
                'visible' => fn () => $user->can('proposals.content') || $user->can('proposals.review'),
                'count' => fn () => PhotoTagReview::pendingFor($user)->count(),
            ],
            'zawodnicy' => [
                'label' => 'Zmiany od zawodników',
                'icon' => 'user',
                'route' => 'admin.player-change-requests.index',
                'intro' => 'Nowe zdjęcia i dane zaproponowane przez zawodników oraz ich rodziców. Zatwierdzone od razu pojawiają się w profilu.',
                'component' => 'admin.player-change-request-manager',
                'visible' => fn () => $user->can('proposals.review'),
                'count' => fn () => PlayerChangeRequest::pending()->reviewableBy($user)->count(),
            ],
            'profile' => [
                'label' => 'Zmiany profili kont',
                'icon' => 'envelope',
                'route' => 'admin.profile-change-requests.index',
                'intro' => 'Prośby o zmianę danych konta zgłoszone przez zawodników, rodziców i kibiców.',
                'component' => 'admin.profile-change-request-manager',
                'visible' => fn () => $user->can('proposals.review'),
                'count' => fn () => ProfileChangeRequest::where('status', 'pending')->count(),
            ],
        ];

        return collect($sections)
            ->filter(fn (array $section) => ($section['visible'])())
            ->map(function (array $section) {
                $section['count'] = ($section['count'])();
                unset($section['visible']);

                return $section;
            });
    }

    public static function pendingCountFor(User $user): int
    {
        return self::sectionsFor($user)->sum('count');
    }
}
