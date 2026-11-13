<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Game;
use App\Models\LinkReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Zgłoszenia niedziałających linków do filmów i transmisji (mecze, mecze turniejowe, turnieje mini).
 * Zalogowana osoba zgłasza link dyskretną ikonką; administrator odpina go albo uznaje, że działa.
 */
class LinkReports
{
    /**
     * Zapisuje zgłoszenie. Zwraca false, gdy rekordu albo linku już nie ma.
     */
    public static function report(User $user, string $key, string $url): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $model = MatchPicker::find($key);

        if (! $model || ! collect($model->normalizedStreams())->contains('url', $url)) {
            return false;
        }

        LinkReport::firstOrCreate(
            ['reportable_type' => $model->getMorphClass(), 'reportable_id' => $model->getKey(), 'url' => $url, 'status' => LinkReport::STATUS_PENDING, 'user_id' => $user->id],
        );

        return true;
    }

    public static function pendingCount(): int
    {
        return LinkReport::pending()->select('reportable_type', 'reportable_id', 'url')->distinct()->get()->count();
    }

    /**
     * Odpina link od meczu albo turnieju i zamyka wszystkie zgłoszenia tego linku.
     */
    public static function remove(LinkReport $report, User $by): void
    {
        $model = $report->reportable;

        if ($model) {
            $streams = collect($model->streams ?? [])
                ->reject(fn ($stream) => trim((string) (is_array($stream) ? ($stream['url'] ?? '') : $stream)) === $report->url)
                ->values()->all();
            $model->streams = $streams ?: null;

            if ($model instanceof Game && trim((string) $model->video_url) === $report->url) {
                $model->video_url = null;
            }

            $model->save();
            AuditLog::record('link.removed', 'Odpięto niedziałający link '.$report->url.' ('.$model->mediaLabel().')', $model);
        }

        self::close($report, LinkReport::STATUS_REMOVED, $by);
    }

    public static function dismiss(LinkReport $report, User $by): void
    {
        self::close($report, LinkReport::STATUS_DISMISSED, $by);
    }

    private static function close(LinkReport $report, string $status, User $by): void
    {
        LinkReport::pending()
            ->where('reportable_type', $report->reportable_type)->where('reportable_id', $report->reportable_id)->where('url', $report->url)
            ->update(['status' => $status, 'resolved_by' => $by->id, 'resolved_at' => now()]);
    }

    public static function canResolve(?User $user): bool
    {
        return (bool) $user?->is_active && ($user->can('media.manage') || $user->can('games.manage'));
    }

    public static function keyFor(?Model $model): string
    {
        return MatchPicker::keyFor($model);
    }
}
