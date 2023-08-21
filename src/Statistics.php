<?php

namespace Devront\AdvancedStatistics;

use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
use Devront\AdvancedStatistics\Attributes\Avg;
use Devront\AdvancedStatistics\Attributes\Param;
use Devront\AdvancedStatistics\Models\Statistic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Statistics
{
    private $keep_daily_statistics_for_days = 90;
    private $keep_monthly_statistics_for_months = 24;

    private string $type;
    private string|int|array|null $owner_id = null;
    private string|null $owner_type = null;
    private Carbon|null $from_date = null;
    private Carbon|null $to_date = null;

    private array $params = [];
    private array $averages = [];
    private array $params_values = [];
    private array $averages_values = [];

    public function __construct()
    {
        $this->initMetadata();
        $this->initSetters();
    }

    public function for(Model|string $owner, array|null $ids = null): self
    {
        if (is_string($owner)) {
            $this->owner_type = class_exists($owner)
                ? (new $owner)->getMorphClass()
                : $owner;
            $this->owner_id = $ids;
        } else {
            $this->owner_id = $owner->getKey();
            $this->owner_type = $owner->getMorphClass();
        }
        return $this;
    }

    public function from($from)
    {
        $this->from_date = Carbon::parse($from);
        return $this;
    }

    public function to($to)
    {
        $this->to_date = Carbon::parse($to);
        return $this;
    }

    public function hit(int|float $value = 1)
    {
        // Avg
        foreach ($this->averages as $average) {
            throw_if(
                !array_key_exists($average, $this->averages_values)
                || (!is_numeric($this->averages_values[$average]) && !is_null($this->averages_values[$average])),
                new \Exception($average . ' average must be set and numeric or null before hitting the stats.')
            );
        }

        $query = app(AdvancedStatistics::class)->getModelClass()::query()
            ->where('timeframe', 'd')
            ->where('type', $this->type)
            ->where('owner_type', $this->owner_type)
            ->where('owner_id', $this->owner_id)
            ->whereDate('from_date', now()->startOfDay())
            ->whereDate('to_date', now()->endOfDay())
            ->where(function ($q) {
                foreach ($this->params as $param) {
                    $q->where("payload->$param", $this->params_values[$param] ?? null);
                }
            });

        $stats = $query->first();

        if ($stats) {
            $old_items_count = $stats->value;
            $stats->value += $value;
            $new_items_count = $stats->value;

            // Avg
            $payload = $stats->payload;
            foreach ($this->averages as $average) {
                if (is_null($this->averages_values[$average])) continue;
                if (!isset($payload['avg'])) $payload['avg'] = [];
                $old_avg = $payload['avg'][$average] ?? 0;
                $new_avg = (($old_avg * $old_items_count) + ($this->averages_values[$average] * $value)) / $new_items_count;
                $payload['avg'][$average] = $new_avg;
            }
            $stats->payload = $payload;

            $stats->save();
        } else {
            $payload = [];
            foreach ($this->params as $param) {
                $payload[$param] = $this->params_values[$param] ?? null;
            }
            foreach ($this->averages as $average) {
                if (!isset($payload['avg'])) $payload['avg'] = [];
                $payload['avg'][$average] = $this->averages_values[$average];
            }
            app(AdvancedStatistics::class)->getModelClass()::create([
                'timeframe' => 'd',
                'type' => $this->type,
                'owner_type' => $this->owner_type,
                'owner_id' => $this->owner_id,
                'from_date' => now()->startOfDay(),
                'to_date' => now()->endOfDay(),
                'payload' => $payload,
                'value' => $value
            ]);
        }
    }

    public function get()
    {
        return $this->baseQuery()->sum('value');
    }

    /**
     * @return Collection<Statistic>
     */
    public function getEntries(): Collection
    {
        return $this->baseQuery()->get();
    }

    /**
     * @param $fields string|array params to group by
     * @param $count int Number of results to return
     * @return Collection Collection of items with total_value and the params grouped by
     */
    public function top(string|array $fields, int $count = 5): Collection
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        // Construct the grouping and selecting logic
        $groupBy = [];
        $select = [];

        foreach ($fields as $field) {
            $groupBy[] = "payload->$field";
            $select[] = "payload->$field as $field";
        }
        $select[] = DB::raw('SUM(value) as total_value');

        $results = $this->baseQuery()
            ->groupBy($groupBy)
            ->select($select)
            ->orderBy('total_value', 'desc')
            ->limit($count)
            ->get();

        foreach ($results as $result) {
            foreach ($fields as $field) {
                $result->{$field} = trim($result->{$field}, '"');
            }
        }

        return $results;
    }

    private function baseQuery()
    {
        $query = app(AdvancedStatistics::class)->getModelClass()::query()
            ->when($this->owner_type, fn($q) => $q->where('owner_type', $this->owner_type))
            ->when(!is_null($this->owner_id), function ($q) {
                if (is_array($this->owner_id)) {
                    $q->whereIn('owner_id', $this->owner_id);
                } else {
                    $q->where('owner_id', $this->owner_id);
                }
            })
            ->when($this->from_date, fn($q) => $q->whereDate('from_date', '>=', $this->from_date))
            ->when($this->to_date, fn($q) => $q->whereDate('to_date', '<=', $this->to_date));

        foreach ($this->params as $param) {
            $value = $this->params_values[$param] ?? null;
            if (is_array($value)) {
                $query->when(isset($this->params_values[$param]), fn($q) => $q->whereIn("payload->$param", $value));
            } else {
                $query->when(isset($this->params_values[$param]), fn($q) => $q->where("payload->$param", $value));
            }
        }
        return $query;
    }

    private function getAverageFor($avg_name, $places = 2)
    {
        $total_items = 0;
        $total_avg = 0;
        foreach ($this->baseQuery()->get() as $stat) {
            if (isset($stat->payload['avg'][$avg_name])) {
                $total_items += $stat->value;
                $total_avg += $stat->payload['avg'][$avg_name] * $stat->value;
            }
        }
        if ($total_items === 0) return 0;
        return round($total_avg / $total_items, $places);
    }

    private function initMetadata()
    {
        $reflectionClass = new \ReflectionClass($this);
        $classAttributes = $reflectionClass->getAttributes();

        foreach ($classAttributes as $attribute) {
            $instance = $attribute->newInstance();
            if (get_class($instance) === AdvancedStatisticsAttribute::class) {
                $this->keep_daily_statistics_for_days = $instance->keepDailyStatisticsForDays;
                $this->keep_monthly_statistics_for_months = $instance->keepMonthlyStatisticsForMonths;
                $this->type = $instance->type ?? get_class($this);
            }
        }
    }

    private function initSetters()
    {
        $reflectionClass = new \ReflectionClass($this);
        $classProperties = $reflectionClass->getProperties();

        $reserved = ['owner_id', 'owner_type', 'from_date', 'to_date', 'params', 'type', 'averages'];

        foreach ($classProperties as $property) {
            $param_attributes = $property->getAttributes(Param::class);

            if (!empty($param_attributes)) {
                $name = strtolower(Str::snake($property->getName()));
                if (in_array($name, $reserved)) {
                    throw new \Exception(join(', ', $reserved) . ' are reserved for internal use and can not be used as statistics params.');
                }
                $this->params[] = $name;
            } else {
                $avg_attributes = $property->getAttributes(Avg::class);
                if (!empty($avg_attributes)) {
                    $name = strtolower(Str::snake($property->getName()));
                    if (in_array($name, $reserved)) {
                        throw new \Exception(join(', ', $reserved) . ' are reserved for internal use and can not be used as statistics avg.');
                    }
                    $this->averages[] = $name;
                }
            }
        }
        usort($this->params, function ($a, $b) {
            return strnatcmp($a, $b);
        });
        usort($this->averages, function ($a, $b) {
            return strnatcmp($a, $b);
        });
    }

    public function __call($method, $params)
    {
        if (Str::startsWith($method, 'getAverage')) {
            $avg_name = Str::snake(Str::after($method, 'getAverage'));
            if (in_array($avg_name, $this->averages)) {
                return $this->getAverageFor($avg_name, isset($params[0]) ? $params[0] : 2);
            }
        } else {
            if (in_array(Str::snake($method), $this->averages)) {
                $avg_name = Str::snake($method);
                $value = $params[0];
                if (!is_null($value) && !is_numeric($value)) throw new \Exception('The value for Avg attributes must be numeric.');
                $this->averages_values[$avg_name] = $value;
                return $this;
            } else if (Str::startsWith($method, 'for')) {
                $param = Str::snake(Str::after($method, 'for'));
                if (in_array($param, $this->params)) {
                    if (count($params) === 1) {
                        $this->params_values[$param] = $params[0]; // Can be array
                        return $this;
                    } else {
                        throw new \Exception($method . '() expects one argument, ' . count($params) . ' passed.');
                    }
                }
            }
        }
    }

    public function getKeepDailyStatisticsForDays()
    {
        return $this->keep_daily_statistics_for_days;
    }

    public function getKeepMonthlyStatisticsForMonths()
    {
        return $this->keep_monthly_statistics_for_months;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getAverages()
    {
        return $this->averages;
    }
}
