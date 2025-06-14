<?php

namespace App\Services\Transaction;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class UserWorkService
{
    public function getUserWorkSummary(int $perPage) {
        return Cache::remember("user_work_summary_page_$perPage", 60, function () use ($perPage) {
            $query = DB::table('transactions')
                ->selectRaw('user_id, TO_TIMESTAMP(time)::date as date')
                ->groupBy('user_id', 'date')
                ->orderByDesc('date');

            $paginated = $query->paginate($perPage);

            $paginated->getCollection()->transform(function ($record) {
                $transactions = DB::table('transactions')
                    ->where('user_id', $record->user_id)
                    ->whereRaw('TO_TIMESTAMP(time)::date = ?', [$record->date])
                    ->orderBy('time')
                    ->get();

                $user = User::find($record->user_id);
                $work = [];
                $temp = [];

                foreach ($transactions as $t) {
                    $formatted = Carbon::createFromTimestamp($t->time)->format('H:i:s');

                    if ($t->device_id == 1) {
                        $temp['come_time'] = $formatted;
                        $come_ts = $t->time;
                    } elseif ($t->device_id == 2 && isset($temp['come_time'])) {
                        $temp['out_time'] = $formatted;
                        $temp['duration'] = gmdate("H:i:s", $t->time - $come_ts);
                        $work[] = $temp;
                        $temp = [];
                    }
                }

                return [
                    'user_id' => $record->user_id,
                    'user_name' => $user?->username ?? null,
                    'date' => $record->date,
                    'work' => $work
                ];
            });

            return $paginated;
        });
    }

    public function getUserWorkMatrix(?string $startDate = null, ?string $endDate = null)
    {
        $start = $this->safeParseDate($startDate, now()->startOfMonth());
        $end = $this->safeParseDate($endDate, now()->endOfMonth());

        $rawData = DB::table('transactions')
            ->selectRaw('user_id, time, device_id')
            ->whereBetween('time', [$start->timestamp, $end->timestamp])
            ->orderBy('user_id')
            ->orderBy('time')
            ->get()
            ->groupBy(fn ($t) => $t->user_id);

        $matrix = [];
        $dates = [];

        foreach ($rawData as $userId => $transactions) {
            $user = User::find($userId);
            $dayWise = [];

            foreach ($transactions as $t) {
                $carbonTime = Carbon::createFromTimestamp($t->time)->setTimezone('Asia/Tashkent');
                $date = $carbonTime->toDateString();

                $dates[$date] = true;

                if (!isset($dayWise[$date])) {
                    $dayWise[$date] = ['come' => null, 'out' => null];
                }

                if ($t->device_id == 1 && !$dayWise[$date]['come']) {
                    $dayWise[$date]['come'] = $carbonTime;
                }

                if ($t->device_id == 2) {
                    $dayWise[$date]['out'] = $carbonTime;
                }
            }

            $totalSeconds = 0;
            $dayCount = 0;
            $days = [];

            foreach ($dayWise as $date => $times) {
                $come = $times['come'];
                $out = $times['out'];

                $displayCome = $come ? $come->format('H:i') : null;
                $displayOut = $out ? $out->format('H:i') : null;

                $clampedCome = $come && $come->lessThan(Carbon::parse($date . ' 08:00:00')) ? Carbon::parse($date . ' 08:00:00') : $come;
                $clampedOut = $out && $out->greaterThan(Carbon::parse($date . ' 17:30:00')) ? Carbon::parse($date . ' 17:30:00') : $out;

                $duration = 0; // Default to 0 for no work time
                if ($clampedCome && $clampedOut && $clampedCome < $clampedOut) {
                    $duration = $clampedCome->diffInSeconds($clampedOut);
                    // Subtract 1 hour (3600 seconds) for lunch
                    $duration = max(0, $duration - 3600);
                    $totalSeconds += $duration;
                    $dayCount++;
                }

                $days[$date] = [
                    'come' => $displayCome,
                    'out' => $displayOut,
                    'duration' => round($duration / 3600, 1),
                ];
            }

            $matrix[] = [
                'user_id' => $userId,
                'user_name' => $user->username ?? 'Unknown',
                'days' => $days,
                'hours' => round($totalSeconds / 3600, 1),
                'total_days' => $dayCount,
            ];
        }

        $dateHeaders = array_keys($dates);
        sort($dateHeaders);

        return [
            'headers' => $dateHeaders,
            'records' => $matrix,
        ];
    }

    private function safeParseDate(?string $value, Carbon $default): Carbon
    {
        if (!$value) return $default->copy()->startOfDay();

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            return $default->copy()->startOfDay();
        }
    }
}
