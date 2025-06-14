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

    public function getUserWorkDetails(int $perPage) {
        return Cache::remember("user_work_detail_page_$perPage", 60, function () use ($perPage) {
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
                $totalSeconds = 0;

                foreach ($transactions as $t) {
                    $carbonTime = Carbon::createFromTimestamp($t->time)->setTimezone('Asia/Tashkent');

                    if ($t->device_id == 1) {
                        $temp['come'] = $carbonTime;
                    } elseif ($t->device_id == 2 && isset($temp['come'])) {
                        $come = $temp['come'];
                        $out = $carbonTime;

                        $workStart = Carbon::parse($come->toDateString() . ' 08:00:00');
                        $workEnd = Carbon::parse($come->toDateString() . ' 17:30:00');
                        $abedStart = Carbon::parse($come->toDateString() . ' 12:00:00');
                        $abedEnd = Carbon::parse($come->toDateString() . ' 13:00:00');

                        $clampedCome = $come->copy()->lessThan($workStart) ? $workStart : $come;
                        $clampedOut = $out->copy()->greaterThan($workEnd) ? $workEnd : $out;

                        if ($clampedCome < $clampedOut) {
                            $interval = $clampedCome->diffInSeconds($clampedOut);

                            if ($clampedCome < $abedEnd && $clampedOut > $abedStart) {
                                $overlapStart = $clampedCome->greaterThan($abedStart) ? $clampedCome : $abedStart;
                                $overlapEnd = $clampedOut->lessThan($abedEnd) ? $clampedOut : $abedEnd;
                                $interval -= $overlapStart->diffInSeconds($overlapEnd);
                            }

                            $totalSeconds += $interval;

                            $work[] = [
                                'come_time' => $come->format('H:i:s'),
                                'out_time' => $out->format('H:i:s'),
                                'duration' => gmdate('H:i:s', $interval),
                            ];
                        }

                        $temp = [];
                    }
                }

                return [
                    'user_id' => $record->user_id,
                    'user_name' => $user?->username ?? null,
                    'date' => $record->date,
                    'work' => $work,
                    'total_duration' => gmdate('H:i:s', $totalSeconds),
                    'suspected' => $totalSeconds > 43200, // more than 12 hours
                ];
            });

            return $paginated;
        });
    }
}
