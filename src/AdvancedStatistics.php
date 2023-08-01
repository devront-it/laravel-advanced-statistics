<?php

namespace Devront\AdvancedStatistics;

use Devront\AdvancedStatistics\Models\Statistic;

class AdvancedStatistics
{
    private string $table_prefix = 'advanced_';
    private bool $use_uuids = false;
    private string $model_class = Statistic::class;

    public array $statistics = [];

    public function useStatistics(array $statistics, bool $merge = true)
    {
        if (!$merge) $this->statistics = [];
        $this->statistics = array_unique(array_merge($this->statistics, $statistics));
        return $this;
    }

    public function useModel($model_class)
    {
        $this->model_class = $model_class;
        return $this;
    }

    public function useUuids()
    {
        $this->use_uuids = true;
        return $this;
    }

    public function withTablePrefix($prefix)
    {
        $this->table_prefix = $prefix;
        return $this;
    }

    public function getTableName()
    {
        return $this->table_prefix . 'statistics';
    }

    public function isUsingUuids()
    {
        return $this->use_uuids;
    }

    public function getModelClass()
    {
        return $this->model_class;
    }
}
