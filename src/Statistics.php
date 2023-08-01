<?php

namespace Devront\AdvancedStatistics;

use Devront\AdvancedStatistics\Attributes\AdvancedStatisticsAttribute;
use Devront\AdvancedStatistics\Attributes\Param;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Statistics
{
    private $keep_daily_statistics_for_days = 90;
    private $keep_monthly_statistics_for_months = 24;

    private string $type;
    private string|int|null $owner_id = null;
    private string|null $owner_type = null;
    private Carbon|null $from_date = null;
    private Carbon|null $to_date = null;

    private array $params = [];

    public function __construct()
    {
        $this->initMetadata();
        $this->initSetters();
    }

    public function for(Model|string $owner): self
    {
        if (is_string($owner)) {
            $this->owner_type = class_exists($owner)
                ? (new $owner)->getMorphClass()
                : $owner;
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
        $query = app(AdvancedStatistics::class)->getModelClass()::query()
            ->where('timeframe', 'd')
            ->where('type', $this->type)
            ->where('owner_type', $this->owner_type)
            ->where('owner_id', $this->owner_id)
            ->whereDate('from_date', now()->startOfDay())
            ->whereDate('to_date', now()->endOfDay())
            ->where(function ($q) {
                foreach ($this->params as $param) {
                    $q->where("payload->$param", $this->{$param} ?? null);
                }
            });

        $stats = $query->first();

        if ($stats) {
            $stats->increment('value', $value);
        } else {
            $payload = [];
            foreach ($this->params as $param) {
                $payload[$param] = $this->{$param} ?? null;
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
        $query = app(AdvancedStatistics::class)->getModelClass()::query()
            ->when($this->owner_type, fn($q) => $q->where('owner_type', $this->owner_type))
            ->when($this->owner_id, fn($q) => $q->where('owner_id', $this->owner_id))
            ->when($this->from_date, fn($q) => $q->whereDate('from_date', '>=', $this->from_date))
            ->when($this->to_date, fn($q) => $q->whereDate('to_date', '<=', $this->to_date));

        foreach ($this->params as $param) {
            $value = $this->{$param} ?? null;
            $query->when(isset($this->{$param}), fn($q) => $q->where("payload->$param", $value));
        }

        return $query->sum('value');
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

        $reserved = ['owner_id', 'owner_type', 'from_date', 'to_date', 'params', 'type'];

        foreach ($classProperties as $property) {
            $attributes = $property->getAttributes(Param::class);

            if (!empty($attributes)) {
                $name = strtolower(Str::snake($property->getName()));
                if (in_array($name, $reserved)) {
                    throw new \Exception(join(', ', $reserved) . ' are reserved for internal use and can not be used as statistics params.');
                }
                $this->params[] = $name;
            }
        }
        usort($this->params, function ($a, $b) {
            return strnatcmp($a, $b);
        });
    }

    public function __call($method, $params)
    {
        if (Str::startsWith($method, 'for')) {
            $param = Str::snake(Str::after($method, 'for'));
            if (in_array($param, $this->params)) {
                if (count($params) === 1) {
                    $this->{$param} = $params[0]; // Can be array
                    return $this;
                } else {
                    throw new \Exception($method . '() expects one argument, ' . count($params) . ' passed.');
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
}
