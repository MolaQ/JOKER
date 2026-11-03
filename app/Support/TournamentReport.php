<?php

namespace App\Support;

use App\Livewire\TournamentPage;
use App\Models\Article;
use App\Models\Tournament;
use App\Models\TournamentAward;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Szkic relacji z turnieju po ostatnim meczu: podium każdej kategorii, miejsca naszych drużyn i wyróżnienia.
 * Redakcja dopisuje wrażenia i zdjęcia, a liczby są już na miejscu.
 */
class TournamentReport
{
    /**
     * Istniejący szkic relacji tego turnieju albo nowy; null, gdy turniej jeszcze trwa.
     */
    public static function draft(Tournament $tournament): ?Article
    {
        if ($existing = $tournament->articles()->where('status', 'draft')->latest('id')->first()) {
            return $existing;
        }

        $categories = $tournament->categories()
            ->with(['groups.participants.competitor', 'participants.competitor', 'participants.group', 'matches.home.competitor', 'matches.away.competitor', 'matches.group', 'matches.category.tournament', 'awards.player', 'awards.participant.competitor'])
            ->get();
        $summary = TournamentPage::summary($categories);

        if ($summary === null) {
            return null;
        }

        $name = $tournament->fullName();
        $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
        $html = '<p>'.e(__(':name za nami. Oto wyniki we wszystkich kategoriach.', ['name' => $name])).'</p>';

        foreach ($summary as $entry) {
            $html .= '<h2>'.e($entry['category']->name).'</h2><ul>';
            foreach ($entry['podium'] as $row) {
                $html .= '<li>'.($medals[$row['position']] ?? '').' '.e($row['participant']->name()).'</li>';
            }
            $html .= '</ul>';

            $ours = $entry['ours']->where('position', '>', 3);
            if ($ours->isNotEmpty()) {
                $html .= '<p><strong>'.e(__('Nasze drużyny')).':</strong> '.e($ours->map(fn (array $row) => $row['participant']->name().' – '.__(':place. miejsce', ['place' => $row['position']]))->implode(', ')).'</p>';
            }

            $awards = $entry['category']->awards;
            if ($awards->isNotEmpty()) {
                $html .= '<p><strong>'.e(__('Wyróżnienia')).':</strong></p><ul>'
                    .$awards->map(fn (TournamentAward $award) => '<li>'.e($award->name).': '.e(collect([$award->personLabel(), $award->teamLabel()])->filter()->implode(', ')).'</li>')->implode('')
                    .'</ul>';
            }
        }

        $html .= '<p><a href="'.e(route('tournaments.show', $tournament)).'">'.e(__('Wszystkie mecze, tabele i drabinki na stronie turnieju')).' →</a></p>';

        $image = null;
        if ($tournament->image_path && Storage::disk('public')->exists($tournament->image_path)) {
            $target = 'articles/'.Str::random(8).'-'.basename($tournament->image_path);
            $image = Storage::disk('public')->copy($tournament->image_path, $target) ? $target : null;
        }

        $title = __(':name: wyniki i klasyfikacja', ['name' => $name]);

        return Article::create([
            'title' => $title,
            'slug' => Slug::unique(Article::class, $title),
            'excerpt' => __('Podium we wszystkich kategoriach, miejsca naszych drużyn i wyróżnienia indywidualne.'),
            'content' => $html,
            'featured_image' => $image,
            'author_id' => Auth::id(),
            'status' => 'draft',
            'allow_comments' => true,
            'tournament_id' => $tournament->id,
        ]);
    }
}
