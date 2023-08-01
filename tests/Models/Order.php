<?php

namespace Devront\AdvancedStatistics\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'number',
        'country',
        'source',
        'user_id',
    ];
}
