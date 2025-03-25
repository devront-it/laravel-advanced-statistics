<?php

namespace Devront\AdvancedStatistics\Models;

use Devront\AdvancedStatistics\AdvancedStatistics;
use Devront\AdvancedStatistics\Statistics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Statistic extends Model
{
    protected static function boot()
    {
        parent::boot();
        if (app(AdvancedStatistics::class)->isUsingUuids()) {
            static::creating(function ($model) {
                $model->keyType = 'string';
                $model->incrementing = false;

                $model->{$model->getKeyName()} = $model->{$model->getKeyName()} ?: (string)Str::orderedUuid();
            });
        }
    }

    public function getIncrementing()
    {
        return !app(AdvancedStatistics::class)->isUsingUuids();
    }

    public function getKeyType()
    {
        return app(AdvancedStatistics::class)->isUsingUuids() ? 'string' : $this->keyType;
    }

    public function getTable()
    {
        return app(AdvancedStatistics::class)->getTableName();
    }

    protected $fillable = [
        'type',
        'timeframe',
        'value',
        'from_date',
        'to_date',
        'owner_id',
        'owner_type',
        'payload',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'payload' => 'json',
    ];

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeTimeframe($query, $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public static function calculateMonthlyStatistics()
    {
        foreach (app(AdvancedStatistics::class)->statistics as $statistics_class) {
            $statistics_instance = new $statistics_class;
            if (!is_a($statistics_instance, Statistics::class)) continue;
            $type = $statistics_instance->getType();
            $keep_daily_statistics_for_days = $statistics_instance->getKeepDailyStatisticsForDays();
            $keep_monthly_statistics_for_months = $statistics_instance->getKeepMonthlyStatisticsForMonths();

            if (!$type) continue;

            // Clean up daily statistics
            $delete_older_than = now()->subDays($keep_daily_statistics_for_days - 1)->startOfDay();

            $payload_keys = $statistics_instance->getParams();

            $statistics = static::query()
                ->timeframe('d')
                ->type($type)
                ->whereDate('from_date', '<', $delete_older_than)
                ->get();

            $key_delimiter = "%~%";

            if (!$payload_keys) {
                // Simply add the daily statistics to the monthly statistics
                foreach ($statistics as $stats) {
                    $stat = static::query()->firstOrCreate([
                        'type' => $type,
                        'timeframe' => 'm',
                        'owner_id' => $stats->owner_id,
                        'owner_type' => $stats->owner_type,
                        'from_date' => $stats->from_date->startOfMonth()->format('Y-m-d'),
                        'to_date' => $stats->from_date->endOfMonth()->format('Y-m-d'),
                    ], [
                        'payload' => $stats->payload,
                    ]);
                    $stat->increment('value', $stats->value);
                }
            } else {
                // Group by the payload key, then increment as above
                $statistics = $statistics->groupBy(function ($stats) use ($payload_keys, $key_delimiter) {
                    return join($key_delimiter, array_map(fn($pk) => $stats->payload[$pk], $payload_keys));
                });
                // Key can be a sku, for example
                foreach ($statistics as $key => $stats_array) {
                    $stats = $stats_array->first();
                    if (!$stats) continue;
                    $query = static::query()
                        ->where([
                            'type' => $type,
                            'timeframe' => 'm',
                            'owner_id' => $stats->owner_id,
                            'owner_type' => $stats->owner_type,
                            'from_date' => $stats->from_date->startOfMonth(),
                            'to_date' => $stats->from_date->endOfMonth(),
                        ]);
                    $values = explode($key_delimiter, $key);
                    foreach ($payload_keys as $index => $payload_key) {
                        $query->where("payload->$payload_key", $values[$index] ?? null);
                    }

                    $monthly_stats = $query->first();

                    if (!$monthly_stats) {
                        static::create([
                            'type' => $type,
                            'timeframe' => 'm',
                            'owner_id' => $stats->owner_id,
                            'owner_type' => $stats->owner_type,
                            'from_date' => $stats->from_date->startOfMonth(),
                            'to_date' => $stats->from_date->endOfMonth(),
                            'payload' => $stats->payload,
                            'value' => $stats_array->sum('value')
                        ]);
                    } else {
                        $monthly_stats->increment('value', $stats_array->sum('value'));
                    }
                }
            }

            // Delete
            static::query()
                ->timeframe('d')
                ->type($type)
                ->whereDate('from_date', '<', $delete_older_than)
                ->delete();

            // Clean up monthly statistics
            $delete_older_than = now()->subMonths($keep_monthly_statistics_for_months)->startOfMonth();

            static::query()
                ->timeframe('m')
                ->type($type)
                ->whereDate('from_date', '<', $delete_older_than)
                ->delete();
        }

    }
}
